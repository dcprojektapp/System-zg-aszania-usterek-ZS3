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

// Pobierz aktywnych użytkowników do listy (niepotrzebne przy logowaniu emailem, ale zostawiam ew. do debugu lub usuwam całkowicie)
// $users = []; 

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
            $error = "Nieprawidłowy kod dostępu.";
        }
    } elseif ($email && $password) {
        try {
            // Zmiana zapytania: szukamy po emailu, sprawdzamy password_hash
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
        $error = "Podaj email i hasło.";
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
        }

        .login-card {
            width: 100%;
            max-width: 600px;
            padding: 2rem;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="card login-card mx-auto">
            <div class="card-body">
                <div class="brand-logo">System zgłaszania usterek<br>w Zespół Szkół nr 3<br>im. Władysława Stanisława
                    Reymonta</div>
                <h5 class="text-center mb-4 text-muted">Zaloguj się</h5>

                <?php if ($error): ?>
                    <div class="alert alert-danger mb-4 shadow-sm"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="mb-4">
                        <label for="email" class="form-label fw-bold">Adres Email</label>
                        <input type="email" class="form-control form-control-lg" id="email" name="email" required
                            placeholder="name@example.com"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="mb-5">
                        <label for="password" class="form-label fw-bold">Hasło</label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password"
                            required placeholder="••••••••">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Zaloguj się</button>
                    </div>
                </form>

                <div class="position-relative my-4">
                    <hr class="text-muted opacity-25">
                    <span class="position-absolute top-50 start-50 translate-middle px-3 bg-white text-muted small fw-bold">LUB</span>
                </div>

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label for="access_code" class="form-label fw-bold">Kod dostępu</label>
                        <input type="password" class="form-control form-control-lg" id="access_code" name="access_code" placeholder="Wpisz kod nauczyciela">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-primary btn-lg">Zaloguj kodem</button>
                    </div>
                </form>

                <div class="text-center mt-3 text-muted small">
                    <p>Domyślny login: nauczyciel@zs3.lukow.pl<br>Hasło: nauczycielZS3</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>