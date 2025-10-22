<footer class="main-footer">
        <p>&copy; <?php echo date("Y"); ?> STME NMIMS, Hyderabad. All Rights Reserved.</p>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <script src="/diagnostic-center/assets/js/superadmin_final.js"></script>
    <?php else: ?>
    <script src="/diagnostic-center/assets/js/main.js"></script>
    <?php endif; ?>

</body>
</html>
