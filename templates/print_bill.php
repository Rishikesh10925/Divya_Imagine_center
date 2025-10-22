<?php
require_once '../includes/db_connect.php'; // Make sure this path is correct

// --- Get Bill ID ---
if (!isset($_GET['bill_id']) || !is_numeric($_GET['bill_id'])) {
    die("Invalid Bill ID provided.");
}
$bill_id = (int)$_GET['bill_id'];

// --- Fetch Bill Details ---
$stmt = $conn->prepare(
    "SELECT b.id as invoice_number, b.gross_amount, b.discount, b.net_amount, b.created_at,
           p.name as patient_name, p.age, p.sex, p.address, p.city, p.mobile_number,
           u.username as receptionist_name,
           rd.doctor_name as referral_doctor_name
     FROM bills b
     JOIN patients p ON b.patient_id = p.id
     JOIN users u ON b.receptionist_id = u.id
     LEFT JOIN referral_doctors rd ON b.referral_doctor_id = rd.id
     WHERE b.id = ?"
);
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$bill_result = $stmt->get_result();

if ($bill_result->num_rows === 0) {
    die("Bill not found for the given ID.");
}
$bill = $bill_result->fetch_assoc();
$stmt->close();

// --- Fetch Bill Items ---
$items_stmt = $conn->prepare(
    "SELECT t.main_test_name, t.sub_test_name, t.price
     FROM bill_items bi
     JOIN tests t ON bi.test_id = t.id
     WHERE bi.bill_id = ? AND bi.item_status = 0"
);
$items_stmt->bind_param("i", $bill_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$bill_items = $items_result->fetch_all(MYSQLI_ASSOC);
$items_stmt->close();

// --- Function to convert number to words ---
function numberToWords(float $number) {
    // (Keep the same numberToWords function as before)
    if ($number == 0) { return 'Zero'; }
    $num = floor($number);
    $decimal = round($number - $num, 2) * 100;
    $words = [];
    $lookup = [0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'];
    // Use a nested function accessible only within numberToWords
    $convertLessThanHundred = function($n, $lookup) { if ($n == 0) return ''; if ($n < 20) { return $lookup[$n]; } else { $tens = floor($n / 10) * 10; $units = $n % 10; return $lookup[$tens] . ($units > 0 ? ' ' . $lookup[$units] : ''); } };
    if ($num >= 10000000) { $crores = floor($num / 10000000); $words[] = $convertLessThanHundred($crores, $lookup) . ' Crore'; $num %= 10000000; }
    if ($num >= 100000) { $lakhs = floor($num / 100000); $words[] = $convertLessThanHundred($lakhs, $lookup) . ' Lakh'; $num %= 100000; }
    if ($num >= 1000) { $thousands = floor($num / 1000); $words[] = $convertLessThanHundred($thousands, $lookup) . ' Thousand'; $num %= 1000; }
    if ($num >= 100) { $hundreds = floor($num / 100); $words[] = $convertLessThanHundred($hundreds, $lookup) . ' Hundred'; $num %= 100; }
    if ($num > 0) { if (!empty($words) && $num > 0) { $words[] = 'and'; } $words[] = $convertLessThanHundred($num, $lookup); }
    $rupees = trim(implode(' ', $words));
    $paise_words = ''; if ($decimal > 0) { $paise_words = $convertLessThanHundred($decimal, $lookup) . ' Paise'; }
    if ($rupees != '' && $paise_words != '') { return $rupees . ' Rupees and ' . $paise_words; } elseif ($rupees != '') { return $rupees . ' Rupees'; } elseif ($paise_words != '') { return $paise_words; } else { return 'Zero Rupees'; }
}


$amount_in_words = numberToWords($bill['net_amount']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bill Receipt (A5) - #<?php echo htmlspecialchars($bill['invoice_number']); ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" />
    <style>
        :root {
            --default-font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Ubuntu, "Helvetica Neue", Helvetica, Arial, "PingFang SC", "Hiragino Sans GB", "Microsoft Yahei UI", "Microsoft Yahei", "Source Han Sans CN", sans-serif;
        }

        body {
            background: #ccc;
            margin: 0;
            padding: 0;
            font-family: var(--default-font-family);
        }

        .page-wrapper { /* Added wrapper for potential centering or multiple pages later */
             padding: 10mm 0; /* Add padding top/bottom on screen */
        }

        .main-container {
            position: relative;
            /* --- A5 Dimensions --- */
            width: 148mm;
            height: 205mm; /* Slightly less than 210mm for margin */
            /* --- End A5 --- */
            margin: 0 auto; /* Center on screen */
            background: #ffffff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            padding: 8mm; /* A5 padding */
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }

        .bill-header {
            display: flex;
            align-items: center;
            border: 1.5px solid #000;
            border-radius: 8px;
            padding: 8px 12px;
            margin-bottom: 8px;
        }

        .logo {
            margin-right: 12px;
            flex-shrink: 0;
        }
         .logo img {
            width: 50px;
            height: 50px;
            border-radius: 6px;
            display: block;
        }

        .center-name {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 1px;
            text-align: center;
            flex-grow: 1;
            line-height: 1.1;
            color: #000;
            font-family: Inter, var(--default-font-family);
        }

        .bill-receipt {
          display: block;
          position: relative;
          height: 12px;
          margin: 10px auto 10px auto;
          color: #000000;
          font-family: Inter, var(--default-font-family);
          font-size: 10px;
          font-weight: 700;
          line-height: 12px;
          text-align: center;
          white-space: nowrap;
          z-index: 19;
        }

        .info-row {
            position: relative;
            width: 100%;
            height: 12px;
            margin-bottom: 5px;
            font-size: 8px;
            line-height: 12px;
            color: #000000;
            font-family: Inter, var(--default-font-family);
        }
        .info-row span {
             position: absolute;
             top: 0;
             height: 100%;
             white-space: nowrap;
             overflow: hidden;
             text-overflow: ellipsis;
        }
        .info-row .label { font-weight: 700; }
        .info-row .colon { font-weight: 700; text-align: center; width: 5px;}
        .info-row .value { font-weight: 500; padding-left: 3px;}

        .info-row .left-label { left: 0; min-width: 60px; }
        .info-row .left-colon { left: 60px; }
        .info-row .left-value { left: 68px; width: calc(50% - 75px); }

        .info-row .right-label { left: 55%; min-width: 45px;}
        .info-row .right-colon { left: calc(55% + 45px); }
        .info-row .right-value { left: calc(55% + 53px); width: calc(45% - 53px); }


        .items-area {
            margin-top: 10px;
            margin-bottom: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 5px 0;
            flex-grow: 1; /* Allow items area to fill space */
            min-height: 70mm; /* Adjust min height */
            font-size: 8px;
            font-family: Inter, var(--default-font-family);
            line-height: 1.3;
             position: relative;
        }
        .items-area .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.07;
            z-index: 0;
            width: 120px;
        }
        .items-area .watermark img {
            width: 100%;
            height: auto;
            display: block;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
             position: relative;
             z-index: 1;
        }
        .items-table th, .items-table td {
            padding: 3px 2px;
            vertical-align: top;
        }
        .items-table th { font-weight: 700; text-align: left;}
        .items-table td { font-weight: 500;}
        .items-table .sno { width: 8%; text-align: center;}
        .items-table .item-name { width: 67%;}
        .items-table .amount { width: 25%; text-align: right;}
        .items-table thead tr { border-bottom: 1px solid #000;}
        .items-table tbody tr td { border-bottom: 0.8px dotted #ccc; }
        .items-table tbody tr:last-child td { border-bottom: none; }
        /* Empty row style */
        .items-table tr.empty-row td { height: 16px; border-bottom: 0.8px dotted #ccc; }
        .items-table tr.empty-row:last-child td { border-bottom: none; }


        .footer-section {
            /* Removed absolute positioning, let it flow naturally */
            margin-top: 10px; /* Space above footer */
            width: 100%;
            height: auto;
            overflow: hidden; /* Clear floats */
             padding-top: 8px;
             border-top: 1px solid #ccc;
        }

        .totals-section {
            float: right;
            width: 40%;
            font-size: 8.5px;
            line-height: 1.4;
            font-family: Inter, var(--default-font-family);
            color: #000;
             position: relative; /* For signature positioning */
             padding-bottom: 25px; /* Space for signature */
        }
         .totals-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
         }
        .totals-row span { white-space: nowrap; }
        .totals-row .label { font-weight: 500; padding-right: 5px; text-align: left;}
        .totals-row .colon { font-weight: 700; }
        .totals-row .value { font-weight: 700; text-align: right; min-width: 50px;}
        .totals-row.grand-total {
            font-weight: 700;
            font-size: 9px;
            border-top: 1px solid #000;
            padding-top: 3px;
            margin-top: 3px;
        }
        .totals-row.grand-total .value { font-weight: 700; }


        .footer-left {
            float: left;
            width: 58%;
            font-size: 7.5px;
            font-family: Inter, var(--default-font-family);
            color: #000;
            line-height: 1.2;
        }
        .amount-in-words {
            font-weight: 700;
            font-size: 8px;
            margin-bottom: 5px;
        }
        .dispute-text {
            font-weight: 700;
            margin-bottom: 5px;
        }
        .address-info {
            font-weight: 400;
        }
        .address-info br {
            content: "";
            display: block;
            margin-bottom: 0.5px;
        }

        .auth-signature {
            position: absolute;
            bottom: 0; /* Position at bottom of totals section */
            right: 0;
            font-size: 8px;
            font-weight: 700;
            white-space: nowrap;
            margin-top: 15px; /* Space above */
        }

        .print-button-container {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 100;
        }
         .print-button-container button, .print-button-container a {
             padding: 8px 15px;
             font-size: 14px;
             cursor: pointer;
             margin: 0 5px;
             text-decoration: none;
             border: 1px solid #ccc;
             background-color: #f0f0f0;
             border-radius: 4px;
         }
         .print-button-container a {
             color: #333;
         }


        /* --- Print Styles --- */
        @media print {
            body { margin: 0; background: #fff; font-size: 7.5pt !important; /* Base print font */ }
            @page {
                size: A5;
                margin: 8mm; /* Adjust print margins */
            }
            .page-wrapper { padding: 0;} /* Remove wrapper padding */
            .main-container {
                width: 100%;
                height: 100%; /* Try to fill the A5 area */
                min-height: initial; /* Override screen min-height */
                margin: 0;
                padding: 0; /* Remove padding */
                border: none;
                box-shadow: none;
                display: block; /* Override flex if causing issues */
            }
            .print-button-container { display: none; }

             /* Fine-tune print sizes and spacing */
            .bill-header { padding: 5px 8px; margin-bottom: 5px; border-width: 1px;}
            .logo img { width: 40px; height: 40px;}
            .center-name { font-size: 16pt !important; }
            .bill-receipt { font-size: 9pt !important; margin: 5px auto 8px auto;}
            .info-row { font-size: 7pt !important; height: 10px; line-height: 10px; margin-bottom: 3px;}
            .items-area { font-size: 7.5pt !important; margin-top: 8px; min-height: initial !important; /* Let content dictate height in print */ flex-grow: 0 !important; }
            .items-table { font-size: 7.5pt !important; }
            .items-table th, .items-table td { padding: 2px 2px !important;}
            .items-table tbody tr td { border-bottom: 0.5px dotted #ccc !important;}
            .items-table tr.empty-row td { height: 14px !important; border-bottom: 0.5px dotted #ccc !important;}
            .items-table tr.empty-row:last-child td { border-bottom: none !important;}

            .footer-section { padding-top: 5px !important; margin-top: 5px !important; position: relative !important; bottom: auto !important; left: auto !important; right: auto !important; } /* Make footer flow */
            .footer-left { font-size: 6.5pt !important; width: 60% !important;}
            .totals-section { font-size: 7.5pt !important; width: 38% !important; padding-bottom: 15px !important;}
            .amount-in-words { font-size: 7.5pt !important;}
            .totals-table { font-size: 7.5pt !important;}
            .grand-total .label, .grand-total .value {font-size: 8pt !important;}
            .auth-signature { font-size: 7pt !important; bottom: 0 !important; margin-top: 10px !important;} /* Adjust signature position */

            .items-area .watermark { opacity: 0.07 !important; print-color-adjust: exact !important; -webkit-print-color-adjust: exact !important; }
        }
        /* --- End Print Styles --- */

    </style>
</head>
<body onload="window.print()">
    <div class="page-wrapper">
       <div class="main-container">

            <div class="bill-header">
                <div class="logo">
                    <img src="../assets/images/logo.jpg" alt="Logo"> </div>
                <div class="center-name">DIVYA IMAGING CENTER</div>
            </div>

            <span class="bill-receipt">BILL RECEIPT</span>

          <div class="info-row">
            <span class="label left-label">BILL NO</span>
            <span class="colon left-colon">:</span>
            <span class="value left-value"><?php echo htmlspecialchars($bill['invoice_number']); ?></span>
            <span class="label right-label">BILL DATE</span>
            <span class="colon right-colon">:</span>
            <span class="value right-value"><?php echo date('d-m-Y', strtotime($bill['created_at'])); ?></span>
          </div>
          <div class="info-row">
            <span class="label left-label">Patient Name</span>
            <span class="colon left-colon">:</span>
            <span class="value left-value"><?php echo htmlspecialchars($bill['patient_name']); ?></span>
            <span class="label right-label">Mobile No</span>
            <span class="colon right-colon">:</span>
            <span class="value right-value"><?php echo htmlspecialchars($bill['mobile_number'] ?? ''); ?></span>
          </div>
          <div class="info-row">
            <span class="label left-label">Age & Gender</span>
            <span class="colon left-colon">:</span>
            <span class="value left-value"><?php echo htmlspecialchars($bill['age']); ?> / <?php echo htmlspecialchars($bill['sex']); ?></span>
            <span class="label right-label">City</span>
            <span class="colon right-colon">:</span>
            <span class="value right-value"><?php echo htmlspecialchars($bill['city'] ?? ''); ?></span>
          </div>
          <div class="info-row">
            <span class="label left-label">Ref. Physician</span>
            <span class="colon left-colon">:</span>
            <span class="value left-value" style="width: calc(100% - 70px);"><?php echo $bill['referral_doctor_name'] ? 'Dr. ' . htmlspecialchars($bill['referral_doctor_name']) : 'Self'; ?></span>
          </div>

          <div class="items-area">
                <div class="watermark">
                     <img src="../assets/images/logo.jpg" alt="Watermark"> </div>
               <table class="items-table">
                   <thead>
                       <tr>
                           <th class="sno">S.No</th>
                           <th class="item-name">Investigation Name</th>
                           <th class="amount">Amount</th>
                       </tr>
                   </thead>
                   <tbody>
                       <?php
                       $sno = 1;
                       $min_rows = 12; // Adjusted minimum rows for A5 item section
                       foreach ($bill_items as $item):
                       ?>
                       <tr>
                           <td class="sno"><?php echo $sno++; ?></td>
                           <td class="item-name">
                               <?php
                                   echo htmlspecialchars($item['main_test_name']);
                                   if (!empty($item['sub_test_name'])) {
                                       echo " - " . htmlspecialchars($item['sub_test_name']);
                                   }
                               ?>
                           </td>
                           <td class="amount"><?php echo number_format($item['price'], 2); ?></td>
                       </tr>
                       <?php endforeach; ?>
                       <?php
                       // Add empty rows
                       $empty_rows_count = $min_rows - count($bill_items);
                       if ($empty_rows_count > 0) {
                           for ($i = 0; $i < $empty_rows_count; $i++) {
                               echo '<tr class="empty-row"><td class="sno">&nbsp;</td><td class="item-name"></td><td class="amount"></td></tr>';
                           }
                       }
                       ?>
                   </tbody>
               </table>
           </div>
          <div class="footer-section">
              <div class="totals-section">
                   <div class="totals-row">
                       <span class="label">Sub Total</span>
                       <span class="colon">:</span>
                       <span class="value"><?php echo number_format($bill['gross_amount'], 2); ?></span>
                   </div>
                   <div class="totals-row">
                       <span class="label">Disc Amt</span>
                       <span class="colon">:</span>
                       <span class="value"><?php echo number_format($bill['discount'], 2); ?></span>
                   </div>
                   <div class="totals-row grand-total">
                       <span class="label">TOTAL</span>
                       <span class="colon">:</span>
                       <span class="value"><?php echo number_format($bill['net_amount'], 2); ?></span>
                   </div>
                    <span class="auth-signature">Authorised signature</span>
              </div>

              <div class="footer-left">
                  <div class="amount-in-words">(Rs <?php echo ucwords($amount_in_words); ?> Only)</div>
                  <div class="dispute-text">All Disputes are subject to Vijayawada Jurisdiction Only. E.&O.E</div>
                  <div class="address-info">
                    #57-7-3, Kakatheeya Road, Near Sonovision, Patamata, Vijayawada.<br />MRI/CT:
                    63091 02019, Ultrasound: 88836 89689, Reception & X-Ray: 91171 22022,<br />Laboratory
                    Services: 83281 81932
                  </div>
              </div>


          </div>


        </div> </div> <div class="print-button-container">
        <button onclick="window.print()">Print Bill (A5)</button>
        <a href="bill_history.php">Back to History</a>
    </div>

</body>
</html>    