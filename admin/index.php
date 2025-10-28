<?php

declare(strict_types=1);

use Admin\AppBuilder\AdminAppBuilder;
use Core\Http\Request;

require_once __DIR__ . '/../load.php';

$builder = new AdminAppBuilder();
$app = $builder->build();
$app->run(Request::fromGlobals());
