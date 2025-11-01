<?php
declare(strict_types=1);

namespace Core\Inventory;

use DateTimeImmutable;
use InvalidArgumentException;

final class InventoryMovement
{
    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';

    private DateTimeImmutable $occurredAt;
    private string $type;
    private int $quantity;
    private string $reason;
    private ?string $note;
    private ?string $reference;

    public function __construct(
        DateTimeImmutable $occurredAt,
        string $type,
        int $quantity,
        string $reason,
        ?string $note = null,
        ?string $reference = null,
    ) {
        $this->setType($type);
        $this->setQuantity($quantity);

        $this->occurredAt = $occurredAt;
        $this->reason = trim($reason);
        $this->note = $note !== null ? trim($note) : null;
        $this->reference = $reference !== null ? trim($reference) : null;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function isInbound(): bool
    {
        return $this->type === self::TYPE_IN;
    }

    public function isOutbound(): bool
    {
        return $this->type === self::TYPE_OUT;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function note(): ?string
    {
        return $this->note;
    }

    public function reference(): ?string
    {
        return $this->reference;
    }

    private function setType(string $type): void
    {
        $type = strtolower(trim($type));
        if (!in_array($type, [self::TYPE_IN, self::TYPE_OUT], true)) {
            throw new InvalidArgumentException(sprintf('Unsupported movement type "%s".', $type));
        }

        $this->type = $type;
    }

    private function setQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be a positive integer.');
        }

        $this->quantity = $quantity;
    }
}
