    </div> <!-- End content-wrapper -->

    <?php if(isset($show_bottom_nav) && $show_bottom_nav && isset($_SESSION['role_id']) && $_SESSION['role_id'] == 6): ?>
    <!-- Bottom Navigation Placeholder -->
    <nav class="bottom-nav fixed-bottom">
        <div class="d-flex justify-content-around align-items-center h-100">
            <a href="index.php" class="nav-item text-center text-decoration-none text-white active">
                <i class="bi bi-house-door-fill fs-4"></i>
                <div class="small">Home</div>
            </a>
            <a href="wallet.php" class="nav-item text-center text-decoration-none text-secondary">
                <i class="bi bi-wallet2 fs-4"></i>
                <div class="small">Wallet</div>
            </a>
            <a href="logs.php" class="nav-item text-center text-decoration-none text-secondary">
                <i class="bi bi-clock-history fs-4"></i>
                <div class="small">History</div>
            </a>
            <a href="profile.php" class="nav-item text-center text-decoration-none text-secondary">
                <i class="bi bi-person-fill fs-4"></i>
                <div class="small">Profile</div>
            </a>
        </div>
    </nav>
    <?php endif; ?>

</div> <!-- End app-container -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="/assets/js/main.js"></script>
</body>
</html>
