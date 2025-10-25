<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

if (current_user()) {
    header('Location: index.php');
    exit();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($password !== $passwordConfirm) {
        $errors[] = 'Lösenorden matchar inte.';
    }

    if (empty($errors)) {
        $errors = register_user($username, $email, $password);
    }

    if (empty($errors)) {
        redirect_with_message('login.php', 'success', 'Ditt konto är skapat. Logga in för att fortsätta.');
    }
}
?>
<!doctype html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrera | <?php echo h(APP_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-dark bg-gradient d-flex align-items-center" style="min-height:100vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-body p-4">
                    <h1 class="h3 text-center mb-4">Skapa konto</h1>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo h($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <form method="post" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Användarnamn</label>
                                <input type="text" name="username" id="username" class="form-control" required value="<?php echo h($_POST['username'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">E-post</label>
                                <input type="email" name="email" id="email" class="form-control" required value="<?php echo h($_POST['email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Lösenord</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirm" class="form-label">Upprepa lösenord</label>
                                <input type="password" name="password_confirm" id="password_confirm" class="form-control" required>
                            </div>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success">Registrera</button>
                        </div>
                    </form>
                    <p class="text-center mt-3 mb-0">Har du redan ett konto? <a href="login.php">Logga in</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
