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
    } elseif (isset($_POST['school_login'])) {
        // --- KONFIGURACJA SIECI SZKOLNEJ ---
        // Lista IP, które mają dostęp bezwarunkowy (np. stały publiczny IP szkoły)
        $allowed_ips = ['31.41.80.186'];

        // Opcjonalnie: Jeśli masz stały publiczny IP szkoły, dodaj go tutaj:
        // $allowed_ips[] = '83.12.34.56';

        $user_ip = $_SERVER['REMOTE_ADDR'];
        $access_granted = false;

        foreach ($allowed_ips as $allowed) {
            // Sprawdź czy IP użytkownika zaczyna się od dozwolonego ciągu
            if (strpos($user_ip, $allowed) === 0) {
                $access_granted = true;
                break;
            }
        }

        // --- SPRAWDZANIE NAZWY KOMPUTERA (HOSTNAME) ---
        // Próba pobrania nazwy domenowej komputera
        $hostname = gethostbyaddr($user_ip);
        if ($hostname === false) { 
            $hostname = $user_ip; 
        }

        // Warunek: nazwa zawiera "k420" ORAZ "Pracownia" + cyfry 01-20
        // Przykład pasujący: k420-Pracownia05.zs3.lukow.pl
        // Regex: /k420.*Pracownia(0[1-9]|1[0-9]|20)/i
        if (!$access_granted) {
            if (preg_match('/k420.*Pracownia(0[1-9]|1[0-9]|20)/i', $hostname)) {
                $access_granted = true;
            }
        }

        if ($access_granted) {
            if ($hostname === $user_ip) {
                // Jeśli nie udało się rozwiązać nazwy, a dostęp przyznano po IP
                $hostname = "Komputer Szkolny ($user_ip)";
            }

            // Logowanie automatyczne na konto Nauczyciel
            $stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE email = :email AND is_active = 1");
            $stmt->execute([':email' => 'nauczyciel@zs3.lukow.pl']);
            $user = $stmt->fetch();

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                // Nadpisujemy nazwę użytkownika w sesji, żeby w zgłoszeniu było widać skąd kliknięto
                $_SESSION['user_name'] = $hostname;
                $_SESSION['user_role'] = $user['role'];
                header("Location: index.php");
                exit;
            } else {
                $error = "Konto 'nauczyciel@zs3.lukow.pl' nie istnieje w bazie. Utwórz je najpierw.";
            }
        } else {
            $error = "Odmowa dostępu. Twój adres IP ($user_ip) nie znajduje się w zaufanej sieci szkolnej.";
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
                    <span
                        class="position-absolute top-50 start-50 translate-middle px-3 bg-white text-muted small fw-bold">LUB</span>
                </div>

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label for="access_code" class="form-label fw-bold">Kod dostępu</label>
                        <input type="password" class="form-control form-control-lg" id="access_code" name="access_code"
                            placeholder="Wpisz kod nauczyciela">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-primary btn-lg">Zaloguj kodem</button>
                    </div>
                </form>

                <div class="position-relative my-4">
                    <hr class="text-muted opacity-25">
                    <span
                        class="position-absolute top-50 start-50 translate-middle px-3 bg-white text-muted small fw-bold">SIEĆ
                        SZKOLNA</span>
                </div>

                <form method="POST" action="login.php">
                    <div class="d-grid gap-2">
                        <button type="submit" name="school_login" value="1" class="btn btn-success btn-lg shadow-sm">
                            <i class="bi bi-building"></i> 🏫 Wejście z sieci ZS3
                        </button>
                    </div>
                </form>
                <div class="text-center mt-3 text-muted small opacity-75">
                    <i class="bi bi-broadcast"></i> Twój IP: <?php echo $_SERVER['REMOTE_ADDR']; ?>
                </div>


            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>