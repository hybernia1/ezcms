<?php

declare(strict_types=1);

namespace Core\Translation;

use RuntimeException;

/**
 * Simple filesystem backed translator inspired by WordPress style translations.
 *
 * Translation catalogues are loaded from PHP files that return associative arrays
 * containing string identifiers mapped to their translated value. Files are
 * expected inside `inc/languages` and named by locale (e.g. `EN.php`,
 * `CS.php`, `PL.php`).
 */
final class Translator
{
    private const DEFAULT_DIRECTORY = __DIR__ . '/../../../languages';

    private string $locale;

    private string $fallbackLocale;

    private string $directory;

    /**
     * @var array<string, array<string, string>>
     */
    private array $catalogues = [];

    private static ?self $globalInstance = null;

    public function __construct(
        ?string $directory = null,
        string $defaultLocale = 'EN',
        ?string $fallbackLocale = null,
    ) {
        $this->directory = rtrim($directory ?? self::DEFAULT_DIRECTORY, '/');
        $this->setLocale($defaultLocale);
        $this->fallbackLocale = $this->normaliseLocale($fallbackLocale ?? $defaultLocale);
    }

    public static function createDefault(): self
    {
        return new self();
    }

    public static function setGlobal(self $translator): void
    {
        self::$globalInstance = $translator;
    }

    public static function getGlobal(): self
    {
        if (self::$globalInstance === null) {
            self::$globalInstance = self::createDefault();
        }

        return self::$globalInstance;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $this->normaliseLocale($locale);
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setFallbackLocale(string $locale): void
    {
        $this->fallbackLocale = $this->normaliseLocale($locale);
    }

    public function getFallbackLocale(): string
    {
        return $this->fallbackLocale;
    }

    public function has(string $key, ?string $locale = null): bool
    {
        $locale = $this->normaliseLocale($locale ?? $this->locale);
        $catalogue = $this->loadCatalogue($locale);

        if (array_key_exists($key, $catalogue)) {
            return true;
        }

        if ($locale === $this->fallbackLocale) {
            return false;
        }

        $fallbackCatalogue = $this->loadCatalogue($this->fallbackLocale);
        return array_key_exists($key, $fallbackCatalogue);
    }

    public function translate(string $key, array $parameters = [], ?string $locale = null): string
    {
        $locale = $this->normaliseLocale($locale ?? $this->locale);
        $catalogue = $this->loadCatalogue($locale);

        if (!array_key_exists($key, $catalogue)) {
            if ($locale !== $this->fallbackLocale) {
                $catalogue = $this->loadCatalogue($this->fallbackLocale);
            }
        }

        $message = $catalogue[$key] ?? $key;

        if ($parameters !== []) {
            $message = $this->formatMessage($message, $parameters);
        }

        return $message;
    }

    /**
     * Convenience wrapper for translate().
     */
    public function __invoke(string $key, array $parameters = [], ?string $locale = null): string
    {
        return $this->translate($key, $parameters, $locale);
    }

    /**
     * @return array<string, string>
     */
    public function getCatalogue(?string $locale = null): array
    {
        $locale = $this->normaliseLocale($locale ?? $this->locale);
        return $this->loadCatalogue($locale);
    }

    private function loadCatalogue(string $locale): array
    {
        if (isset($this->catalogues[$locale])) {
            return $this->catalogues[$locale];
        }

        $path = $this->directory . '/' . $locale . '.php';
        if (!is_file($path)) {
            $this->catalogues[$locale] = [];
            return $this->catalogues[$locale];
        }

        $catalogue = require $path;
        if (!is_array($catalogue)) {
            throw new RuntimeException(sprintf(
                'Soubor s překlady "%s" musí vracet pole, vrácen "%s".',
                $path,
                get_debug_type($catalogue)
            ));
        }

        $normalised = [];
        foreach ($catalogue as $key => $message) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            $normalised[$key] = (string) $message;
        }

        return $this->catalogues[$locale] = $normalised;
    }

    private function normaliseLocale(string $locale): string
    {
        $locale = str_replace(['-', ' '], '_', trim($locale));
        if ($locale === '') {
            throw new RuntimeException('Locale nesmí být prázdná.');
        }

        return strtoupper($locale);
    }

    /**
     * @param array<int|string, mixed> $parameters
     */
    private function formatMessage(string $message, array $parameters): string
    {
        if ($parameters === []) {
            return $message;
        }

        $keys = array_keys($parameters);
        $isAssociative = $keys !== range(0, count($parameters) - 1);

        if ($isAssociative) {
            $replacements = [];
            foreach ($parameters as $name => $value) {
                if (!is_scalar($value) && !method_exists($value, '__toString')) {
                    continue;
                }

                $value = (string) $value;
                $replacements['{' . $name . '}'] = $value;
                $replacements[':' . $name] = $value;
            }

            if ($replacements === []) {
                return $message;
            }

            return strtr($message, $replacements);
        }

        try {
            return vsprintf($message, array_map(static fn ($value): string => (string) $value, $parameters));
        } catch (\ValueError) {
            return $message;
        }
    }
}

require_once __DIR__ . '/functions.php';
