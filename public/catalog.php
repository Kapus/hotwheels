<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/car_repository.php';

require_login();

$user = current_user();
$userId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $carId = parse_int($_POST['car_id'] ?? null);
    $quantity = max(0, parse_int($_POST['quantity'] ?? null));
    $condition = $_POST['condition'] ?? 'mint';
    $notes = trim($_POST['notes'] ?? '');

    if ($carId <= 0) {
        redirect_with_message('catalog.php', 'danger', 'Ogiltigt fordons-id.');
    }

    if ($quantity === 0) {
        delete_user_car($userId, $carId);
        redirect_with_message('catalog.php', 'info', 'Bilen togs bort från din samling.');
    }

    if (!in_array($condition, ['mint', 'loose', 'custom'], true)) {
        $condition = 'mint';
    }

    upsert_user_car($userId, $carId, $quantity, $condition, $notes);
    redirect_with_message('catalog.php', 'success', 'Samlingen uppdaterades.');
}

$criteria = [
    'q' => trim($_GET['q'] ?? ''),
    'year' => $_GET['year'] ?? '',
    'series' => $_GET['series'] ?? '',
    'treasure' => $_GET['treasure'] ?? '',
    'sort' => $_GET['sort'] ?? '',
];

$cars = search_cars($criteria);
$resultCount = count($cars);
$hasSort = !empty($criteria['sort']) && $criteria['sort'] !== 'year_desc';
$filters = get_car_filters();
$userCars = get_user_car_map($userId);

$pageTitle = 'Katalog';
include __DIR__ . '/../partials/header.php';

$conditionOptions = [
    'mint' => 'Mint',
    'loose' => 'Loose',
    'custom' => 'Custom',
];
?>
<form id="filterForm" class="card card-body shadow-sm border-0 mb-4 js-filter-form" method="get">
    <div class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label" for="q">Sök</label>
            <input type="text" id="q" name="q" value="<?php echo h($criteria['q']); ?>" class="form-control" placeholder="Namn, serie...">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="year">År</label>
            <select id="year" name="year" class="form-select">
                <option value="">Alla</option>
                <?php foreach ($filters['years'] as $year): ?>
                    <option value="<?php echo $year; ?>" <?php echo $criteria['year'] == $year ? 'selected' : ''; ?>><?php echo $year; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="series">Serie</label>
            <select id="series" name="series" class="form-select">
                <option value="">Alla</option>
                <?php foreach ($filters['series'] as $series): ?>
                    <option value="<?php echo h($series); ?>" <?php echo $criteria['series'] === $series ? 'selected' : ''; ?>><?php echo h($series); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="treasure">Treasure</label>
            <select id="treasure" name="treasure" class="form-select">
                <option value="">Alla</option>
                <option value="regular" <?php echo $criteria['treasure'] === 'regular' ? 'selected' : ''; ?>>Treasure Hunt</option>
                <option value="super" <?php echo $criteria['treasure'] === 'super' ? 'selected' : ''; ?>>Super Treasure</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="sort">Sortering</label>
            <select id="sort" name="sort" class="form-select">
                <option value="year_desc" <?php echo $criteria['sort'] === 'year_desc' ? 'selected' : ''; ?>>År (nyast)</option>
                <option value="year_asc" <?php echo $criteria['sort'] === 'year_asc' ? 'selected' : ''; ?>>År (äldst)</option>
                <option value="name_asc" <?php echo $criteria['sort'] === 'name_asc' ? 'selected' : ''; ?>>Namn A-Ö</option>
                <option value="name_desc" <?php echo $criteria['sort'] === 'name_desc' ? 'selected' : ''; ?>>Namn Ö-A</option>
                <option value="collector_asc" <?php echo $criteria['sort'] === 'collector_asc' ? 'selected' : ''; ?>>Samlar-nummer stigande</option>
                <option value="collector_desc" <?php echo $criteria['sort'] === 'collector_desc' ? 'selected' : ''; ?>>Samlar-nummer fallande</option>
            </select>
        </div>
        <div class="col-md-12 d-flex justify-content-end">
            <div class="d-flex gap-2">
                <a href="catalog.php" class="btn btn-outline-secondary">Rensa</a>
                <button class="btn btn-primary" type="submit">Filtrera</button>
            </div>
        </div>
    </div>
</form>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0 text-muted">Visar <?php echo $resultCount; ?> bilar</h6>
    <?php if (!empty($criteria['q']) || !empty($criteria['year']) || !empty($criteria['series']) || !empty($criteria['treasure']) || $hasSort): ?>
        <small class="text-muted">Aktiva filter används</small>
    <?php endif; ?>
</div>

<div class="row g-3">
    <?php if (empty($cars)): ?>
        <div class="col-12">
            <div class="alert alert-info">Inga bilar matchar dina filter just nu.</div>
        </div>
    <?php endif; ?>
    <?php foreach ($cars as $car):
        $owned = $userCars[$car['id']] ?? null;
    ?>
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 shadow-sm border-0">
                <?php if (!empty($car['image_url'])): ?>
                    <img src="<?php echo h($car['image_url']); ?>" class="card-img-top" alt="<?php echo h($car['name']); ?>">
                <?php else: ?>
                    <div class="card-img-top bg-secondary bg-gradient text-white d-flex align-items-center justify-content-center">Ingen bild</div>
                <?php endif; ?>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title mb-1"><?php echo h($car['name']); ?></h5>
                            <small class="text-muted"><?php echo (int)$car['year']; ?> · <?php echo h($car['series']); ?></small>
                            <?php if (!empty($car['collector_number'])): ?>
                                <div class="small text-muted">#<?php echo h($car['collector_number']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="text-end">
                            <?php if ($car['is_super_treasure']): ?>
                                <span class="badge bg-danger">Super Treasure</span>
                            <?php elseif ($car['is_treasure_hunt']): ?>
                                <span class="badge bg-warning text-dark">Treasure Hunt</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($owned): ?>
                        <div class="mt-2">
                            <span class="badge bg-success">I din samling: <?php echo (int)$owned['quantity']; ?> st</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white">
                    <form method="post" class="row g-2 align-items-center">
                        <input type="hidden" name="car_id" value="<?php echo (int)$car['id']; ?>">
                        <div class="col-4">
                            <label class="form-label small text-muted">Antal</label>
                            <input type="number" min="0" name="quantity" class="form-control" value="<?php echo h($owned['quantity'] ?? '0'); ?>">
                        </div>
                        <div class="col-4">
                            <label class="form-label small text-muted">Skick</label>
                            <select name="condition" class="form-select">
                                <?php foreach ($conditionOptions as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo (($owned['condition'] ?? 'mint') === $key) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted">Anteckning</label>
                            <textarea name="notes" class="form-control" rows="1" placeholder="Till exempel skick eller byten."><?php echo h($owned['notes'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-12 d-grid">
                            <button class="btn btn-outline-primary btn-sm">Jag har denna</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
