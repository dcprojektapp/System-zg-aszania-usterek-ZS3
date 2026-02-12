<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

if (isset($_SESSION['flash_success'])) {
    $success_msg = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

// Obsługa formularza zgłoszenia
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reporter_name = trim($_POST['reporter_name'] ?? '');
    $room = trim($_POST['room_number'] ?? '');
    $issue = trim($_POST['issue_type'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if ($reporter_name && $room && $issue) {
        try {
            $stmt = $pdo->prepare("INSERT INTO tickets (user_id, reporter_name, room_number, issue_type, description) VALUES (:user_id, :reporter, :room, :issue, :desc)");
            $stmt->execute([
                ':user_id' => $user_id,
                ':reporter' => $reporter_name,
                ':room' => $room,
                ':issue' => $issue,
                ':desc' => $desc
            ]);

            // Powiadomienie Email dla Adminów
            require_once __DIR__ . '/../includes/Mailer.php';
            $mail_subject = "Nowe zgłoszenie: Sala $room ($issue)";
            $mail_body = "
                <h3>Nowe zgłoszenie usterki</h3>
                <p><strong>Zgłaszający:</strong> " . htmlspecialchars($reporter_name) . "</p>
                <p><strong>Sala:</strong> " . htmlspecialchars($room) . "</p>
                <p><strong>Typ usterki:</strong> " . htmlspecialchars($issue) . "</p>
                <p><strong>Opis:</strong> " . nl2br(htmlspecialchars($desc)) . "</p>
                <p><a href='http://{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['PHP_SELF']) . "/admin/dashboard.php'>Przejdź do panelu administratora</a></p>
            ";
            Mailer::notifyAdmins($pdo, $mail_subject, $mail_body);

            // Powiadomienie Telegram
            require_once __DIR__ . '/../includes/TelegramNotifier.php';
            $telegram_message = "🆕 <b>Nowe zgłoszenie usterki</b>\n\n" .
                "🕒 <b>Data:</b> " . date('Y-m-d H:i') . "\n" .
                "👤 <b>Zgłaszający:</b> " . htmlspecialchars($reporter_name) . "\n" .
                "📍 <b>Sala:</b> " . htmlspecialchars($room) . "\n" .
                "⚠️ <b>Usterka:</b> " . htmlspecialchars($issue) . "\n";
            if (!empty($desc)) {
                $telegram_message .= "📝 <b>Opis:</b> " . htmlspecialchars($desc) . "\n";
            }

            // Link do panelu (zakładając, że domena jest dostępna publicznie lub w sieci lokalnej)
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $link = "$protocol://{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['PHP_SELF']) . "/admin/dashboard.php";
            $telegram_message .= "\n🔗 <a href='$link'>Przejdź do panelu</a>";

            TelegramNotifier::send($telegram_message);
            $_SESSION['flash_success'] = "Zgłoszenie od " . htmlspecialchars($reporter_name) . " zostało wysłane pomyślnie.";
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            $error_msg = "Błąd podczas wysyłania zgłoszenia: " . $e->getMessage();
        }
    } else {
        $error_msg = "Proszę wypełnić wymagane pola.";
    }
}

require_once __DIR__ . '/../includes/header.php';

// Pobieranie zgłoszeń użytkownika
try {
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = :user_id ORDER BY created_at DESC");
    $stmt->execute([':user_id' => $user_id]);
    $tickets = $stmt->fetchAll();
} catch (PDOException $e) {
    $tickets = [];
    $error_msg = "Nie udało się pobrać listy zgłoszeń.";
}
?>

<div class="row">
    <!-- Formularz zgłoszeniowy -->
    <div class="col-lg-4 mb-4 d-flex flex-column">
        <div class="card flex-fill">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold mb-4">Nowe zgłoszenie</h5>

                <?php if ($success_msg): ?>
                    <div class="alert alert-success shadow-sm border-0"><?php echo $success_msg; ?></div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                    <div class="alert alert-danger shadow-sm border-0"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <form method="POST" action="index.php" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="reporter_name" class="form-label fw-bold small text-uppercase text-muted">Osoba
                            zgłaszająca</label>
                        <select class="form-select select2" id="reporter_name" name="reporter_name" required
                            data-placeholder="Wybierz osobę..." style="width: 100%;">
                            <option></option>
                            <?php
                            $teachers = [
                                'Bancerz Aneta',
                                'Biadun Paweł',
                                'Bielińska Aneta',
                                'Bijata Mateusz',
                                'Boreczek Dariusz',
                                'Boreczek Monika',
                                'Borkowska Anna',
                                'Borkowska Ilona',
                                'Chaber Dawid',
                                'Cichowlas Karol',
                                'Czarnecka Katarzyna',
                                'Drygiel Daniel',
                                'Duszek Beata',
                                'Gawłowicz Joanna',
                                'Grzyb Marcin',
                                'Ignatowicz Beata',
                                'Izdebska Agnieszka',
                                'Janaszek Janusz',
                                'Janowski Rafał',
                                'Jurek Małgorzata',
                                'Juszczyńska Małgorzata',
                                'Karwowska Małgorzata',
                                'Karwowski Sławomir',
                                'Kłoczko Robert',
                                'Koboj Arkadiusz',
                                'Kopeć Mateusz',
                                'Korgul Agnieszka',
                                'Kozakiewicz Danuta',
                                'Kożuch Anna',
                                'Lesiewicz Dorota',
                                'Linde Iwona',
                                'Majczyna Justyna',
                                'Markowska Joanna',
                                'Moskwiak Małgorzata',
                                'Narejko Mariusz',
                                'Opalińska Katarzyna',
                                'Opaliński Dariusz',
                                'Pakuła Iwona',
                                'Peplak Tomasz',
                                'Petrowa Jolanta',
                                'Pietrzak Anna',
                                'Pietrzak Mirosław',
                                'Pilich Natalia',
                                'Pulik Wioletta',
                                'Rodak Edyta',
                                'Rokicka Małgorzata',
                                'Rokicki Artur',
                                'Rzepecki Wojciech',
                                'Rzymowska Agata',
                                'Rzymowska Ewa',
                                'Sadło Marcin',
                                'Sawicka Dorota',
                                'Sawicki Bartosz',
                                'Siwiec Weronika',
                                'Smyk Wojciech',
                                'Sozoniuk Monika',
                                'Syrzycka Barbara',
                                'Świder Michał',
                                'Świerk Małgorzata',
                                'Świętochowska Magdalena',
                                'Tchórzewska-Sidor Anna',
                                'Tchórzewski Damian',
                                'Tomaszewska-Peryt Wioletta',
                                'Wawryniuk Renata',
                                'Wiatr Jakub Grzegorz',
                                'Wiącek Małgorzata',
                                'Wiącek Zbigniew',
                                'Wojciechowski Radosław',
                                'Wójcik Anna',
                                'Wójcik Justyna',
                                'Zaniewicz Daniel',
                                'Zarzycka-Dmitruk Katarzyna',
                                'Zemło Paweł',
                                'Zielonka Wojciech',
                                'Żurawska-Bijata Monika',
                                'Żurawski Michał'
                            ];
                            sort($teachers, SORT_LOCALE_STRING);
                            $selected_reporter = $_POST['reporter_name'] ?? '';
                            foreach ($teachers as $teacher) {
                                $selected = ($teacher === $selected_reporter) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($teacher) . '" ' . $selected . '>' . htmlspecialchars($teacher) . '</option>';
                            }
                            ?>
                        </select>
                        <div class="invalid-feedback">Wybierz osobę zgłaszającą z listy.</div>
                    </div>

                    <div class="mb-3">
                        <label for="room_number"
                            class="form-label fw-bold small text-uppercase text-muted">Lokalizacja</label>
                        <select class="form-select select2" id="room_number" name="room_number" required
                            data-placeholder="Wybierz salę..." style="width: 100%;">
                            <option></option>
                            <?php
                            $rooms = [
                                '1',
                                '1a',
                                '1b',
                                '2',
                                '3',
                                '4a',
                                '4b',
                                '5',
                                '6',
                                '7',
                                '8',
                                '9',
                                '10',
                                '11',
                                '12',
                                '13',
                                '14',
                                '15',
                                '16',
                                '17',
                                '18',
                                '19',
                                '20',
                                'B1',
                                'B2',
                                'B3',
                                'B4',
                                'B5',
                                'B6',
                                'bo1',
                                'bo2',
                                'bo3',
                                'bo4',
                                'cen',
                                'int',
                                'JO',
                                'k1',
                                'k2',
                                'k3',
                                'k4',
                                'K5',
                                'kate1',
                                'kate2',
                                'kate3',
                                'kate4',
                                'kuch.',
                                'm1',
                                'm2',
                                'm3',
                                'm4',
                                'm5',
                                'Sala',
                                'wdż'
                            ];
                            sort($rooms, SORT_NATURAL | SORT_FLAG_CASE);
                            foreach ($rooms as $roomOption) {
                                echo '<option value="' . htmlspecialchars($roomOption) . '">' . htmlspecialchars($roomOption) . '</option>';
                            }
                            ?>
                        </select>
                        <div class="invalid-feedback">Wybierz lokalizację z listy.</div>
                    </div>

                    <div class="mb-3">
                        <label for="issue_type" class="form-label fw-bold small text-uppercase text-muted">Rodzaj
                            usterki</label>
                        <select class="form-select select2" id="issue_type" name="issue_type" required
                            data-placeholder="Wybierz..." style="width: 100%;">
                            <option></option>
                            <option value="Komputer nie uruchamia się">Komputer nie uruchamia się</option>
                            <option value="Problem z myszką / klawiaturą">Problem z myszką / klawiaturą</option>
                            <option value="Brak internetu">Brak internetu</option>
                            <option value="Problem z monitorem">Problem z monitorem</option>
                            <option value="Telewizory / Projektor">Telewizory / Projektor</option>
                            <option value="Dźwięk (Głośniki / Słuchawki)">Dźwięk (Głośniki / Słuchawki)</option>
                            <option value="Oprogramowanie / Logowanie">Oprogramowanie / Logowanie</option>
                            <option value="Zalanie sprzętu">Zalanie sprzętu</option>
                            <option value="Uszkodzenie gniazdka (USB/Audio)">Uszkodzenie gniazdka (USB/Audio)</option>
                            <option value="Wirusy / Podejrzane reklamy">Wirusy / Podejrzane reklamy</option>
                            <option value="Inne">Inne (Opisz w dodatkowym opisie)</option>
                        </select>
                        <div class="invalid-feedback">Wybierz rodzaj usterki.</div>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label fw-bold small text-uppercase text-muted">Opis
                            dodatkowy</label>
                        <textarea class="form-control" id="description" name="description" rows="4"
                            placeholder="Opisz krótko problem..."></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary shadow-sm py-2">
                            <span class="fw-bold">Wyślij zgłoszenie</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Lista zgłoszeń -->
    <div class="col-lg-8 mb-4 d-flex flex-column">
        <div class="card flex-fill">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold mb-4">Zgłoszenia usterek</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle custom-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Osoba</th>
                                <th>Miejsce</th>
                                <th>Usterka</th>
                                <th>Status</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($tickets) > 0): ?>
                                <?php foreach ($tickets as $ticket): ?>
                                    <tr>
                                        <td class="fw-bold text-muted">#<?php echo $ticket['id']; ?></td>
                                        <td><?php echo htmlspecialchars($ticket['reporter_name'] ?? $_SESSION['user_name']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($ticket['room_number']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['issue_type']); ?></td>
                                        <td>
                                            <?php
                                            $status = $ticket['status'];
                                            $badgeClass = 'bg-secondary';
                                            $icon = '';
                                            switch ($status) {
                                                case 'nowe':
                                                    $badgeClass = 'bg-danger';
                                                    $icon = '❗';
                                                    break;
                                                case 'w_trakcie':
                                                    $badgeClass = 'bg-warning text-dark';
                                                    $icon = '⚙️';
                                                    break;
                                                case 'naprawione':
                                                    $badgeClass = 'bg-success';
                                                    $icon = '✅';
                                                    break;
                                                case 'rozwiazane':
                                                    $badgeClass = 'bg-info text-dark';
                                                    $icon = 'ℹ️';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?> rounded-pill px-3 py-2">
                                                <?php echo $icon . ' ' . ucfirst(str_replace('_', ' ', $status)); ?>
                                            </span>
                                        </td>
                                        <td class="text-muted small">
                                            <?php echo date('d.m.Y', strtotime($ticket['created_at'])); ?>
                                            <div class="tiny-text"><?php echo date('H:i', strtotime($ticket['created_at'])); ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <div class="mb-2" style="font-size: 2rem;">📭</div>
                                        Brak zgłoszeń. Ciesz się dniem!
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Walidacja Bootstrap
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms)
            .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
    })()

    // Inicjalizacja Select2
    $(document).ready(function () {
        $('.select2').select2({
            theme: 'bootstrap-5',
            placeholder: 'Wybierz...',
            width: '100%'
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>