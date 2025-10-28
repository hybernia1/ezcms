<?php

declare(strict_types=1);

namespace Admin\Controller;

use Core\Http\Request;
use Core\Http\Response;
use Core\View\TemplateRenderer;

final class AuthController
{
    public function __construct(
        private readonly TemplateRenderer $templates,
        private readonly string $appName,
    ) {
    }

    public function showLogin(Request $request): Response
    {
        $html = $this->templates->render('auth/login', [
            'appName' => $this->appName,
            'error' => $request->getAttribute('auth_error'),
            'lastUsername' => $request->getAttribute('auth_last_username', ''),
        ]);

        return Response::html($html);
    }

    public function handleLogin(Request $request): Response
    {
        $data = $request->getParsedBody();
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($username === '' || $password === '') {
            return $this->invalidCredentials($username, 'Vyplňte prosím přihlašovací údaje.');
        }

        if ($username === 'admin' && $password === 'admin') {
            return Response::redirect('/admin/dashboard');
        }

        return $this->invalidCredentials($username, 'Neplatné přihlašovací údaje.');
    }

    private function invalidCredentials(string $username, string $message): Response
    {
        $html = $this->templates->render('auth/login', [
            'appName' => $this->appName,
            'error' => $message,
            'lastUsername' => $username,
        ]);

        return Response::html($html, 401);
    }
}
