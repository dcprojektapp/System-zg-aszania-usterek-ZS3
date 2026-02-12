<?php

class TelegramNotifier
{
    private static $logFile = __DIR__ . '/../logs/telegram.log';

    public static function log($message, $status = 'INFO')
    {
        $date = date('Y-m-d H:i:s');
        // Skróć wiadomość do logów
        $preview = str_replace(["\r", "\n"], ' ', substr(strip_tags($message), 0, 100));
        $logEntry = "[$date] [$status] MSG: $preview...\n";
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND);
    }

    public static function send($message)
    {
        // Sprawdź czy plik konfiguracyjny istnieje
        if (!file_exists(__DIR__ . '/../config/telegram_config.php')) {
            self::log("Brak pliku konfiguracyjnego telegram_config.php", "ERROR");
            return false;
        }

        $config = require __DIR__ . '/../config/telegram_config.php';

        $token = $config['bot_token'] ?? '';
        $chatId = $config['chat_id'] ?? '';

        if (empty($token) || empty($chatId)) {
            // Cicha porażka jeśli nie skonfigurowano, żeby nie spamować logów błędami przy braku chęci używania
            return false;
        }

        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        // Dane do wysłania
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
                'ignore_errors' => true // Ważne: pozwala odczytać treść błędu z API (np. 400 Bad Request)
            ],
            // Obejście problemów z certyfikatami SSL w XAMPP (opcjonalne, ale bezpieczne dla środowisk lokalnych)
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ],
        ];

        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            self::log("Błąd połączenia z API Telegrama (file_get_contents failed)", "ERROR");
            return false;
        }

        $response = json_decode($result, true);

        if (isset($response['ok']) && $response['ok']) {
            self::log($message, "SENT");
            return true;
        } else {
            $errorDesc = $response['description'] ?? 'Nieznany błąd API';
            $errorCode = $response['error_code'] ?? '---';
            self::log("API Error ($errorCode): $errorDesc", "ERROR");
            return false;
        }
    }
}
?>