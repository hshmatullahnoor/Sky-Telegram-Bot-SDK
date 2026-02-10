<?php

/**
 * Front Controller
 *
 * All requests are routed through this file via .htaccess.
 */

require __DIR__ . '/../config/bootstrap.php';

use Classes\Router;

$router = new Router();

// Load route definitions
require __DIR__ . '/../routes/web.php';

// Dispatch the request
$router->dispatch();
