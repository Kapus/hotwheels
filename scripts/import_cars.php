<?php
// Import Hot Wheels cars from JSON file into MySQL database.
// Supports CLI usage:   php import_cars.php [file.json]
// Or browser usage:    import_cars.php?file=file.json

const DEFAULT_IMPORT_FILE = __DIR__ . '/hotwheels_all_years.json';
const DB_DSN = 'mysql:host=localhost;dbname=hotwheels;charset=utf8mb4';
const DB_USER = 'root';
const DB_PASS = '';

function usage(): void
{
    echo "Usage:\n  CLI    : php import_cars.php [file.json]\n  Browser: import_cars.php?file=file.json\n";
}

function resolve_file(array $argv): string
{
    if (PHP_SAPI === 'cli') {
        $path = $argv[1] ?? DEFAULT_IMPORT_FILE;
        if (in_array($path, ['-h', '--help'], true)) {
            usage();
            exit(0);
        }
        return $path;
    }

    $fileParam = $_GET['file'] ?? '';
    if ($fileParam === '') {
        return DEFAULT_IMPORT_FILE;
    }

    return str_starts_with($fileParam, DIRECTORY_SEPARATOR)
        ? $fileParam
        : __DIR__ . DIRECTORY_SEPARATOR . $fileParam;
}

function load_json(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException('File not found: ' . $path);
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        throw new RuntimeException('Unable to read file: ' . $path);
    }

    $data = json_decode($contents, true);
    if (!is_array($data)) {
        throw new RuntimeException('Invalid JSON structure in: ' . $path);
    }

    return $data;
}

function get_connection(): PDO
{
    try {
        return new PDO(DB_DSN, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        throw new RuntimeException('Database connection failed: ' . $e->getMessage());
    }
}

function import_cars(array $cars, PDO $pdo): int
{
    if (empty($cars)) {
        throw new RuntimeException('JSON file does not contain any items.');
    }

    $sql = 'INSERT INTO cars (name, year, series, collector_number, is_treasure_hunt, is_super_treasure, image_url)
            VALUES (:name, :year, :series, :collector_number, :treasure, :super, :image_url)
            ON DUPLICATE KEY UPDATE series = VALUES(series), collector_number = VALUES(collector_number),
                is_treasure_hunt = VALUES(is_treasure_hunt), is_super_treasure = VALUES(is_super_treasure), image_url = VALUES(image_url)';
    $stmt = $pdo->prepare($sql);

    $count = 0;
    foreach ($cars as $idx => $car) {
        if (!is_array($car)) {
            throw new RuntimeException('Invalid item at index ' . $idx);
        }

        foreach (['name', 'year'] as $required) {
            if (!isset($car[$required]) || $car[$required] === '') {
                throw new RuntimeException("Missing required field '{$required}' at index {$idx}.");
            }
        }

        $stmt->execute([
            'name' => (string)$car['name'],
            'year' => (int)$car['year'],
            'series' => $car['series'] !== null && $car['series'] !== '' ? (string)$car['series'] : 'Okänd serie',
            'collector_number' => $car['collector_number'] !== null && $car['collector_number'] !== '' ? (string)$car['collector_number'] : null,
            'treasure' => !empty($car['is_treasure_hunt']) ? 1 : 0,
            'super' => !empty($car['is_super_treasure']) ? 1 : 0,
            'image_url' => $car['image_url'] ?? '',
        ]);
        $count++;
    }

    return $count;
}

try {
    $file = resolve_file($argv ?? []);
    $cars = load_json($file);
    $pdo = get_connection();
    $imported = import_cars($cars, $pdo);
    $message = "Imported {$imported} cars from {$file}.";

    if (PHP_SAPI === 'cli') {
        echo $message . PHP_EOL;
    } else {
        echo '<div style="font-family:monospace; padding:1rem;">' . htmlspecialchars($message, ENT_QUOTES) . '</div>';
    }
} catch (Throwable $e) {
    $error = 'Import failed: ' . $e->getMessage();
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $error . PHP_EOL);
        exit(1);
    } else {
        http_response_code(500);
        echo '<div style="font-family:monospace; color:#b00020; padding:1rem;">' . htmlspecialchars($error, ENT_QUOTES) . '</div>';
    }
}
