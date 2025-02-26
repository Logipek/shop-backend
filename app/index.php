<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';

use App\Router;
use App\Controllers\{User, Auth, Product, Order};

$controllers = [
    User::class,
    Auth::class,
    Product::class,
    Order::class,
];

$router = new Router();
$router->registerControllers($controllers);
$router->run();