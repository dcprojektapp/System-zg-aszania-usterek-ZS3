<?php
require_once __DIR__ . '/../config/db.php';
session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (isset($_POST['access_code'])) {
        $access_code = trim($_POST['access_code']);
        if ($access_code === 'nauczycielZS3') {
            $stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE email = :email");
            $stmt->execute([':email' => 'nauczyciel@zs3.lukow.pl']);
            $user = $stmt->fetch();

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                header("Location: index.php");
                exit;
            } else {
                $error = "Konto nauczyciela nie zostało znalezione.";
            }
        } else {
            $error = "Nieprawidłowe hasło dostępowe.";
        }
    } elseif ($email && $password) {
        try {
            $stmt = $pdo->prepare("SELECT id, name, password_hash, role FROM users WHERE email = :email AND is_active = 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                $error = "Błędny email lub hasło.";
            }
        } catch (PDOException $e) {
            $error = "Błąd logowania: " . $e->getMessage();
        }
    } else {
        $error = "Podaj prawidłowe dane logowania.";
    }
}
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie - System Zgłaszania Usterek</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">

    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .login-card {
            width: 100%;
            max-width: 600px;
            padding: 2rem;
        }

        .hidden {
            display: none !important;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="card login-card mx-auto">
            <div class="card-body">
                <div class="brand-logo mb-4">System zgłaszania usterek<br>w Zespół Szkół nr 3<br>im. Władysława Stanisława Reymonta</div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger mb-4 shadow-sm"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Opcje wyboru -->
                <div id="selection-buttons">
                    <h5 class="text-center mb-4 text-muted">Wybierz sposób logowania</h5>
                    <div class="d-grid gap-3">
                        <button class="btn btn-outline-primary btn-lg" onclick="showTeacherForm()">Zaloguj się jako nauczyciel</button>
                        <button class="btn btn-outline-secondary btn-lg" onclick="showAdminForm()">Zaloguj się jako administrator</button>
                    </div>
                </div>

                <!-- Formularz logowania dla Nauczyciela -->
                <div id="teacher-form" class="hidden">
                    <h5 class="text-center mb-4 text-primary">Nauczyciel - hasło dostępowe</h5>
                    <form method="POST" action="login.php">
                        <div class="mb-4">
                            <label for="access_code" class="form-label fw-bold">Hasło dostępowe</label>
                            <input type="password" class="form-control form-control-lg" id="access_code" name="access_code" placeholder="Wpisz hasło" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Zaloguj się</button>
                            <button type="button" class="btn btn-link text-muted" onclick="showSelection()">Wróć do wyboru</button>
                        </div>
                    </form>
                </div>

                <!-- Formularz logowania dla Administratora -->
                <div id="admin-form" class="hidden">
                    <h5 class="text-center mb-4 text-secondary">Zaloguj się jako administrator</h5>
                    <form method="POST" action="login.php">
                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">Adres Email</label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" required placeholder="name@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label fw-bold">Hasło</label>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" required placeholder="••••••••">
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-secondary btn-lg">Zaloguj się</button>
                            <button type="button" class="btn btn-link text-muted" onclick="showSelection()">Wróć do wyboru</button>
                        </div>
                    </form>
                </div>



            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showTeacherForm() {
            document.getElementById('selection-buttons').classList.add('hidden');
            document.getElementById('teacher-form').classList.remove('hidden');
            document.getElementById('admin-form').classList.add('hidden');
        }

        function showAdminForm() {
            document.getElementById('selection-buttons').classList.add('hidden');
            document.getElementById('admin-form').classList.remove('hidden');
            document.getElementById('teacher-form').classList.add('hidden');
        }

        function showSelection() {
            document.getElementById('selection-buttons').classList.remove('hidden');
            document.getElementById('teacher-form').classList.add('hidden');
            document.getElementById('admin-form').classList.add('hidden');
        }

        // Gdy wystąpi błąd - przywróć odpowiedni formularz na podstawie danych POST
        <?php if ($error): ?>
            <?php if (isset($_POST['access_code'])): ?>
                showTeacherForm();
            <?php elseif (isset($_POST['email'])): ?>
                showAdminForm();
            <?php endif; ?>
        <?php endif; ?>
    </script>
</body>

</html>