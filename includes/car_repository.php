<?php
require_once __DIR__ . '/db.php';

const SEARCH_RESULT_LIMIT = 200;

function get_car_filters(): array
{
    $pdo = get_db_connection();
    $years = $pdo->query('SELECT DISTINCT year FROM cars ORDER BY year ASC')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $series = $pdo->query('SELECT DISTINCT series FROM cars ORDER BY series ASC')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    return ['years' => $years, 'series' => $series];
}

function search_cars(array $criteria, int $limit = SEARCH_RESULT_LIMIT): array
{
    $pdo = get_db_connection();
    $sql = 'SELECT * FROM cars WHERE 1=1';
    $params = [];

    if (!empty($criteria['q'])) {
        $sql .= ' AND (name LIKE :q OR collector_number LIKE :q)';
        $params['q'] = '%' . $criteria['q'] . '%';
    }

    if (!empty($criteria['year'])) {
        $sql .= ' AND year = :year';
        $params['year'] = (int)$criteria['year'];
    }

    if (!empty($criteria['series'])) {
        $sql .= ' AND series = :series';
        $params['series'] = $criteria['series'];
    }

    if (isset($criteria['treasure'])) {
        if ($criteria['treasure'] === 'regular') {
            $sql .= ' AND is_treasure_hunt = 1';
        } elseif ($criteria['treasure'] === 'super') {
            $sql .= ' AND is_super_treasure = 1';
        }
    }

    $sort = $criteria['sort'] ?? 'year_desc';
    switch ($sort) {
        case 'year_asc':
            $sql .= ' ORDER BY year ASC, name ASC';
            break;
        case 'name_asc':
            $sql .= ' ORDER BY name ASC';
            break;
        case 'name_desc':
            $sql .= ' ORDER BY name DESC';
            break;
        case 'collector_asc':
            $sql .= ' ORDER BY CAST(collector_number AS UNSIGNED), collector_number ASC, year DESC';
            break;
        case 'collector_desc':
            $sql .= ' ORDER BY CAST(collector_number AS UNSIGNED) DESC, collector_number DESC, year DESC';
            break;
        default:
            $sql .= ' ORDER BY year DESC, name ASC';
    }

    if ($limit > 0) {
        $sql .= ' LIMIT :limit';
    }

    $stmt = $pdo->prepare($sql);
    foreach ($params as $param => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue(':' . $param, $value, $type);
    }

    if ($limit > 0) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }

    $stmt->execute();
    return $stmt->fetchAll();
}

function upsert_user_car(int $user_id, int $car_id, int $quantity, string $condition, string $notes): void
{
    $pdo = get_db_connection();
    $sql = 'INSERT INTO user_cars (user_id, car_id, quantity, `condition`, notes) VALUES (:user_id, :car_id, :quantity, :condition, :notes)
        ON DUPLICATE KEY UPDATE quantity = :quantity_update, `condition` = :condition_update, notes = :notes_update';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'user_id' => $user_id,
        'car_id' => $car_id,
        'quantity' => $quantity,
        'condition' => $condition,
        'notes' => $notes,
        'quantity_update' => $quantity,
        'condition_update' => $condition,
        'notes_update' => $notes,
    ]);
}

function get_user_collection(int $user_id): array
{
    $sql = 'SELECT c.*, uc.quantity, uc.condition, uc.notes
            FROM user_cars uc
            JOIN cars c ON c.id = uc.car_id
            WHERE uc.user_id = :user_id
            ORDER BY c.year DESC, c.name ASC';
    $stmt = get_db_connection()->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchAll();
}

function delete_user_car(int $user_id, int $car_id): void
{
    $sql = 'DELETE FROM user_cars WHERE user_id = :user_id AND car_id = :car_id';
    $stmt = get_db_connection()->prepare($sql);
    $stmt->execute([
        'user_id' => $user_id,
        'car_id' => $car_id,
    ]);
}

function get_user_car_map(int $user_id): array
{
    $sql = 'SELECT car_id, quantity, `condition`, notes FROM user_cars WHERE user_id = :user_id';
    $stmt = get_db_connection()->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $map[(int)$row['car_id']] = $row;
    }
    return $map;
}

function get_collection_stats(int $user_id): array
{
    $pdo = get_db_connection();

    $totalsql = 'SELECT COUNT(*) AS distinct_count, COALESCE(SUM(quantity), 0) AS total_quantity FROM user_cars WHERE user_id = :user_id';
    $stmt = $pdo->prepare($totalsql);
    $stmt->execute(['user_id' => $user_id]);
    $basic = $stmt->fetch() ?: ['distinct_count' => 0, 'total_quantity' => 0];

    $seriesSql = 'SELECT c.series, COUNT(*) AS cnt FROM user_cars uc JOIN cars c ON c.id = uc.car_id
                  WHERE uc.user_id = :user_id GROUP BY c.series ORDER BY cnt DESC';
    $stmt = $pdo->prepare($seriesSql);
    $stmt->execute(['user_id' => $user_id]);
    $bySeries = $stmt->fetchAll();

    $yearSql = 'SELECT c.year, COUNT(*) AS cnt FROM user_cars uc JOIN cars c ON c.id = uc.car_id
                WHERE uc.user_id = :user_id GROUP BY c.year ORDER BY c.year ASC';
    $stmt = $pdo->prepare($yearSql);
    $stmt->execute(['user_id' => $user_id]);
    $byYear = $stmt->fetchAll();

    return [
        'basic' => $basic,
        'by_series' => $bySeries,
        'by_year' => $byYear,
    ];
}

function create_custom_car(int $user_id, string $name, int $year, string $series, ?string $collectorNumber, bool $treasure, bool $super, string $image_url): int
{
    $pdo = get_db_connection();
    $sql = 'INSERT INTO cars (name, year, series, collector_number, is_treasure_hunt, is_super_treasure, image_url) VALUES
            (:name, :year, :series, :collector, :treasure, :super, :image)
            ON DUPLICATE KEY UPDATE series = VALUES(series), collector_number = VALUES(collector_number),
            is_treasure_hunt = VALUES(is_treasure_hunt), is_super_treasure = VALUES(is_super_treasure), image_url = VALUES(image_url)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'name' => $name,
        'year' => $year,
        'series' => $series,
        'collector' => $collectorNumber ?: null,
        'treasure' => $treasure ? 1 : 0,
        'super' => $super ? 1 : 0,
        'image' => $image_url,
    ]);
    $carId = (int)$pdo->lastInsertId();
    if ($carId === 0) {
        $stmt = $pdo->prepare('SELECT id FROM cars WHERE name = :name AND year = :year LIMIT 1');
        $stmt->execute(['name' => $name, 'year' => $year]);
        $carId = (int)$stmt->fetchColumn();
    }
    upsert_user_car($user_id, $carId, 1, 'custom', 'Custom build');
    return $carId;
}
?>
