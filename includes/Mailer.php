<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Załaduj autoloadera Composera
require __DIR__ . '/../vendor/autoload.php';

class Mailer
{
    private static $logFile = __DIR__ . '/../logs/emails.log';

    public static function log($to, $subject, $message, $status = 'SENT')
    {
        $date = date('Y-m-d H:i:s');
        $preview = str_replace(["\r", "\n"], ' ', substr(strip_tags($message), 0, 100));
        $logEntry = "[$date] [$status] TO: $to | SUBJECT: $subject | MSG: $preview...\n";
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND);
    }

    public static function send($to, $subject, $message)
    {
        $config = [];
        if (file_exists(__DIR__ . '/../config/mail_config.php')) {
            $config = require __DIR__ . '/../config/mail_config.php';
        }

        $mail = new PHPMailer(true);

        try {
            // Konfiguracja serwera
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $config['port'];
            $mail->CharSet = 'UTF-8';

            // Poprawa dostarczalności - ustawienie domeny klienta (ważne dla Office 365)
            $mail->Hostname = 'zs3.lukow.pl';

            // Rozwiązanie problemów z SSL/TLS (częste na lokalnych serwerach XAMPP)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Odbiorcy
            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->Sender = $config['from_email']; // Ustawienie Envelope-From
            $mail->addReplyTo($config['from_email'], $config['from_name']);
            $mail->addAddress($to);

            // Treść
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = strip_tags($message);

            $mail->send();
            self::log($to, $subject, $message, 'SMTP SENT');
            return true;
        } catch (Exception $e) {
            self::log($to, $subject, $message, "SMTP ERROR: {$mail->ErrorInfo}");
            return false;
        }
    }

    public static function notifyAdmins($pdo, $subject, $message)
    {
        try {
            $stmt = $pdo->query("SELECT email FROM users WHERE role = 'admin'");
            $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($admins as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    self::send($email, "[Admin] $subject", $message);
                }
            }
        } catch (PDOException $e) {
            // Ignoruj błędy bazy przy mailach
            self::log('ADMINS', $subject, $message, "DB ERROR: " . $e->getMessage());
        }
    }
}
?>