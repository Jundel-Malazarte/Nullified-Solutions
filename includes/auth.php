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

function require_admin()
{
    if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        redirect_to('../dashboard.php');
    }
}

function is_valid_email($email)
{
    return is_string($email) && filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
}

function is_valid_full_name($fullName)
{
    $value = trim((string) $fullName);

    if ($value === '' || strlen($value) < 2) {
        return false;
    }

    return preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ]+(?:[\'\-. ][A-Za-zÀ-ÖØ-öø-ÿ]+)*$/u', $value) === 1;
}

function is_strong_password($password)
{
    return is_string($password)
        && preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password) === 1;
}

function logout_user()
{
    $_SESSION = [];
    if (session_id() !== '') {
        session_destroy();
    }
}
