<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

require_admin();

$pdo = get_db_connection();

function fetch_import_stats(PDO $pdo): array
{
    $total = (int)$pdo->query('SELECT COUNT(*) FROM cars')->fetchColumn();
    $last = $pdo->query('SELECT MAX(updated_at) FROM cars')->fetchColumn();
    return [
        'total' => $total,
        'last' => $last,
    ];
}

$statusMessage = null;
$importCount = 0;
$stats = fetch_import_stats($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['data_file']) || $_FILES['data_file']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Kunde inte läsa den uppladdade filen.');
        }

        $fileInfo = $_FILES['data_file'];
        $extension = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
        if ($extension !== 'json') {
            throw new RuntimeException('Endast JSON-filer stöds.');
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'hw_import_');
        if ($tmpPath === false || !move_uploaded_file($fileInfo['tmp_name'], $tmpPath)) {
            throw new RuntimeException('Kunde inte spara den uppladdade filen temporärt.');
        }

        $json = file_get_contents($tmpPath);
        @unlink($tmpPath);
        if ($json === false) {
            throw new RuntimeException('Kunde inte läsa innehållet i filen.');
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new RuntimeException('Ogiltig JSON-struktur. Förväntar en array av objekt.');
        }

        $required = ['name', 'year', 'series', 'collector_number', 'is_treasure_hunt', 'is_super_treasure', 'image_url'];
        foreach ($data as $index => $entry) {
            if (!is_array($entry)) {
                throw new RuntimeException('Ogiltigt objekt på index ' . $index . '.');
            }
            foreach ($required as $field) {
                if (!array_key_exists($field, $entry)) {
                    throw new RuntimeException("Fältet '{$field}' saknas i post " . ($index + 1) . '.');
                }
            }
            foreach ($entry as $key => $value) {
                if (is_string($value)) {
                    $data[$index][$key] = trim($value);
                }
            }
        }

        if (empty($data)) {
            throw new RuntimeException('JSON-filen innehåller inga poster.');
        }

        $pdo->beginTransaction();
        $sql = 'INSERT INTO cars (name, year, series, collector_number, is_treasure_hunt, is_super_treasure, image_url)
                VALUES (:name, :year, :series, :collector_number, :treasure, :super, :image)
                ON DUPLICATE KEY UPDATE series = VALUES(series), collector_number = VALUES(collector_number),
                is_treasure_hunt = VALUES(is_treasure_hunt), is_super_treasure = VALUES(is_super_treasure), image_url = VALUES(image_url)';
        $stmt = $pdo->prepare($sql);

        foreach ($data as $entry) {
            $stmt->execute([
                'name' => $entry['name'],
                'year' => (int)$entry['year'],
                'series' => $entry['series'] !== '' ? $entry['series'] : 'Okänd serie',
                'collector_number' => $entry['collector_number'] !== '' ? $entry['collector_number'] : null,
                'treasure' => !empty($entry['is_treasure_hunt']) ? 1 : 0,
                'super' => !empty($entry['is_super_treasure']) ? 1 : 0,
                'image' => $entry['image_url'] ?? '',
            ]);
            $importCount++;
        }

        $pdo->commit();
        $statusMessage = ['type' => 'success', 'text' => "Importen slutförd. {$importCount} poster bearbetades."];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $statusMessage = ['type' => 'danger', 'text' => $e->getMessage()];
        $importCount = 0;
    }

    $stats = fetch_import_stats($pdo);
}

$pageTitle = 'Admin import';
include __DIR__ . '/../partials/header.php';
?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0">Senaste import</h6>
            </div>
            <div class="card-body">
                <p class="mb-2">Total antal bilar: <strong><?php echo h($stats['total']); ?></strong></p>
                <p class="mb-0">Senaste uppdatering:
                    <strong>
                        <?php echo $stats['last'] ? h(date('Y-m-d H:i', strtotime($stats['last']))) : 'Ingen import ännu'; ?>
                    </strong>
                </p>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Importera Hot Wheels-data</h6>
                <span class="badge bg-secondary">Endast JSON</span>
            </div>
            <div class="card-body">
                <?php if ($statusMessage): ?>
                    <div class="alert alert-<?php echo h($statusMessage['type']); ?>">
                        <?php echo h($statusMessage['text']); ?>
                    </div>
                <?php endif; ?>
                <form method="post" enctype="multipart/form-data" class="vstack gap-3">
                    <div>
                        <label for="data_file" class="form-label">Välj JSON-fil</label>
                        <input type="file" class="form-control" id="data_file" name="data_file" accept="application/json,.json" required>
                        <div class="form-text">Filen ska följa strukturen från <code>hotwheels_all_years.json</code>.</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-cloud-upload"></i> Importera
                        </button>
                        <?php if ($importCount > 0): ?>
                            <span class="align-self-center text-success">Sist: <?php echo $importCount; ?> poster</span>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
