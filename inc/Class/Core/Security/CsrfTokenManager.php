<?php

declare(strict_types=1);

namespace Core\Security;

use RuntimeException;

/**
 * Session backed CSRF token manager.
 */
final class CsrfTokenManager
{
    private string $sessionKey;

    private int $ttl;

    private int $maxTokens;

    public function __construct(string $sessionKey = '_csrf_tokens', int $ttl = 10800, int $maxTokens = 50)
    {
        if ($ttl < 0) {
            throw new RuntimeException('TTL CSRF tokenu nesmí být záporný.');
        }

        if ($maxTokens < 1) {
            throw new RuntimeException('Musí být uložen alespoň jeden CSRF token.');
        }

        $this->sessionKey = $sessionKey;
        $this->ttl = $ttl;
        $this->maxTokens = $maxTokens;
    }

    public function getToken(?string $id = null): string
    {
        $id = $this->normaliseId($id);
        $this->ensureStorage();
        $this->purgeExpired();

        $entry = $_SESSION[$this->sessionKey][$id] ?? null;
        if (is_array($entry) && $this->isEntryValid($entry)) {
            return $entry['value'];
        }

        return $this->generateToken($id);
    }

    public function generateToken(?string $id = null): string
    {
        $id = $this->normaliseId($id);
        $this->ensureStorage();

        $token = bin2hex(random_bytes(32));
        $_SESSION[$this->sessionKey][$id] = [
            'value' => $token,
            'expires_at' => $this->ttl > 0 ? time() + $this->ttl : null,
        ];

        $this->enforceTokenLimit();

        return $token;
    }

    public function validateToken(string $token, ?string $id = null, bool $consume = true): bool
    {
        $id = $this->normaliseId($id);
        $this->ensureStorage();
        $this->purgeExpired();

        $entry = $_SESSION[$this->sessionKey][$id] ?? null;
        if (!is_array($entry) || !isset($entry['value']) || !is_string($entry['value'])) {
            return false;
        }

        $isValid = hash_equals($entry['value'], $token);

        if ($isValid && $consume) {
            unset($_SESSION[$this->sessionKey][$id]);
        }

        return $isValid;
    }

    public function invalidateToken(?string $id = null): void
    {
        $id = $this->normaliseId($id);
        $this->ensureStorage();
        unset($_SESSION[$this->sessionKey][$id]);
    }

    public function purgeExpired(): void
    {
        $this->ensureStorage();

        foreach ($_SESSION[$this->sessionKey] as $id => $entry) {
            if (!is_array($entry) || !$this->isEntryValid($entry)) {
                unset($_SESSION[$this->sessionKey][$id]);
            }
        }
    }

    private function ensureStorage(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!headers_sent() && !session_start()) {
                throw new RuntimeException('Nepodařilo se spustit session pro CSRF ochranu.');
            }

            if (session_status() !== PHP_SESSION_ACTIVE) {
                throw new RuntimeException('Session musí být aktivní pro CSRF ochranu.');
            }
        }

        $_SESSION[$this->sessionKey] ??= [];
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function isEntryValid(array $entry): bool
    {
        if (!isset($entry['value']) || !is_string($entry['value']) || $entry['value'] === '') {
            return false;
        }

        if (!isset($entry['expires_at']) || $entry['expires_at'] === null) {
            return true;
        }

        if (!is_int($entry['expires_at'])) {
            return false;
        }

        return $entry['expires_at'] >= time();
    }

    private function enforceTokenLimit(): void
    {
        $tokens = &$_SESSION[$this->sessionKey];
        while (count($tokens) > $this->maxTokens) {
            array_shift($tokens);
        }
    }

    private function normaliseId(?string $id): string
    {
        $id = $id !== null ? trim($id) : 'default';
        if ($id === '') {
            throw new RuntimeException('Identifikátor CSRF tokenu nesmí být prázdný.');
        }

        return $id;
    }
}
