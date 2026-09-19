<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Session;

Session::start();
Auth::logout();

header('Location: login.php');
exit;
