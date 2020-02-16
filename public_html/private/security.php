<?php
session_start();

require_once __DIR__ . '/model/Partner.class.php';
require_once __DIR__ . '/model/Admin.class.php';

define('SESSION_USER_KEY', 'USER');
define('IS_ADMIN', 'IS_ADMIN');

function get_clean_obtain($paramName)
{
    $rawValue = $_GET[$paramName] ?? null;
    $HTMLFilteredValue = htmlspecialchars($rawValue);
    return $HTMLFilteredValue;
}

function post_clean_obtain($paramName)
{
    $rawValue = $_POST[$paramName] ?? null;
    $HTMLFilteredValue = htmlspecialchars($rawValue);
    return $HTMLFilteredValue;
}


function is_authenticated($asAdmin = false)
{
    if($asAdmin) return !empty($_SESSION[SESSION_USER_KEY]) && get_class($_SESSION[SESSION_USER_KEY]) === 'Admin';
    return !empty($_SESSION[SESSION_USER_KEY]);
}

function login(Partner $user)
{
    $_SESSION[SESSION_USER_KEY] = $user;
}

function loginAdmin(Admin $admin)
{
    $_SESSION[SESSION_USER_KEY] = $admin;
}

function logout()
{
    unset($_SESSION[SESSION_USER_KEY]);
    session_destroy();
}

function getCurrentLoggedUser()
{
    return $_SESSION[SESSION_USER_KEY];
}