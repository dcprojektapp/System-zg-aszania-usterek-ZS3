<?php
require_once __DIR__ . '/../config/db.php';

try {
    $sql = file_get_contents(__DIR__ . '/../database.sql');
    $pdo->exec($sql);
    echo "Baza danych i tabele zostały pomyślnie utworzone.<br>";

    // Opcjonalne czyszczenie tabeli users przy użyciu parametru ?reset=1
    if (isset($_GET['reset']) && $_GET['reset'] == 1) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("TRUNCATE TABLE users");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        echo "Tabela użytkowników została wyczyszczona.<br>";
    }

    // Sprawdź czy kolumna email istnieje w users, jeśli nie - dodaj ją
    try {
        $pdo->query("SELECT email FROM users LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) NOT NULL UNIQUE AFTER name");
        $pdo->exec("ALTER TABLE users CHANGE COLUMN pin_hash password_hash VARCHAR(255) NOT NULL");
        echo "Zaktualizowano strukturę tabeli users (dodano email).<br>";
    }

    // Sprawdź czy kolumna reporter_name istnieje w tickets, jeśli nie - dodaj ją
    try {
        $pdo->query("SELECT reporter_name FROM tickets LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN reporter_name VARCHAR(100) NOT NULL AFTER user_id");
        echo "Zaktualizowano strukturę tabeli tickets (dodano reporter_name).<br>";
    }

    // Sprawdź czy kolumna is_archived istnieje w tickets, jeśli nie - dodaj ją
    try {
        $pdo->query("SELECT is_archived FROM tickets LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN is_archived TINYINT(1) DEFAULT 0 AFTER status");
        echo "Zaktualizowano strukturę tabeli tickets (dodano is_archived).<br>";
    }

    // Wyczyść tabelę users i dodaj tylko jednego użytkownika testowego
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE users");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    // Dodawanie przykładowych użytkowników
    // Hasło: nauczycielZS3
    $name = 'Nauczyciel ZS3';
    $email = 'nauczyciel@zs3.lukow.pl';
    $password = 'nauczycielZS3';
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $role = 'user'; // Domyślnie nauczyciel zgłasza usterki

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :pass, :role)");
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':pass' => $password_hash,
        ':role' => $role
    ]);

    // Dodatkowo Administrator dla testów (opcjonalnie, zakomentowane jeśli ma być TYLKO jeden)
    // admin@zs3.lukow.pl / AdminZS3
    $admin_pass = password_hash('AdminZS3', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO users (name, email, password_hash, role) VALUES ('Administrator', 'admin@zs3.lukow.pl', '$admin_pass', 'admin')");

    echo "Utworzono użytkownika: $email (hasło: $password)<br>";
    echo "Utworzono administratora: admin@zs3.lukow.pl (hasło: AdminZS3)<br>";


    echo "<a href='index.php'>Przejdź do strony głównej</a>";

} catch (PDOException $e) {
    die("Błąd instalacji: " . $e->getMessage());
}
?>