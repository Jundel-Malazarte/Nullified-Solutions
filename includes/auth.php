<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function redirect_to($path)
{
    header('Location: ' . $path);
    exit;
}

function require_login()
{
    if (empty($_SESSION['user_id'])) {
        redirect_to('login.php');
    }
}

function logout_user()
{
    $_SESSION = [];
    if (session_id() !== '') {
        session_destroy();
    }
}
