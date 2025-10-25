<?php
// Update these constants to match your MySQL credentials.
const DB_HOST = 'localhost';
const DB_NAME = 'hotwheels';
const DB_USER = 'root';
const DB_PASS = '';
const APP_NAME = 'Hot Wheels Collector';
const ADMIN_USERS = ['admin']; // Lägg till användarnamn som ska ha adminrättigheter

// Start session for every request.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
?>
