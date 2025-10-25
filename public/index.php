<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/car_repository.php';

require_login();

$pdo = get_db_connection();
$totalCars = (int)$pdo->query('SELECT COUNT(*) FROM cars')->fetchColumn();
$treasureTotal = (int)$pdo->query('SELECT COUNT(*) FROM cars WHERE is_treasure_hunt = 1')->fetchColumn();
$superTotal = (int)$pdo->query('SELECT COUNT(*) FROM cars WHERE is_super_treasure = 1')->fetchColumn();

$userId = (int)current_user()['id'];
$ownedDistinctStmt = $pdo->prepare('SELECT COUNT(*) FROM user_cars WHERE user_id = :user_id');
$ownedDistinctStmt->execute(['user_id' => $userId]);
$ownedDistinct = (int)$ownedDistinctStmt->fetchColumn();

$totalQuantityStmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM user_cars WHERE user_id = :user_id');
$totalQuantityStmt->execute(['user_id' => $userId]);
$ownedQuantity = (int)$totalQuantityStmt->fetchColumn();

$recentCarsStmt = $pdo->prepare('SELECT * FROM cars ORDER BY year DESC, id DESC LIMIT 6');
$recentCarsStmt->execute();
$recentCars = $recentCarsStmt->fetchAll();

$pageTitle = 'Dashboard';
include __DIR__ . '/../partials/header.php';
?>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h6 class="text-uppercase text-muted">Totalt bilar</h6>
                <h2 class="fw-bold"><?php echo $totalCars; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h6 class="text-uppercase text-muted">Treasure Hunts</h6>
                <h2 class="fw-bold text-warning"><?php echo $treasureTotal; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h6 class="text-uppercase text-muted">Super Treasure</h6>
                <h2 class="fw-bold text-danger"><?php echo $superTotal; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h6 class="text-uppercase text-muted">Min samling</h6>
                <h2 class="fw-bold"><?php echo $ownedDistinct; ?> <small class="text-muted">(<?php echo $ownedQuantity; ?> st)</small></h2>
            </div>
        </div>
    </div>
</div>

<section class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Senaste tillskotten</h5>
        <a href="catalog.php" class="btn btn-outline-primary btn-sm">Visa alla</a>
    </div>
    <div class="row g-3">
        <?php foreach ($recentCars as $car): ?>
            <div class="col-sm-6 col-md-4 col-xl-2">
                <div class="card h-100 shadow-sm border-0">
                    <?php if (!empty($car['image_url'])): ?>
                        <img src="<?php echo h($car['image_url']); ?>" class="card-img-top" alt="<?php echo h($car['name']); ?>">
                    <?php else: ?>
                        <div class="card-img-top bg-secondary bg-gradient d-flex align-items-center justify-content-center text-white">Ingen bild</div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h6 class="card-title"><?php echo h($car['name']); ?></h6>
                        <p class="card-text text-muted mb-1"><?php echo h($car['series']); ?></p>
                        <small class="text-muted"><?php echo (int)$car['year']; ?></small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
