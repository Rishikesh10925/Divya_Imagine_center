<?php
session_start();
// Authentication and database connection
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'accountant') {
    http_response_code(403);
    die(json_encode(["error" => "Unauthorized access."]));
}
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (isset($_GET['action']) && $_GET['action'] == 'getAccountantDashboardData') {
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');
    $end_date_for_query = $end_date . ' 23:59:59';
    $response = [];

    // --- Core metrics ---
    $metrics = [
        'total_earnings' => 0.0,
        'total_discounts' => 0.0,
        'total_payouts' => 0.0,
        'pending_payouts' => 0.0,
    ];

    // Total earnings (paid bills)
    $earnings_sql = "SELECT COALESCE(SUM(net_amount), 0) AS total_earnings FROM bills WHERE payment_status = 'Paid' AND bill_status != 'Void' AND created_at BETWEEN ? AND ?";
    if ($stmt = $conn->prepare($earnings_sql)) {
        $stmt->bind_param('ss', $start_date, $end_date_for_query);
        $stmt->execute();
        $stmt->bind_result($total_earnings);
        $stmt->fetch();
        $metrics['total_earnings'] = (float) $total_earnings;
        $stmt->close();
    }

    // Total discounts (all discounts applied on non-void bills)
    $discounts_sql = "SELECT COALESCE(SUM(discount), 0) AS total_discounts FROM bills WHERE bill_status != 'Void' AND created_at BETWEEN ? AND ?";
    if ($stmt = $conn->prepare($discounts_sql)) {
        $stmt->bind_param('ss', $start_date, $end_date_for_query);
        $stmt->execute();
        $stmt->bind_result($total_discounts);
        $stmt->fetch();
        $metrics['total_discounts'] = (float) $total_discounts;
        $stmt->close();
    }

    // Total payouts recorded for the period, aggregated by doctor for accurate pending calculation
    $doctor_payouts = [];
    $payouts_sql = "SELECT doctor_id, COALESCE(SUM(payout_amount), 0) AS total_payout FROM doctor_payout_history WHERE paid_at BETWEEN ? AND ? GROUP BY doctor_id";
    if ($stmt = $conn->prepare($payouts_sql)) {
        $stmt->bind_param('ss', $start_date, $end_date_for_query);
        $stmt->execute();
        $result = $stmt->get_result();
        $total_payout_amount = 0.0;
        while ($row = $result->fetch_assoc()) {
            $doctorId = (int) $row['doctor_id'];
            $paidAmount = (float) $row['total_payout'];
            $doctor_payouts[$doctorId] = $paidAmount;
            $total_payout_amount += $paidAmount;
        }
        $metrics['total_payouts'] = $total_payout_amount;
        $stmt->close();
    }

    // Pending payouts per doctor using payable charges minus payouts already recorded in the same period
    $pending_sql = "
        SELECT rd.id AS doctor_id,
               SUM(COALESCE(dtp.payable_amount, t.default_payable_amount, 0)) AS total_payable
        FROM bills b
        JOIN bill_items bi ON b.id = bi.bill_id
        JOIN tests t ON bi.test_id = t.id
        JOIN referral_doctors rd ON b.referral_doctor_id = rd.id
        LEFT JOIN doctor_test_payables dtp ON rd.id = dtp.doctor_id AND bi.test_id = dtp.test_id
        WHERE b.payment_status = 'Paid'
          AND b.bill_status != 'Void'
          AND b.referral_type = 'Doctor'
          AND (b.discount = 0 OR b.discount_by = 'Center')
          AND b.created_at BETWEEN ? AND ?
        GROUP BY rd.id
    ";

    if ($stmt = $conn->prepare($pending_sql)) {
        $stmt->bind_param('ss', $start_date, $end_date_for_query);
        $stmt->execute();
        $result = $stmt->get_result();
        $pending_total = 0.0;
        while ($row = $result->fetch_assoc()) {
            $doctorId = (int) $row['doctor_id'];
            $payable = (float) $row['total_payable'];
            $alreadyPaid = $doctor_payouts[$doctorId] ?? 0.0;
            $pending_total += max(0.0, $payable - $alreadyPaid);
        }
        $metrics['pending_payouts'] = $pending_total;
        $stmt->close();
    }

    $response['metrics'] = $metrics;
    
    // --- Charts ---

    // Revenue vs Expenses (Last 6 Months - This chart is intentionally not affected by date filter)
    $rev_exp_query = "SELECT DATE_FORMAT(created_at, '%b %Y') as month, SUM(revenue) as revenue, SUM(expenses) as expenses FROM (SELECT created_at, net_amount as revenue, 0 as expenses FROM bills WHERE bill_status != 'Void' AND payment_status = 'Paid' UNION ALL SELECT created_at, 0 as revenue, amount as expenses FROM expenses WHERE status = 'Paid') as monthly_data WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY created_at ASC";
    $rev_exp_result = $conn->query($rev_exp_query);
    $response['revenue_vs_expenses'] = ['labels' => [], 'revenue' => [], 'expenses' => []];
    while($row = $rev_exp_result->fetch_assoc()) {
        $response['revenue_vs_expenses']['labels'][] = $row['month'];
        $response['revenue_vs_expenses']['revenue'][] = (float)$row['revenue'];
        $response['revenue_vs_expenses']['expenses'][] = (float)$row['expenses'];
    }

    // Expense Breakdown for selected period
    $exp_brk_query = "SELECT expense_type, SUM(amount) as total FROM expenses WHERE status = 'Paid' AND created_at BETWEEN ? AND ? GROUP BY expense_type";
    $stmt = $conn->prepare($exp_brk_query);
    $stmt->bind_param("ss", $start_date, $end_date_for_query);
    $stmt->execute();
    $result = $stmt->get_result();
    $response['expense_breakdown'] = ['labels' => [], 'values' => []];
    while($row = $result->fetch_assoc()) {
        $response['expense_breakdown']['labels'][] = $row['expense_type'];
        $response['expense_breakdown']['values'][] = (float)$row['total'];
    }
    $stmt->close();
    
    // Top 5 Doctor Payouts for selected period
    $payout_q = "
        SELECT rd.doctor_name,
               SUM(COALESCE(dtp.payable_amount, t.default_payable_amount, 0)) AS total_payable
        FROM bills b
        JOIN bill_items bi ON b.id = bi.bill_id
        JOIN referral_doctors rd ON b.referral_doctor_id = rd.id
        JOIN tests t ON bi.test_id = t.id
        LEFT JOIN doctor_test_payables dtp ON rd.id = dtp.doctor_id AND bi.test_id = dtp.test_id
        WHERE b.referral_type = 'Doctor'
          AND b.payment_status = 'Paid'
          AND b.bill_status != 'Void'
          AND (b.discount = 0 OR b.discount_by = 'Center')
          AND b.created_at BETWEEN ? AND ?
        GROUP BY rd.id, rd.doctor_name
        HAVING total_payable > 0
        ORDER BY total_payable DESC
        LIMIT 5
    ";
    $stmt = $conn->prepare($payout_q);
    $stmt->bind_param("ss", $start_date, $end_date_for_query);
    $stmt->execute();
    $result = $stmt->get_result();
    $response['doctor_payouts'] = ['labels' => [], 'values' => []];
    while($row = $result->fetch_assoc()) {
        $response['doctor_payouts']['labels'][] = 'Dr. ' . $row['doctor_name'];
        $response['doctor_payouts']['values'][] = (float)$row['total_payable'];
    }
    $stmt->close();
    
    // Revenue by Payment Mode for selected period
    $pay_mode_query = "SELECT payment_mode, SUM(net_amount) as total FROM bills WHERE payment_status = 'Paid' AND bill_status != 'Void' AND created_at BETWEEN ? AND ? GROUP BY payment_mode";
    $stmt = $conn->prepare($pay_mode_query);
    $stmt->bind_param("ss", $start_date, $end_date_for_query);
    $stmt->execute();
    $result = $stmt->get_result();
    $response['payment_modes'] = ['labels' => [], 'values' => []];
    while($row = $result->fetch_assoc()) {
        $response['payment_modes']['labels'][] = $row['payment_mode'];
        $response['payment_modes']['values'][] = (float)$row['total'];
    }
    $stmt->close();

    echo json_encode($response);
    exit();
} elseif (isset($_GET['action']) && $_GET['action'] == 'getDoctorPayouts') {
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');
    $end_date_for_query = $end_date . ' 23:59:59';
    $doctor_id = isset($_GET['doctor_id']) && $_GET['doctor_id'] !== '' ? (int) $_GET['doctor_id'] : null;

    $params = [$start_date, $end_date_for_query];
    $types = 'ss';

    $doctor_filter_sql = '';
    if ($doctor_id) {
        $doctor_filter_sql = ' AND rd.id = ?';
        $params[] = $doctor_id;
        $types .= 'i';
    }

    $sql = "
        SELECT
            dbt.doctor_id,
            dbt.doctor_name,
            COUNT(dbt.bill_id) AS total_bills,
            SUM(dbt.total_tests) AS total_tests,
            SUM(dbt.total_payable) AS total_payable,
            SUM(dbt.total_payable_after_discount) AS payable_after_discount,
            SUM(dbt.discount_absorbed) AS doctor_discount_absorbed,
            SUM(dbt.gross_amount) AS total_gross_amount,
            SUM(dbt.discount_amount) AS total_discount_amount,
            SUM(dbt.net_amount) AS total_net_amount
        FROM (
            SELECT
                rd.id AS doctor_id,
                rd.doctor_name,
                b.id AS bill_id,
                COUNT(bi.id) AS total_tests,
                SUM(COALESCE(dtp.payable_amount, t.default_payable_amount, 0)) AS total_payable,
                CASE
                    WHEN b.discount_by = 'Doctor' THEN
                        GREATEST(SUM(COALESCE(dtp.payable_amount, t.default_payable_amount, 0)) - b.discount, 0)
                    ELSE
                        SUM(COALESCE(dtp.payable_amount, t.default_payable_amount, 0))
                END AS total_payable_after_discount,
                CASE
                    WHEN b.discount_by = 'Doctor' THEN
                        LEAST(b.discount, SUM(COALESCE(dtp.payable_amount, t.default_payable_amount, 0)))
                    ELSE 0
                END AS discount_absorbed,
                b.gross_amount,
                b.discount AS discount_amount,
                b.net_amount
            FROM bills b
            JOIN bill_items bi ON b.id = bi.bill_id
            JOIN tests t ON bi.test_id = t.id
            JOIN referral_doctors rd ON b.referral_doctor_id = rd.id
            LEFT JOIN doctor_test_payables dtp ON rd.id = dtp.doctor_id AND bi.test_id = dtp.test_id
            WHERE b.payment_status = 'Paid'
              AND b.bill_status != 'Void'
              AND b.referral_type = 'Doctor'
              AND b.created_at BETWEEN ? AND ?
              {$doctor_filter_sql}
            GROUP BY rd.id, rd.doctor_name, b.id, b.gross_amount, b.discount, b.discount_by, b.net_amount
        ) AS dbt
        GROUP BY dbt.doctor_id, dbt.doctor_name
        HAVING total_payable > 0 OR payable_after_discount > 0
        ORDER BY dbt.doctor_name
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare payouts query.']);
        exit();
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $response = [
        'filters' => [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'doctor_id' => $doctor_id,
        ],
        'payouts' => [],
    ];

    while ($row = $result->fetch_assoc()) {
        $response['payouts'][] = [
            'doctor_id' => (int) $row['doctor_id'],
            'doctor_name' => $row['doctor_name'],
            'total_bills' => (int) $row['total_bills'],
            'total_tests' => (int) $row['total_tests'],
            'total_payable' => (float) $row['total_payable'],
            'payable_after_discount' => (float) $row['payable_after_discount'],
            'discount_applied' => (float) $row['doctor_discount_absorbed'],
            'total_gross_amount' => (float) $row['total_gross_amount'],
            'total_discount_amount' => (float) $row['total_discount_amount'],
            'total_net_amount' => (float) $row['total_net_amount'],
        ];
    }

    $stmt->close();

    echo json_encode($response);
    exit();
}
?>