<?php
// Ten skrypt symuluje pełne dodanie zgłoszenia: INSERT do DB + Email + Telegram
echo "=== TEST INTEGRACYJNY SYSTEMU ===\n";

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/Mailer.php';
require_once __DIR__ . '/../includes/TelegramNotifier.php';

// Dane testowe
$reporter = "Tester Automatyczny";
$room = "Sala Testowa";
$issue = "Test integracji systemów";
$desc = "To jest automatyczny test sprawdzający poprawność zapisu do bazy oraz wysyłki powiadomień Email i Telegram.\nData: " . date('Y-m-d H:i:s');
$user_id = 999; // ID użytkownika (można użyć istniejącego lub symulowanego)

echo "[1/3] Zapisywanie zgłoszenia w bazie danych... ";
try {
    // 1. Dodaj zgłoszenie
    $stmt = $pdo->prepare("INSERT INTO tickets (user_id, reporter_name, room_number, issue_type, description, status, created_at) VALUES (:user_id, :reporter, :room, :issue, :desc, 'nowe', NOW())");
    $stmt->execute([
        ':user_id' => $user_id,
        ':reporter' => $reporter,
        ':room' => $room,
        ':issue' => $issue,
        ':desc' => $desc
    ]);
    $ticketId = $pdo->lastInsertId();
    echo "OK (ID: $ticketId)\n";

    // 2. Wyślij Email
    echo "[2/3] Wysyłanie powiadomienia Email... ";
    $mail_subject = "Nowe zgłoszenie #$ticketId: Sala $room ($issue)";
    $mail_body = "
        <h3>Nowe zgłoszenie usterki (TEST)</h3>
        <p><strong>Zgłaszający:</strong> " . htmlspecialchars($reporter) . "</p>
        <p><strong>Sala:</strong> " . htmlspecialchars($room) . "</p>
        <p><strong>Typ usterki:</strong> " . htmlspecialchars($issue) . "</p>
        <p><strong>Opis:</strong> " . nl2br(htmlspecialchars($desc)) . "</p>
        <p><small>To powiadomienie zostało wygenerowane w ramach testów wdrożeniowych.</small></p>
    ";

    // Logika Mailer::notifyAdmins wewnątrz testu dla pewności (z logowaniem wyniku)
    // Bezpośrednie użycie Mailer::send, aby przechwycić wynik boolean, bo notifyAdmins jest void
    $emailStmt = $pdo->query("SELECT email FROM users WHERE role = 'admin'");
    $admins = $emailStmt->fetchAll(PDO::FETCH_COLUMN);
    $emailSuccess = true;
    foreach ($admins as $email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if (!Mailer::send($email, "[Test] $mail_subject", $mail_body)) {
                $emailSuccess = false;
                echo "\n   -> Błąd wysyłania do $email";
            }
        }
    }

    if ($emailSuccess && count($admins) > 0) {
        echo "OK (Do: " . count($admins) . " adminów)\n";
    } elseif (count($admins) == 0) {
        echo "POMINIĘTO (Brak adminów w bazie)\n";
    } else {
        echo "BŁĄD (Sprawdź logi)\n";
    }

    // 3. Wyślij Telegram
    echo "[3/3] Wysyłanie powiadomienia Telegram... ";
    $telegram_message = "🆕 <b>Nowe zgłoszenie usterki (TEST)</b>\n\n" .
        "🕒 <b>Data:</b> " . date('Y-m-d H:i') . "\n" .
        "👤 <b>Zgłaszający:</b> " . htmlspecialchars($reporter) . "\n" .
        "📍 <b>Sala:</b> " . htmlspecialchars($room) . "\n" .
        "⚠️ <b>Usterka:</b> " . htmlspecialchars($issue) . "\n" .
        "📝 <b>Opis:</b> " . htmlspecialchars($desc) . "\n" .
        "🆔 <b>ID Zgłoszenia:</b> #$ticketId";

    if (TelegramNotifier::send($telegram_message)) {
        echo "OK\n";
    } else {
        echo "BŁĄD\n";
    }

    echo "\n=== PODSUMOWANIE TESTU ===\n";
    echo "Zgłoszenie #$ticketId zostało pomyślnie przetworzone.\n";
    echo "Sprawdź skrzynkę e-mail oraz kanał Telegram.\n";

    // Opcjonalnie: usuń rekord testowy
    // $pdo->exec("DELETE FROM tickets WHERE id = $ticketId");
    // echo "(Rekord testowy usunięty z bazy)\n";

} catch (PDOException $e) {
    echo "BŁĄD BAZY DANYCH: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "WYJĄTEK: " . $e->getMessage() . "\n";
}
?>