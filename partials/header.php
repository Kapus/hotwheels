<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$user = current_user();
$title = $pageTitle ?? APP_NAME;
?>
<!doctype html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo h($title); ?> | <?php echo h(APP_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-light">
<div class="d-flex" id="layout">
    <nav class="bg-dark text-white sidebar p-3" id="sidebar">
        <div class="d-flex align-items-center mb-4">
            <i class="bi bi-speedometer2 fs-2 me-2"></i>
            <span class="fs-4 fw-semibold"><?php echo h(APP_NAME); ?></span>
        </div>
        <ul class="nav nav-pills flex-column gap-2">
            <li class="nav-item"><a class="nav-link text-white" href="index.php"><i class="bi bi-grid me-2"></i>Dashboard</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="catalog.php"><i class="bi bi-collection me-2"></i>Katalog</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="profile.php"><i class="bi bi-person-badge me-2"></i>Min samling</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="offers.php"><i class="bi bi-arrows-exchange me-2"></i>Förslag</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="add_custom.php"><i class="bi bi-brush me-2"></i>Lägg till custom</a></li>
            <?php if (is_admin()): ?>
                <li class="nav-item"><a class="nav-link text-white" href="admin_import.php"><i class="bi bi-cloud-upload me-2"></i>Admin import</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="flex-grow-1 d-flex flex-column min-vh-100">
        <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
            <div class="container-fluid">
                <button class="btn btn-outline-secondary d-lg-none" id="toggleSidebar"><i class="bi bi-list"></i></button>
                <span class="navbar-brand mb-0 h1"><?php echo h($title); ?></span>
                <div class="d-flex align-items-center gap-3">
                    <?php if ($user): ?>
                        <span><i class="bi bi-person-circle me-1"></i><?php echo h($user['username']); ?></span>
                        <a class="btn btn-outline-danger btn-sm" href="logout.php">Logga ut</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
        <main class="container-fluid py-4 flex-grow-1">
            <?php if ($flash = get_flash()): ?>
                <div class="alert alert-<?php echo h($flash['type']); ?> alert-dismissible fade show" role="alert">
                    <?php echo h($flash['text']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
