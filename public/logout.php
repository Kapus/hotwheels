<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

if (current_user()) {
    logout_user();
}
redirect_with_message('login.php', 'info', 'Du är nu utloggad.');
?>
