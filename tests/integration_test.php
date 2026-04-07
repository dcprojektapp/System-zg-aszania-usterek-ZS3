<?php
// Ten skrypt symuluje pełne dodanie zgłoszenia: INSERT do DB + Telegram
echo "=== TEST INTEGRACYJNY SYSTEMU ===\n";

require_once __DIR__ . '/../config/db.php';
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

    // 2. Wyślij Telegram
    echo "[2/2] Wysyłanie powiadomienia Telegram... ";
    $telegram_message = "🆕 Nowe zgłoszenie usterki (TEST)\n\n" .
        "🕒 Data: " . date('Y-m-d H:i') . "\n" .
        "👤 Zgłaszający: " . htmlspecialchars($reporter) . "\n" .
        "📍 Sala: " . htmlspecialchars($room) . "\n" .
        "⚠️ Usterka: " . htmlspecialchars($issue) . "\n" .
        "📝 Opis: " . htmlspecialchars($desc) . "\n\n" .
        "🔗 Przejdź do panelu";

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
