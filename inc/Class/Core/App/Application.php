<?php

declare(strict_types=1);

namespace Core\App;

use Core\Http\Request;
use Core\Http\Response;
use Core\Routing\Router;
use RuntimeException;
use Throwable;

final class Application
{
    public function __construct(
        private readonly Router $router,
        private readonly Container $container,
        private readonly bool $debug = false,
    ) {
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function handle(Request $request): Response
    {
        $this->container->setInstance('request', $request);

        try {
            $match = $this->router->match($request->getMethod(), $request->getPath());
            if ($match === null) {
                return Response::html('<h1>404</h1><p>Stránka nenalezena.</p>', 404);
            }

            $request = $request
                ->withAttribute('route', $match->getRoute())
                ->withAttribute('route_params', $match->getParameters());

            $this->container->setInstance('request', $request);
            $this->container->setInstance('route_match', $match);

            $result = ($match->getHandler())($match);

            return $this->normalizeResponse($result);
        } catch (Throwable $exception) {
            if ($this->debug) {
                throw $exception;
            }

            return Response::html('<h1>500</h1><p>Došlo k neočekávané chybě.</p>', 500);
        }
    }

    public function run(?Request $request = null): void
    {
        $request ??= Request::fromGlobals();
        $response = $this->handle($request);
        $response->send();
    }

    private function normalizeResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_string($result)) {
            return Response::html($result);
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        throw new RuntimeException('Handler musí vracet instanci Response, string nebo array.');
    }
}
