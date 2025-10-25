<?php
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect_with_message(string $location, string $type, string $text): void
{
    $_SESSION['flash'] = ['type' => $type, 'text' => $text];
    header('Location: ' . $location);
    exit();
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function parse_int(?string $value, int $default = 0): int
{
    if ($value === null) {
        return $default;
    }
    return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int)$value : $default;
}

function parse_bool($value): bool
{
    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
}
?>
