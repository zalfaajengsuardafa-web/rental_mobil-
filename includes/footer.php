</main>

<footer class="footer bg-dark text-white mt-auto py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2">
                    <i class="bi bi-car-front-fill text-primary fs-5"></i>
                    <span class="fw-bold"><?php echo APP_NAME; ?></span>
                </div>
                <small class="text-muted"><?php echo APP_TAGLINE; ?></small>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <small class="text-muted">
                    &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> &mdash;
                    UAS Praktikum Pemrograman Web 1
                </small>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo BASE_URL; ?>assets/js/app.js"></script>
</body>
</html>
