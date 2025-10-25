<?php
require_once __DIR__ . '/db.php';

function create_offer(int $from_user, int $to_user, int $car_id, string $type, string $message): void
{
    $sql = 'INSERT INTO offers (from_user, to_user, car_id, type, message, status) VALUES (:from_user, :to_user, :car_id, :type, :message, "pending")';
    $stmt = get_db_connection()->prepare($sql);
    $stmt->execute([
        'from_user' => $from_user,
        'to_user' => $to_user,
        'car_id' => $car_id,
        'type' => $type,
        'message' => $message,
    ]);
}

function update_offer_status(int $offer_id, int $user_id, string $status): bool
{
    $sql = 'UPDATE offers SET status = :status WHERE id = :id AND (to_user = :user_id OR from_user = :user_id)';
    $stmt = get_db_connection()->prepare($sql);
    $stmt->execute([
        'status' => $status,
        'id' => $offer_id,
        'user_id' => $user_id,
    ]);
    return $stmt->rowCount() > 0;
}

function get_offers_for_user(int $user_id): array
{
    $sql = 'SELECT o.*, c.name AS car_name, c.image_url,
                   u1.username AS from_username, u2.username AS to_username
            FROM offers o
            JOIN cars c ON c.id = o.car_id
            JOIN users u1 ON u1.id = o.from_user
            JOIN users u2 ON u2.id = o.to_user
        WHERE o.from_user = :user_id OR o.to_user = :user_id
        ORDER BY o.created_at DESC';
    $stmt = get_db_connection()->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchAll();
}

function get_potential_trade_partners(int $user_id): array
{
    $sql = 'SELECT id, username FROM users WHERE id <> :id ORDER BY username ASC';
    $stmt = get_db_connection()->prepare($sql);
    $stmt->execute(['id' => $user_id]);
    return $stmt->fetchAll();
}

?>
