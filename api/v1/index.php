<?php
session_start();

// Set content type to JSON
header("Content-Type: application/json");

// Autoload Core Libraries
spl_autoload_register(function ($className) {
    // Look in the core directory
    $corePath = '../../core/' . $className . '.php';
    if (file_exists($corePath)) {
        require_once $corePath;
        return;
    }

    // Look in the controllers directory
    $controllerPath = 'controllers/' . $className . '.php';
    if (file_exists($controllerPath)) {
        require_once $controllerPath;
        return;
    }
});

// Init Router
$router = new Router();

// Define API routes
// User routes
$router->add('users', 'UserController::index');
$router->add('users/register', 'UserController::register', 'POST');
$router->add('users/login', 'UserController::login', 'POST');
$router->add('users/logout', 'UserController::logout', 'POST');

// Episode routes
$router->add('episodes', 'EpisodeController::index');
$router->add('episodes/(\d+)', 'EpisodeController::show');
$router->add('episodes', 'EpisodeController::create', 'POST');
$router->add('episodes/(\d+)', 'EpisodeController::update', 'PUT');
$router->add('episodes/(\d+)', 'EpisodeController::delete', 'DELETE');

// Dispatch the router
$router->dispatch(isset($_GET['url']) ? $_GET['url'] : '');
