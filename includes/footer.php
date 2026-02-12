</div>

<footer class="bg-white border-top py-4 mt-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                <p class="mb-0 text-muted small">
                    &copy;
                    <?php echo date('Y'); ?> Zespół Szkół nr 3 im. Władysława Stanisława Reymonta
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <div
                    class="d-flex align-items-center justify-content-center justify-content-md-end gap-2 text-muted small">
                    <span class="opacity-75">Created by:</span>
                    <a href="#"
                        class="d-flex align-items-center text-decoration-none text-dark fw-semibold transition-hover">
                        Dawid Chaber
                        <?php
                        $is_admin = strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false;
                        $img_path = ($is_admin ? '../../' : '../') . 'assets/img/dc_logo.png';
                        ?>
                        <img src="<?php echo $img_path; ?>" alt="Logo" height="30" class="ms-2"
                            style=" object-fit: contain;">
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>
<!-- jQuery (required for Select2) -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: $(this).data('placeholder'),
        });
    });
</script>
</body>

</html>