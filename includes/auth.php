<?php
require_once __DIR__ . '/db.php';

function find_user_by_username(string $username): ?array
{
    $sql = 'SELECT * FROM users WHERE username = :username LIMIT 1';
    $stmt = get_db_connection()->prepare($sql);
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function find_user_by_email(string $email): ?array
{
    $sql = 'SELECT * FROM users WHERE email = :email LIMIT 1';
    $stmt = get_db_connection()->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function find_user_by_id(int $id): ?array
{
    $sql = 'SELECT * FROM users WHERE id = :id LIMIT 1';
    $stmt = get_db_connection()->prepare($sql);
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function register_user(string $username, string $email, string $password): array
{
    $errors = [];
    if (!preg_match('/^[A-Za-z0-9_\-]{3,30}$/', $username)) {
        $errors[] = 'Användarnamnet måste vara 3-30 tecken och får bara innehålla bokstäver, siffror, bindestreck eller understreck.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Ogiltig e-postadress.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Lösenordet måste bestå av minst 6 tecken.';
    }

    if (find_user_by_username($username)) {
        $errors[] = 'Användarnamnet är redan upptaget.';
    }

    if (find_user_by_email($email)) {
        $errors[] = 'E-postadressen används redan.';
    }

    if (!empty($errors)) {
        return $errors;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $sql = 'INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)';
    $stmt = get_db_connection()->prepare($sql);
    $stmt->execute([
        'username' => $username,
        'email' => $email,
        'password_hash' => $hash,
    ]);

    return $errors;
}

function login_user(string $username, string $password): bool
{
    $user = find_user_by_username($username);
    if (!$user) {
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    $_SESSION['user_id'] = $user['id'];
    return true;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    static $cache = null;
    if ($cache) {
        return $cache;
    }

    $cache = find_user_by_id((int)$_SESSION['user_id']);
    return $cache ?: null;
}

function is_admin(): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }

    if (!defined('ADMIN_USERS') || !is_array(ADMIN_USERS)) {
        return false;
    }

    return in_array($user['username'], ADMIN_USERS, true) || in_array($user['email'], ADMIN_USERS, true);
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: login.php');
        exit();
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        header('HTTP/1.1 403 Forbidden');
        exit('Åtkomst nekad: administrationsrättigheter krävs.');
    }
}
?>
