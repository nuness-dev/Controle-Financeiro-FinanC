<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Session;

Session::start();

header('Location: ' . (Auth::check() ? 'dashboard.php' : 'login.php'));
exit;
