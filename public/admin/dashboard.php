<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$success_msg = '';
$error_msg = '';

// Obsługa archiwizacji zgłoszenia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_id'])) {
    $archive_id = (int) $_POST['archive_id'];
    try {
        $stmt = $pdo->prepare("UPDATE tickets SET is_archived = 1 WHERE id = :id");
        $stmt->execute([':id' => $archive_id]);
        $success_msg = "Zgłoszenie #" . $archive_id . " zostało zarchiwizowane.";
    } catch (PDOException $e) {
        $error_msg = "Błąd podczas archiwizacji: " . $e->getMessage();
    }
}

// Obsługa zmiany statusu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id'], $_POST['status'])) {
    $ticket_id = (int) $_POST['ticket_id'];
    $status = $_POST['status'];

    try {
        $resolved_at = ($status === 'naprawione' || $status === 'rozwiazane') ? date('Y-m-d H:i:s') : null;
        $stmt = $pdo->prepare("UPDATE tickets SET status = :status, resolved_at = :resolved_at WHERE id = :id");
        $stmt->execute([':status' => $status, ':resolved_at' => $resolved_at, ':id' => $ticket_id]);

        // Powiadomienie o zmianie statusu przez Telegram
        require_once __DIR__ . '/../../includes/TelegramNotifier.php';
        try {
            $userStmt = $pdo->prepare("SELECT t.issue_type, t.room_number FROM tickets t WHERE t.id = :id");
            $userStmt->execute([':id' => $ticket_id]);
            $ticketInfo = $userStmt->fetch();

            if ($ticketInfo) {
                $status_pl = mb_strtoupper(str_replace('_', ' ', $status));
                $telegram_message = "🔄 <b>Aktualizacja zgłoszenia</b>\n\n" .
                    "Twoje zgłoszenie dotyczące usterki <b>" . htmlspecialchars($ticketInfo['issue_type']) . "</b> w sali <b>" . htmlspecialchars($ticketInfo['room_number']) . "</b> zmieniło status na:\n\n" .
                    "📌 <b>{$status_pl}</b>";
                
                TelegramNotifier::send($telegram_message);
            }
        } catch (Exception $e) {
            // Ignoruj błędy wysyłki
        }

        $success_msg = "Status zgłoszenia #" . $ticket_id . " został zaktualizowany.";
    } catch (PDOException $e) {
        $error_msg = "Błąd aktualizacji statusu: " . $e->getMessage();
    }
}

// Filtrowanie
$filter_status = $_GET['status'] ?? '';
$where_clause = "";
$params = [];

if ($filter_status) {
    $where_clause = "WHERE t.status = :status AND t.is_archived = 0";
    $params[':status'] = $filter_status;
} else {
    $where_clause = "WHERE t.is_archived = 0";
}

// Pobieranie wszystkich zgłoszeń z nazwą użytkownika
try {
    $query = "SELECT t.*, COALESCE(NULLIF(t.reporter_name, ''), u.name) as user_name 
              FROM tickets t 
              JOIN users u ON t.user_id = u.id 
              $where_clause 
              ORDER BY t.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();
} catch (PDOException $e) {
    $tickets = [];
    $error_msg = "Nie udało się pobrać listy zgłoszeń.";
}
?>

<div class="card mb-4 shadow-sm">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <h5 class="card-title fw-bold m-0">Panel Administratora</h5>
            <div class="btn-group shadow-sm flex-wrap">
                <a href="dashboard.php"
                    class="btn btn-sm btn-outline-primary <?php echo !$filter_status ? 'active' : ''; ?>">Wszystkie</a>
                <a href="dashboard.php?status=nowe"
                    class="btn btn-sm btn-outline-danger <?php echo $filter_status == 'nowe' ? 'active' : ''; ?>">Nowe</a>
                <a href="dashboard.php?status=w_trakcie"
                    class="btn btn-sm btn-outline-warning <?php echo $filter_status == 'w_trakcie' ? 'active' : ''; ?>">W trakcie</a>
            </div>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success border-0 shadow-sm"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-danger border-0 shadow-sm"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle custom-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Zgłaszający</th>
                        <th>Miejsce</th>
                        <th>Usterka</th>
                        <th>Opis</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Akcja</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($tickets) > 0): ?>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td class="fw-bold text-muted">#<?php echo $ticket['id']; ?></td>
                                <td>
                                    <span
                                        class="fw-semibold text-primary"><?php echo htmlspecialchars($ticket['user_name']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($ticket['room_number']); ?></td>
                                <td>
                                    <span
                                        class="badge bg-light text-dark border"><?php echo htmlspecialchars($ticket['issue_type']); ?></span>
                                </td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($ticket['description']); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $badgeClass = 'bg-secondary';
                                    switch ($ticket['status']) {
                                        case 'nowe':
                                            $badgeClass = 'bg-danger';
                                            break;
                                        case 'w_trakcie':
                                            $badgeClass = 'bg-warning text-dark';
                                            break;
                                        case 'naprawione':
                                            $badgeClass = 'bg-success';
                                            break;
                                        case 'rozwiazane':
                                            $badgeClass = 'bg-info text-dark';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?> rounded-pill">
                                        <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                    </span>
                                </td>
                                <td class="small text-muted no-wrap-cell"><?php echo date('d.m H:i', strtotime($ticket['created_at'])); ?>
                                </td>
                                <td class="no-wrap-cell">
                                    <div class="d-flex flex-wrap gap-2">
                                        <form method="POST" class="d-flex" style="max-width: 140px;">
                                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                            <select name="status" class="form-select form-select-sm shadow-none border-1"
                                                onchange="this.form.submit()" style="font-size: 0.8rem;">
                                                <option value="" disabled selected>Zmień...</option>
                                                <option value="nowe">Nowe</option>
                                                <option value="w_trakcie">W trakcie</option>
                                                <option value="naprawione">Naprawione</option>
                                                <option value="rozwiazane">Rozwiązane</option>
                                            </select>
                                        </form>
                                        <form method="POST"
                                            onsubmit="return confirm('Czy na pewno chcesz przenieść to zgłoszenie do archiwum?');">
                                            <input type="hidden" name="archive_id" value="<?php echo $ticket['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary p-1 px-2 border-0"
                                                title="Archiwizuj zgłoszenie">
                                                📦
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">Brak zgłoszeń</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>