<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/car_repository.php';

require_login();

$userId = (int)current_user()['id'];
$collection = get_user_collection($userId);
$stats = get_collection_stats($userId);

$yearLabels = array_map(fn($row) => (string)$row['year'], $stats['by_year']);
$yearValues = array_map(fn($row) => (int)$row['cnt'], $stats['by_year']);
$seriesLabels = array_map(fn($row) => $row['series'], $stats['by_series']);
$seriesValues = array_map(fn($row) => (int)$row['cnt'], $stats['by_series']);

$pageTitle = 'Min samling';
$pageScripts = $pageScripts ?? [];

$conditionLabels = [
    'mint' => 'Mint',
    'loose' => 'Loose',
    'custom' => 'Custom',
];
$conditionIcons = [
    'mint' => 'bi-shield-check',
    'loose' => 'bi-box',
    'custom' => 'bi-brush',
];

$pageScripts[] = '<script>document.addEventListener("DOMContentLoaded", function(){
    const yearCtx = document.getElementById("collectionByYear");
    if (yearCtx) {
        new Chart(yearCtx, {
            type: "line",
            data: {
                labels: ' . json_encode($yearLabels) . ',
                datasets: [{
                    label: "Antal modeller",
                    data: ' . json_encode($yearValues) . ',
                    borderColor: "#0d6efd",
                    backgroundColor: "rgba(13,110,253,0.2)",
                    tension: 0.3,
                }]
            }
        });
    }
    const seriesCtx = document.getElementById("collectionBySeries");
    if (seriesCtx) {
        new Chart(seriesCtx, {
            type: "doughnut",
            data: {
                labels: ' . json_encode($seriesLabels) . ',
                datasets: [{
                    label: "Serier",
                    data: ' . json_encode($seriesValues) . ',
                    backgroundColor: ["#0d6efd", "#6610f2", "#6f42c1", "#d63384", "#fd7e14", "#20c997", "#198754"],
                }]
            },
            options: { plugins: { legend: { position: "bottom" } } }
        });
    }
});</script>';

include __DIR__ . '/../partials/header.php';
?>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h6 class="text-uppercase text-muted">Distinct modeller</h6>
                <h2 class="fw-bold"><?php echo (int)$stats['basic']['distinct_count']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h6 class="text-uppercase text-muted">Totalt antal</h6>
                <h2 class="fw-bold"><?php echo (int)$stats['basic']['total_quantity']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h6 class="text-uppercase text-muted">Treasure i samlingen</h6>
                <h2 class="fw-bold text-warning">
                    <?php
                    $treasureCount = array_reduce($collection, function ($carry, $item) {
                        return $carry + (int)$item['is_treasure_hunt'] + (int)$item['is_super_treasure'];
                    }, 0);
                    echo $treasureCount;
                    ?>
                </h2>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h6 class="mb-0">Samling per år</h6>
            </div>
            <div class="card-body">
                <canvas id="collectionByYear" height="220"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h6 class="mb-0">Seriefördelning</h6>
            </div>
            <div class="card-body">
                <canvas id="collectionBySeries" height="220"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Mina bilar</h6>
        <small class="text-muted"><?php echo count($collection); ?> poster</small>
    </div>
    <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>Namn</th>
                    <th>År</th>
                    <th>Serie</th>
                    <th>Samlar#</th>
                    <th>Skick</th>
                    <th>Antal</th>
                    <th>Anteckning</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($collection as $item): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($item['image_url'])): ?>
                                    <img src="<?php echo h($item['image_url']); ?>" alt="<?php echo h($item['name']); ?>" width="48" height="48" class="rounded" style="object-fit:cover;">
                                <?php endif; ?>
                                <div>
                                    <div class="fw-semibold"><?php echo h($item['name']); ?></div>
                                    <?php if ($item['is_super_treasure']): ?>
                                        <span class="badge bg-danger">Super Treasure</span>
                                    <?php elseif ($item['is_treasure_hunt']): ?>
                                        <span class="badge bg-warning text-dark">Treasure Hunt</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?php echo (int)$item['year']; ?></td>
                        <td><?php echo h($item['series']); ?></td>
                        <td><?php echo h($item['collector_number'] ?? ''); ?></td>
                        <td>
                            <span class="badge bg-secondary condition-badge">
                                <i class="bi <?php echo $conditionIcons[$item['condition']] ?? 'bi-tag'; ?>"></i>
                                <?php echo h($conditionLabels[$item['condition']] ?? ucfirst($item['condition'])); ?>
                            </span>
                        </td>
                        <td><?php echo (int)$item['quantity']; ?></td>
                        <td><?php echo h($item['notes']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
