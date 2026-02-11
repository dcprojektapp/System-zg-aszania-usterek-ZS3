<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Zgłaszania Usterek</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
    <!-- Custom CSS -->
    <link
        href="<?php echo strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false ? '../../' : '../'; ?>assets/css/style.css"
        rel="stylesheet">
</head>

<body>
    <?php
    $is_admin_page = strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false;
    $path_prefix = $is_admin_page ? '../' : '';
    ?>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $path_prefix; ?>index.php">System Zgłaszania Usterek w ZS nr 3 im.
                W.S. Reymonta</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $path_prefix; ?>index.php">Zgłoś Usterkę</a>
                        </li>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $path_prefix; ?>admin/dashboard.php">Panel Admina</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $path_prefix; ?>admin/reports.php">Raporty</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <div class="d-flex">
                        <span class="navbar-text me-3 text-muted fw-bold">
                            Witaj, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </span>
                        <a href="<?php echo $path_prefix; ?>logout.php" class="btn btn-outline-primary btn-sm">Wyloguj</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="container">