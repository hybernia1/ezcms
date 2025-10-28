<?php

declare(strict_types=1);

namespace Admin\AppBuilder;

use Admin\Controller\AuthController;
use Admin\Controller\DashboardController;
use Core\App\Application;
use Core\App\Container;
use Core\AppBuilder\BaseBuilder;
use Core\Routing\RouteMatch;
use Core\Routing\Router;
use Core\View\TemplateRenderer;
use RuntimeException;

final class AdminAppBuilder extends BaseBuilder
{
    private bool $configured = false;

    public function __construct()
    {
        parent::__construct(new Router('/admin'));
    }

    public function build(?bool $debug = null): Application
    {
        $this->configure();

        return parent::build($debug);
    }

    private function configure(): void
    {
        if ($this->configured) {
            return;
        }

        $this->configured = true;

        $this->withConfig([
            'app' => [
                'name' => 'EZCMS Admin',
            ],
            'paths' => [
                'templates' => BASE_DIR . '/admin/templates',
            ],
        ]);

        $this->withService('view', static function (Container $container): TemplateRenderer {
            $basePath = $container->getParameter('paths.templates');
            if (!is_string($basePath) || $basePath === '') {
                throw new RuntimeException('Nebyla nastavena cesta k šablonám administrace.');
            }

            return new TemplateRenderer($basePath);
        });

        $this->withService('admin.auth_controller', static function (Container $container): AuthController {
            $templates = $container->get('view');
            $appName = (string) $container->getParameter('app.name', 'Administrace');

            return new AuthController($templates, $appName);
        });

        $this->withService('admin.dashboard_controller', static function (Container $container): DashboardController {
            $templates = $container->get('view');
            $appName = (string) $container->getParameter('app.name', 'Administrace');

            return new DashboardController($templates, $appName);
        });

        $this->afterBuild(static function (Application $app, Container $container): void {
            $router = $app->getRouter();

            $router->get(
                static function (RouteMatch $match) use ($container) {
                    $request = $container->get('request');

                    return $container->get('admin.auth_controller')->showLogin($request);
                },
                '/login',
                'admin.login',
            );

            $router->post(
                static function (RouteMatch $match) use ($container) {
                    $request = $container->get('request');

                    return $container->get('admin.auth_controller')->handleLogin($request);
                },
                '/login',
                'admin.login.submit',
            );

            $dashboardHandler = static function (RouteMatch $match) use ($container) {
                $request = $container->get('request');

                return $container->get('admin.dashboard_controller')->dashboard($request);
            };

            $router->get($dashboardHandler, '/', 'admin.dashboard');
            $router->get($dashboardHandler, '/dashboard', 'admin.dashboard.home');
        });
    }
}
