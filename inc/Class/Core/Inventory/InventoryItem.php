<?php
declare(strict_types=1);

namespace Core\Inventory;

use Core\Utils\Slugger;

final class InventoryItem
{
    /** @var list<InventoryMovement> */
    private array $movements = [];

    public function __construct(
        private readonly string $sku,
        private readonly string $name,
        private readonly int $startingStock = 0,
    ) {}

    public function sku(): string
    {
        return $this->sku;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function startingStock(): int
    {
        return $this->startingStock;
    }

    public function slug(): string
    {
        return Slugger::from($this->sku !== '' ? $this->sku : $this->name, 'inventory-item');
    }

    public function addMovement(InventoryMovement $movement): void
    {
        $this->movements[] = $movement;
        usort(
            $this->movements,
            static fn (InventoryMovement $a, InventoryMovement $b): int => $a->occurredAt() <=> $b->occurredAt()
        );
    }

    /**
     * @return list<InventoryMovement>
     */
    public function movements(): array
    {
        return $this->movements;
    }

    public function currentStock(): int
    {
        $stock = $this->startingStock;
        foreach ($this->movements as $movement) {
            $stock += $movement->isInbound() ? $movement->quantity() : -$movement->quantity();
        }

        return $stock;
    }

    /**
     * @return list<array{movement: InventoryMovement, balance: int}>
     */
    public function timeline(): array
    {
        $balance = $this->startingStock;
        $timeline = [];

        foreach ($this->movements as $movement) {
            $balance += $movement->isInbound() ? $movement->quantity() : -$movement->quantity();
            $timeline[] = [
                'movement' => $movement,
                'balance' => $balance,
            ];
        }

        return $timeline;
    }
}
