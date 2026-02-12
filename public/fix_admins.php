<?php
require_once __DIR__ . '/../config/db.php';

$admins = [
    ['name' => 'Dawid Chaber', 'email' => 'dchaber@zs3.lukow.pl', 'pass' => 'ZS3Lukow'],
    ['name' => 'Wojciech Zielonka', 'email' => 'wzielonka@zs3.lukow.pl', 'pass' => 'ZS3Lukow']
];

echo "<h3>Aktualizacja kont administratorów</h3>";

foreach ($admins as $admin) {
    try {
        $hash = password_hash($admin['pass'], PASSWORD_DEFAULT);

        // Sprawdź czy użytkownik istnieje
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $admin['email']]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Aktualizuj istniejące konto
            $update = $pdo->prepare("UPDATE users SET password_hash = :hash, name = :name, role = 'admin' WHERE id = :id");
            $update->execute([
                ':hash' => $hash,
                ':name' => $admin['name'],
                ':id' => $existing['id']
            ]);
            echo "Zaktualizowano hasło dla: <strong>{$admin['name']}</strong> ({$admin['email']})<br>";
        } else {
            // Dodaj nowe konto
            $insert = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :pass, 'admin')");
            $insert->execute([
                ':name' => $admin['name'],
                ':email' => $admin['email'],
                ':pass' => $hash
            ]);
            echo "Dodano administratora: <strong>{$admin['name']}</strong> ({$admin['email']})<br>";
        }
    } catch (PDOException $e) {
        echo "Błąd przy koncie {$admin['name']}: " . $e->getMessage() . "<br>";
    }
}

echo "<br><a href='index.php'>Wróć do strony głównej</a> | <a href='admin/settings.php'>Przejdź do ustawień</a>";
?>