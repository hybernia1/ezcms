<?php
declare(strict_types=1);

/**
 * Jednoduchý autoloader pro třídy v /inc/Class.
 *
 * - automaticky mapuje první úroveň namespace na shodně pojmenovaný adresář
 * - fallback pro legacy názvy tříd s podtržítky
 */

const BASE_DIR  = __DIR__;
const CLASS_DIR = __DIR__ . '/inc/Class';

spl_autoload_register(
    static function (string $class): void {
        $class = ltrim($class, '\\');
        if ($class === '') {
            return;
        }

        static $namespaceRoots = null;
        if ($namespaceRoots === null) {
            $namespaceRoots = [];
            if (is_dir(CLASS_DIR)) {
                $iterator = new \DirectoryIterator(CLASS_DIR);
                foreach ($iterator as $item) {
                    if ($item->isDir() && !$item->isDot()) {
                        $namespaceRoots[$item->getBasename() . '\\'] = $item->getPathname();
                    }
                }
            }
        }

        foreach ($namespaceRoots as $prefix => $directory) {
            if (str_starts_with($class, $prefix)) {
                $relative = substr($class, strlen($prefix));
                $path = $directory . '/' . str_replace('\\', '/', $relative) . '.php';

                if (is_file($path)) {
                    require_once $path;
                    return;
                }

                throw new \RuntimeException(sprintf(
                    'Třída "%s" nebyla nalezena v namespace "%s". Očekávaný soubor: %s',
                    $class,
                    rtrim($prefix, '\\'),
                    $path
                ));
            }
        }

        $relativePath = str_replace('\\', '/', $class) . '.php';
        $path = CLASS_DIR . '/' . $relativePath;
        if (is_file($path)) {
            require_once $path;
            return;
        }

        if (str_contains($class, '_')) {
            $legacyPath = CLASS_DIR . '/' . str_replace('_', '/', $class) . '.php';
            if (is_file($legacyPath)) {
                require_once $legacyPath;
                return;
            }
        }
    },
    prepend: true
);

