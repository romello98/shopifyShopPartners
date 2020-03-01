<?php

require_once __DIR__ . '/private/security.php';

$isAdmin = get_clean_obtain('admin') ?? null;

if(is_authenticated())
{
    logout();
}

if($isAdmin == null)
    header('Location: /login.php');
else
    header('Location: /admin/');