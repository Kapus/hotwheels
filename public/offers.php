<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/car_repository.php';
require_once __DIR__ . '/../includes/offer_repository.php';

require_login();

$user = current_user();
$userId = (int)$user['id'];
$collection = get_user_collection($userId);
$partners = get_potential_trade_partners($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $toUser = parse_int($_POST['to_user'] ?? null);
        $carId = parse_int($_POST['car_id'] ?? null);
        $type = $_POST['type'] ?? 'buy';
        $message = trim($_POST['message'] ?? '');

        if ($toUser === $userId) {
            redirect_with_message('offers.php', 'danger', 'Du kan inte skicka förslag till dig själv.');
        }

        if ($toUser <= 0 || $carId <= 0) {
            redirect_with_message('offers.php', 'danger', 'Välj mottagare och bil.');
        }

        if (!in_array($type, ['buy', 'trade'], true)) {
            $type = 'buy';
        }

        $ownsSelectedCar = array_filter($collection, fn($item) => (int)$item['id'] === $carId);
        if (!$ownsSelectedCar) {
            redirect_with_message('offers.php', 'danger', 'Du kan bara erbjuda bilar som finns i din samling.');
        }

        create_offer($userId, $toUser, $carId, $type, $message);
        redirect_with_message('offers.php', 'success', 'Förslaget skickades.');
    }

    if ($action === 'update') {
        $offerId = parse_int($_POST['offer_id'] ?? null);
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['accepted', 'declined'], true)) {
            redirect_with_message('offers.php', 'danger', 'Ogiltig status.');
        }
        if (update_offer_status($offerId, $userId, $status)) {
            redirect_with_message('offers.php', 'success', 'Status uppdaterades.');
        }
        redirect_with_message('offers.php', 'danger', 'Kunde inte uppdatera status.');
    }
}

$collection = get_user_collection($userId);
$partners = get_potential_trade_partners($userId);
$offers = get_offers_for_user($userId);

$pageTitle = 'Förslag';
include __DIR__ . '/../partials/header.php';
?>
<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0">Skicka nytt förslag</h6>
            </div>
            <div class="card-body">
                <?php if (empty($collection)): ?>
                    <div class="alert alert-info">Lägg till bilar i din samling för att kunna skicka förslag.</div>
                <?php else: ?>
                    <form method="post" class="vstack gap-3">
                        <input type="hidden" name="action" value="create">
                        <div>
                            <label class="form-label" for="to_user">Till användare</label>
                            <select class="form-select" name="to_user" id="to_user" required>
                                <option value="">Välj användare</option>
                                <?php foreach ($partners as $partner): ?>
                                    <option value="<?php echo (int)$partner['id']; ?>"><?php echo h($partner['username']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="car_id">Bil</label>
                            <select class="form-select" name="car_id" id="car_id" required>
                                <option value="">Välj bil</option>
                                <?php foreach ($collection as $item): ?>
                                    <option value="<?php echo (int)$item['id']; ?>"><?php echo h($item['name']); ?> (<?php echo h($item['series']); ?>, <?php echo (int)$item['year']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="type">Typ</label>
                            <select class="form-select" name="type" id="type">
                                <option value="buy">Köp</option>
                                <option value="trade">Byte</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="message">Meddelande</label>
                            <textarea class="form-control" name="message" id="message" rows="3" placeholder="Hej! Jag är intresserad av..."></textarea>
                        </div>
                        <div>
                            <button class="btn btn-primary" type="submit">Skicka förslag</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0">Mina förslag</h6>
            </div>
            <div class="card-body">
                <?php if (empty($offers)): ?>
                    <p class="text-muted">Inga förslag ännu.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($offers as $offer): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div class="d-flex gap-3">
                                        <?php if (!empty($offer['image_url'])): ?>
                                            <img src="<?php echo h($offer['image_url']); ?>" alt="<?php echo h($offer['car_name']); ?>" width="64" height="64" class="rounded" style="object-fit:cover;">
                                        <?php endif; ?>
                                        <div>
                                            <h6 class="mb-1"><?php echo h($offer['car_name']); ?> · <?php echo strtoupper($offer['type']); ?></h6>
                                            <small class="text-muted">Från: <?php echo h($offer['from_username']); ?> · Till: <?php echo h($offer['to_username']); ?></small>
                                            <?php if (!empty($offer['message'])): ?>
                                                <p class="mt-2 mb-0"><?php echo nl2br(h($offer['message'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="badge bg-<?php echo $offer['status'] === 'pending' ? 'warning text-dark' : ($offer['status'] === 'accepted' ? 'success' : 'secondary'); ?> text-uppercase"><?php echo h($offer['status']); ?></span>
                                </div>
                                <?php if ($offer['to_user'] === $userId && $offer['status'] === 'pending'): ?>
                                    <div class="mt-3 d-flex gap-2">
                                        <form method="post">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                            <input type="hidden" name="status" value="accepted">
                                            <button class="btn btn-success btn-sm" type="submit">Acceptera</button>
                                        </form>
                                        <form method="post">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                            <input type="hidden" name="status" value="declined">
                                            <button class="btn btn-outline-secondary btn-sm" type="submit">Avslå</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
