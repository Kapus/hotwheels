<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/car_repository.php';

require_login();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $year = parse_int($_POST['year'] ?? null, (int)date('Y'));
    $series = trim($_POST['series'] ?? 'Customs');
    $collector = trim($_POST['collector_number'] ?? '');
    $treasure = isset($_POST['is_treasure_hunt']);
    $super = isset($_POST['is_super_treasure']);
    $image = trim($_POST['image_url'] ?? '');

    if ($name === '') {
        $errors[] = 'Namn är obligatoriskt.';
    }

    if ($year < 1968 || $year > (int)date('Y') + 1) {
        $errors[] = 'Ange ett giltigt årtal.';
    }

    if (empty($errors)) {
        $carId = create_custom_car((int)current_user()['id'], $name, $year, $series ?: 'Customs', $collector !== '' ? $collector : null, $treasure, $super, $image);
        redirect_with_message('catalog.php', 'success', 'Din custom lades till med id ' . $carId . '.');
    }
}

$pageTitle = 'Lägg till custom';
include __DIR__ . '/../partials/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title">Skapa din egen Hot Wheels</h5>
                <p class="text-muted">Fyll i detaljerna för att lägga till en egen modifikation eller custom-bil.</p>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo h($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form method="post" class="vstack gap-3">
                    <div>
                        <label class="form-label" for="name">Namn</label>
                        <input type="text" name="name" id="name" class="form-control" required value="<?php echo h($_POST['name'] ?? ''); ?>">
                    </div>
                    <div>
                        <label class="form-label" for="year">År</label>
                        <input type="number" name="year" id="year" class="form-control" value="<?php echo h($_POST['year'] ?? date('Y')); ?>" min="1968" max="<?php echo date('Y') + 1; ?>">
                    </div>
                    <div>
                        <label class="form-label" for="series">Serie</label>
                        <input type="text" name="series" id="series" class="form-control" value="<?php echo h($_POST['series'] ?? 'Customs'); ?>">
                    </div>
                    <div>
                        <label class="form-label" for="collector_number">Samlar-nummer</label>
                        <input type="text" name="collector_number" id="collector_number" class="form-control" placeholder="t.ex. 01/250" value="<?php echo h($_POST['collector_number'] ?? ''); ?>">
                    </div>
                    <div>
                        <label class="form-label" for="image_url">Bild-URL</label>
                        <input type="url" name="image_url" id="image_url" class="form-control" placeholder="https://..." value="<?php echo h($_POST['image_url'] ?? ''); ?>">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_treasure_hunt" id="is_treasure_hunt" <?php echo isset($_POST['is_treasure_hunt']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_treasure_hunt">Markera som Treasure Hunt</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_super_treasure" id="is_super_treasure" <?php echo isset($_POST['is_super_treasure']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_super_treasure">Markera som Super Treasure</label>
                    </div>
                    <div>
                        <button class="btn btn-success" type="submit">Lägg till custom</button>
                        <a href="catalog.php" class="btn btn-outline-secondary">Tillbaka</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
