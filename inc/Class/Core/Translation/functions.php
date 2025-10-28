<?php

declare(strict_types=1);

use Core\Translation\Translator;

if (!function_exists('__')) {
    /**
     * Retrieve a translated string.
     */
    function __(string $key, array $parameters = [], ?string $locale = null): string
    {
        return Translator::getGlobal()->translate($key, $parameters, $locale);
    }
}

if (!function_exists('_e')) {
    /**
     * Echo a translated string.
     */
    function _e(string $key, array $parameters = [], ?string $locale = null): void
    {
        echo __($key, $parameters, $locale);
    }
}

if (!function_exists('__f')) {
    /**
     * Format a translated string using vsprintf semantics.
     *
     * @param mixed ...$args
     */
    function __f(string $key, ...$args): string
    {
        $translation = Translator::getGlobal()->translate($key);

        if ($args === []) {
            return $translation;
        }

        try {
            return vsprintf($translation, $args);
        } catch (ValueError) {
            return $translation;
        }
    }
}
