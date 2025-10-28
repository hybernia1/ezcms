<?php

declare(strict_types=1);

namespace Admin\Controller;

use Core\Http\Request;
use Core\Http\Response;
use Core\View\TemplateRenderer;

final class DashboardController
{
    public function __construct(
        private readonly TemplateRenderer $templates,
        private readonly string $appName,
    ) {
    }

    public function dashboard(Request $request): Response
    {
        $html = $this->templates->render('dashboard/index', [
            'appName' => $this->appName,
        ]);

        return Response::html($html);
    }
}
