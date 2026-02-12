<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>window.location.href = '../login.php';</script>";
    exit;
}

$password_msg = '';
$password_error = '';
$admin_msg = '';
$admin_error = '';

// Obsługa formularzy
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Zmiana hasła
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($current_password && $new_password && $confirm_password) {
            if ($new_password === $confirm_password) {
                try {
                    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id");
                    $stmt->execute([':id' => $_SESSION['user_id']]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($current_password, $user['password_hash'])) {
                        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
                        $updateStmt->execute([':hash' => $new_hash, ':id' => $_SESSION['user_id']]);
                        $password_msg = "Hasło zostało pomyślnie zmienione.";
                    } else {
                        $password_error = "Obecne hasło jest nieprawidłowe.";
                    }
                } catch (PDOException $e) {
                    $password_error = "Wystąpił błąd: " . $e->getMessage();
                }
            } else {
                $password_error = "Nowe hasła nie są identyczne.";
            }
        } else {
            $password_error = "Wypełnij wszystkie pola.";
        }
    }

    // Dodawanie administratora
    if ($action === 'add_admin') {
        $new_name = trim($_POST['admin_name'] ?? '');
        $new_email = trim($_POST['admin_email'] ?? '');
        $new_pass = $_POST['admin_password'] ?? '';

        if ($new_name && $new_email && $new_pass) {
            try {
                // Sprawdź czy taki email już istnieje
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
                $stmt->execute([':email' => $new_email]);
                if ($stmt->fetch()) {
                    $admin_error = "Użytkownik o podanym adresie email już istnieje.";
                } else {
                    $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, 'admin')");
                    $stmt->execute([':name' => $new_name, ':email' => $new_email, ':hash' => $hash]);
                    $admin_msg = "Dodano nowego administratora: " . htmlspecialchars($new_name);
                }
            } catch (PDOException $e) {
                $admin_error = "Błąd bazy danych: " . $e->getMessage();
            }
        } else {
            $admin_error = "Wypełnij wszystkie pola nowego administratora.";
        }
    }
}

// Pobierz listę administratorów do wyświetlenia
try {
    $admins = $pdo->query("SELECT id, name, email FROM users WHERE role = 'admin' ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    $admins = [];
}
?>

<div class="row g-4">
    <!-- Karta Zmiany Hasła -->
    <div class="col-md-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold mb-4">🔐 Twoje Hasło</h5>

                <?php if ($password_msg): ?>
                    <div class="alert alert-success border-0 shadow-sm"><?php echo $password_msg; ?></div>
                <?php endif; ?>
                <?php if ($password_error): ?>
                    <div class="alert alert-danger border-0 shadow-sm"><?php echo $password_error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Obecne hasło</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nowe hasło</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                    </div>
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Potwierdź nowe hasło</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Zmień hasło</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Karta Zarządzania Administratorami -->
    <div class="col-md-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold mb-4">👥 Zarządzanie Administratorami</h5>

                <?php if ($admin_msg): ?>
                    <div class="alert alert-success border-0 shadow-sm"><?php echo $admin_msg; ?></div>
                <?php endif; ?>
                <?php if ($admin_error): ?>
                    <div class="alert alert-danger border-0 shadow-sm"><?php echo $admin_error; ?></div>
                <?php endif; ?>

                <!-- Formularz dodawania -->
                <div class="bg-light p-3 rounded mb-4 border">
                    <h6 class="fw-bold mb-3">Dodaj nowego administratora</h6>
                    <form method="POST" class="row g-2">
                        <input type="hidden" name="action" value="add_admin">
                        <div class="col-md-6">
                            <input type="text" class="form-control form-control-sm" name="admin_name" placeholder="Imię i Nazwisko" required>
                        </div>
                        <div class="col-md-6">
                            <input type="email" class="form-control form-control-sm" name="admin_email" placeholder="Email" required>
                        </div>
                        <div class="col-md-12">
                            <input type="password" class="form-control form-control-sm" name="admin_password" placeholder="Hasło" required minlength="6">
                        </div>
                         <div class="col-12 text-end mt-2">
                            <button type="submit" class="btn btn-sm btn-success">
                                <span class="me-1">➕</span> Dodaj Administratora
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Lista administratorów -->
                <h6 class="fw-bold mb-3 text-muted small text-uppercase">Lista kont administracyjnych</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nazwa</th>
                                <th>Email</th>
                                <th class="text-end">ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td class="fw-semibold">
                                        <?php echo htmlspecialchars($admin['name']); ?>
                                        <?php if ($admin['id'] == $_SESSION['user_id']): ?>
                                            <span class="badge bg-primary ms-1" style="font-size: 0.65rem;">TY</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td class="text-end small text-muted">#<?php echo $admin['id']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>