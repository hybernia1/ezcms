<?php

declare(strict_types=1);

namespace Core\View;

use RuntimeException;

final class TemplateRenderer
{
    public function __construct(private readonly string $basePath)
    {
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function render(string $template, array $context = []): string
    {
        $path = $this->resolveTemplate($template);

        extract($context, EXTR_SKIP);

        ob_start();
        try {
            /** @psalm-suppress UnresolvableInclude */
            include $path;
        } finally {
            $output = ob_get_clean();
        }

        return (string) $output;
    }

    private function resolveTemplate(string $template): string
    {
        $template = str_replace(['\\', '..'], ['/', ''], $template);
        if (!str_ends_with($template, '.php')) {
            $template .= '.php';
        }

        $path = rtrim($this->basePath, '/\\') . '/' . ltrim($template, '/');

        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Šablona "%s" nebyla nalezena (%s).', $template, $path));
        }

        return $path;
    }
}
