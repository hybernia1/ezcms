<?php
declare(strict_types=1);

namespace Core\Inventory;

final class InventoryMovementReason
{
    public const INVENTORY_CHECK = 'inventory_check';
    public const ORDER_DISPATCH = 'order_dispatch';
    public const MANUAL_ADJUSTMENT = 'manual_adjustment';

    public static function label(string $reason, ?string $note = null): string
    {
        $map = [
            self::INVENTORY_CHECK => __('inventory.reason.inventory_check'),
            self::ORDER_DISPATCH => __('inventory.reason.order_dispatch'),
            self::MANUAL_ADJUSTMENT => __('inventory.reason.manual_adjustment'),
        ];

        $label = $map[$reason] ?? $reason;

        if ($note !== null && $note !== '') {
            $label .= ' — ' . $note;
        }

        return $label;
    }
}
