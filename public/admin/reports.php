<?php
require_once __DIR__ . '/../../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obsługa eksportu do CSV (musi być przed jakimkolwiek outputem HTML)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        die('Brak uprawnień');
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=raport_usterek_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');

    // BOM dla Excela
    fputs($output, "\xEF\xBB\xBF");

    // Nagłówki kolumn
    fputcsv($output, ['ID', 'Zgłaszający', 'Sala', 'Typ Usterki', 'Opis', 'Status', 'Data Utworzenia']);

    // Pobranie danych z filtrowaniem
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $where_sql = "1=1";
    $params = [];

    if ($date_from) {
        $where_sql .= " AND t.created_at >= :date_from";
        $params[':date_from'] = $date_from . ' 00:00:00';
    }
    if ($date_to) {
        $where_sql .= " AND t.created_at <= :date_to";
        $params[':date_to'] = $date_to . ' 23:59:59';
    }

    $query = "SELECT t.id, COALESCE(NULLIF(t.reporter_name, ''), u.name) as reporter, t.room_number, t.issue_type, t.description, t.status, t.created_at 
              FROM tickets t 
              JOIN users u ON t.user_id = u.id 
              WHERE $where_sql
              ORDER BY t.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

require_once __DIR__ . '/../../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>window.location.href = '../login.php';</script>";
    exit;
}

// Parametry filtrowania (ponownie dla widoku HTML)
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where_sql = "1=1";
$params = [];

if ($date_from) {
    $where_sql .= " AND created_at >= :date_from";
    $params[':date_from'] = $date_from . ' 00:00:00';
}
if ($date_to) {
    $where_sql .= " AND created_at <= :date_to";
    $params[':date_to'] = $date_to . ' 23:59:59';
}

// 1. Podstawowe liczniki
$stats = [
    'total' => 0,
    'new' => 0,
    'in_progress' => 0,
    'resolved' => 0,
];

// Pobieranie statystyk ogólnych
try {
    // Helper function for prepared statements
    function getCount($pdo, $sql, $params)
    {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    $stats['total'] = getCount($pdo, "SELECT COUNT(*) FROM tickets WHERE $where_sql", $params);
    $stats['new'] = getCount($pdo, "SELECT COUNT(*) FROM tickets WHERE status = 'nowe' AND $where_sql", $params);
    $stats['in_progress'] = getCount($pdo, "SELECT COUNT(*) FROM tickets WHERE status = 'w_trakcie' AND $where_sql", $params);
    $stats['resolved'] = getCount($pdo, "SELECT COUNT(*) FROM tickets WHERE status IN ('rozwiazane', 'naprawione') AND $where_sql", $params);



    // Dane do wykresów
    // Statusy
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM tickets WHERE $where_sql GROUP BY status");
    $stmt->execute($params);
    $statusData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Typy usterek (Top 5)
    $stmt = $pdo->prepare("SELECT issue_type, COUNT(*) as count FROM tickets WHERE $where_sql GROUP BY issue_type ORDER BY count DESC LIMIT 5");
    $stmt->execute($params);
    $issueData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Sale (Top 5)
    $stmt = $pdo->prepare("SELECT room_number, COUNT(*) as count FROM tickets WHERE $where_sql GROUP BY room_number ORDER BY count DESC LIMIT 5");
    $stmt->execute($params);
    $roomData = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Błąd pobierania danych: ' . $e->getMessage() . '</div>';
}
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <h2 class="fw-bold m-0">Raporty i Statystyki</h2>

        <form class="d-flex gap-2 align-items-center" method="GET" action="reports.php">
            <div class="input-group">
                <span class="input-group-text bg-white text-muted small">Od</span>
                <input type="date" class="form-control" name="date_from"
                    value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            <div class="input-group">
                <span class="input-group-text bg-white text-muted small">Do</span>
                <input type="date" class="form-control" name="date_to"
                    value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            <button type="submit" class="btn btn-primary shadow-sm">Filtruj</button>
            <?php if ($date_from || $date_to): ?>
                <a href="reports.php" class="btn btn-outline-secondary" title="Wyczyść filtry">✕</a>
            <?php endif; ?>
            <a href="reports.php?export=csv<?php echo $date_from ? '&date_from=' . $date_from : ''; ?><?php echo $date_to ? '&date_to=' . $date_to : ''; ?>"
                class="btn btn-success shadow-sm" title="Pobierz CSV">
                CSV
            </a>
        </form>
    </div>
</div>

<!-- Kafelki Statystyk -->
<div class="row mb-4 g-3 row-cols-1 row-cols-md-2 row-cols-lg-4">
    <div class="col">
        <div class="card border-0 shadow-sm h-100 bg-primary text-white">
            <div class="card-body">
                <h6 class="text-uppercase opacity-75 small fw-bold">Wszystkie</h6>
                <h2 class="display-6 fw-bold mb-0"><?php echo $stats['total']; ?></h2>
                <small class="opacity-75">Łącznie zgłoszeń</small>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card border-0 shadow-sm h-100 bg-danger text-white">
            <div class="card-body">
                <h6 class="text-uppercase opacity-75 small fw-bold">Nowe</h6>
                <h2 class="display-6 fw-bold mb-0"><?php echo $stats['new']; ?></h2>
                <small class="opacity-75">Wymagają uwagi</small>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card border-0 shadow-sm h-100 bg-warning text-dark">
            <div class="card-body">
                <h6 class="text-uppercase opacity-75 small fw-bold">W trakcie</h6>
                <h2 class="display-6 fw-bold mb-0"><?php echo $stats['in_progress']; ?></h2>
                <small class="opacity-75">Serwisowane</small>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card border-0 shadow-sm h-100 bg-success text-white">
            <div class="card-body">
                <h6 class="text-uppercase opacity-75 small fw-bold">Rozwiązane</h6>
                <h2 class="display-6 fw-bold mb-0"><?php echo $stats['resolved']; ?></h2>
                <small class="opacity-75">Sukces</small>
            </div>
        </div>
    </div>

</div>

<div class="row mb-4">
    <!-- Wykres Usterki -->
    <div class="col-lg-8 mb-4 mb-lg-0">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold mb-4 text-secondary">Najczęstsze typy usterek</h5>
                <div style="height: 300px; position: relative; width: 100%;">
                    <canvas id="issuesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Wykres Statusy -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4 d-flex flex-column">
                <h5 class="card-title fw-bold mb-4 text-secondary">Status zgłoszeń</h5>
                <div class="flex-grow-1 d-flex align-items-center justify-content-center">
                    <canvas id="statusChart" style="max-height: 250px;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Tabela Sal -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold mb-3 text-secondary">Najbardziej awaryjne sale</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sala</th>
                                <th class="text-end">Liczba zgłoszeń</th>
                                <th class="text-end">Udział</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roomData as $room): ?>
                                <?php
                                $percent = $stats['total'] > 0 ? round(($room['count'] / $stats['total']) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td class="fw-bold text-primary"><?php echo htmlspecialchars($room['room_number']); ?>
                                    </td>
                                    <td class="text-end fw-bold"><?php echo $room['count']; ?></td>
                                    <td class="text-end text-muted small"><?php echo $percent; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($roomData)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Brak danych</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Ostatnie zgłoszenia (Mini lista) -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title fw-bold m-0 text-secondary">Ostatnie zgłoszenia</h5>
                    <a href="dashboard.php" class="btn btn-sm btn-outline-primary">Zobacz wszystkie</a>
                </div>

                <?php
                $latest = $pdo->query("SELECT * FROM tickets ORDER BY created_at DESC LIMIT 5")->fetchAll();
                ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($latest as $ticket): ?>
                        <div class="list-group-item px-0 border-bottom-0 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold text-dark">
                                    #<?php echo $ticket['id']; ?>     <?php echo htmlspecialchars($ticket['issue_type']); ?>
                                </span>
                                <div class="small text-muted">
                                    Sala: <?php echo htmlspecialchars($ticket['room_number']); ?> •
                                    <?php echo date('d.m H:i', strtotime($ticket['created_at'])); ?>
                                </div>
                            </div>
                            <?php
                            $badgeClass = 'bg-secondary';
                            if ($ticket['status'] == 'nowe')
                                $badgeClass = 'bg-danger';
                            if ($ticket['status'] == 'w_trakcie')
                                $badgeClass = 'bg-warning text-dark';
                            if ($ticket['status'] == 'naprawione')
                                $badgeClass = 'bg-success';
                            if ($ticket['status'] == 'rozwiazane')
                                $badgeClass = 'bg-info text-dark';
                            ?>
                            <span class="badge <?php echo $badgeClass; ?> rounded-pill">
                                <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($latest)): ?>
                        <div class="text-center text-muted py-3">Brak zgłoszeń</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Konfiguracja kolorów
    const colors = {
        primary: '#4e73df',
        success: '#1cc88a',
        info: '#36b9cc',
        warning: '#f6c23e',
        danger: '#e74a3b',
        secondary: '#858796',
        light: '#f8f9fc'
    };

    // Dane do wykresu Usterek
    const issueLabels = <?php echo json_encode(array_column($issueData, 'issue_type')); ?>;
    const issueCounts = <?php echo json_encode(array_column($issueData, 'count')); ?>;

    const ctxIssues = document.getElementById('issuesChart').getContext('2d');
    new Chart(ctxIssues, {
        type: 'bar',
        data: {
            labels: issueLabels,
            datasets: [{
                label: 'Liczba zgłoszeń',
                data: issueCounts,
                backgroundColor: colors.primary,
                borderRadius: 5,
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [2, 2] }
                },
                x: {
                    grid: { display: false }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });

    // Dane do wykresu Statusów
    const statusData = {
        'Nowe': <?php echo $statusData['nowe'] ?? 0; ?>,
        'W trakcie': <?php echo $statusData['w_trakcie'] ?? 0; ?>,
        'Naprawione': <?php echo $statusData['naprawione'] ?? 0; ?>,
        'Rozwiązane': <?php echo $statusData['rozwiazane'] ?? 0; ?>
    };

    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: Object.keys(statusData),
            datasets: [{
                data: Object.values(statusData),
                backgroundColor: [colors.danger, colors.warning, colors.success, colors.info],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            },
            cutout: '70%'
        }
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>