<?php

declare(strict_types=1);

namespace Front\AppBuilder;

use Core\App\Application;
use Core\AppBuilder\BaseBuilder;

final class FrontAppBuilder extends BaseBuilder
{
    public function build(?bool $debug = null): Application
    {
        return parent::build($debug);
    }
}
