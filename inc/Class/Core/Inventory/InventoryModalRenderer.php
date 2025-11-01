<?php
declare(strict_types=1);

namespace Core\Inventory;

use const ENT_QUOTES;
use function htmlspecialchars;
use function sprintf;

final class InventoryModalRenderer
{
    private static bool $assetsInjected = false;

    public function render(InventoryItem $item, ?string $modalId = null): string
    {
        $modalId = $modalId !== null && $modalId !== ''
            ? $modalId
            : sprintf('inventory-modal-%s', $item->slug());

        $timeline = $item->timeline();
        $buttonLabel = __('inventory.modal.button');
        $title = __('inventory.modal.title', ['sku' => $item->sku(), 'name' => $item->name()]);
        $currentStock = $item->currentStock();

        ob_start();
        if (!self::$assetsInjected) {
            echo $this->renderAssets();
            self::$assetsInjected = true;
        }

        $safeModalId = htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeButtonLabel = htmlspecialchars($buttonLabel, ENT_QUOTES, 'UTF-8');
        ?>
        <button
            type="button"
            class="inventory-modal__toggle"
            data-inventory-modal-target="<?= $safeModalId ?>"
        ><?= $safeButtonLabel ?></button>
        <div class="inventory-modal" id="<?= $safeModalId ?>" hidden data-inventory-modal>
            <div class="inventory-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="<?= $safeModalId ?>-title">
                <header class="inventory-modal__header">
                    <h2 id="<?= $safeModalId ?>-title" class="inventory-modal__title"><?= $safeTitle ?></h2>
                    <button type="button" class="inventory-modal__close" data-inventory-modal-close>
                        <span aria-hidden="true">&times;</span>
                        <span class="inventory-modal__sr-only"><?= htmlspecialchars(__('inventory.modal.close'), ENT_QUOTES, 'UTF-8') ?></span>
                    </button>
                </header>
                <div class="inventory-modal__body">
                    <p class="inventory-modal__stock">
                        <?= htmlspecialchars(__('inventory.modal.currentStock', ['stock' => (string) $currentStock]), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php if ($timeline === []) { ?>
                        <p class="inventory-modal__empty"><?= htmlspecialchars(__('inventory.modal.empty'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php } else { ?>
                        <div class="inventory-modal__table-wrapper">
                            <table class="inventory-modal__table">
                                <thead>
                                    <tr>
                                        <th scope="col"><?= htmlspecialchars(__('inventory.modal.table.date'), ENT_QUOTES, 'UTF-8') ?></th>
                                        <th scope="col"><?= htmlspecialchars(__('inventory.modal.table.direction'), ENT_QUOTES, 'UTF-8') ?></th>
                                        <th scope="col" class="inventory-modal__table-quantity">
                                            <?= htmlspecialchars(__('inventory.modal.table.quantity'), ENT_QUOTES, 'UTF-8') ?>
                                        </th>
                                        <th scope="col" class="inventory-modal__table-quantity">
                                            <?= htmlspecialchars(__('inventory.modal.table.balance'), ENT_QUOTES, 'UTF-8') ?>
                                        </th>
                                        <th scope="col"><?= htmlspecialchars(__('inventory.modal.table.reason'), ENT_QUOTES, 'UTF-8') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach (array_reverse($timeline) as $entry) {
                                    $movement = $entry['movement'];
                                    $balance = $entry['balance'];
                                    $directionKey = $movement->isInbound()
                                        ? 'inventory.movement.type.in'
                                        : 'inventory.movement.type.out';
                                    $direction = htmlspecialchars(__(
                                        $directionKey
                                    ), ENT_QUOTES, 'UTF-8');
                                    $date = htmlspecialchars($movement->occurredAt()->format(__('inventory.modal.table.dateFormat')),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    $quantity = htmlspecialchars((string) $movement->quantity(), ENT_QUOTES, 'UTF-8');
                                    $balanceValue = htmlspecialchars((string) $balance, ENT_QUOTES, 'UTF-8');
                                    $reason = htmlspecialchars(InventoryMovementReason::label(
                                        $movement->reason(),
                                        $movement->note()
                                    ), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <tr>
                                        <td><?= $date ?></td>
                                        <td><?= $direction ?></td>
                                        <td class="inventory-modal__table-quantity" data-direction="<?= $movement->type() ?>">
                                            <?= $quantity ?>
                                        </td>
                                        <td class="inventory-modal__table-quantity inventory-modal__table-balance">
                                            <?= $balanceValue ?>
                                        </td>
                                        <td>
                                            <div class="inventory-modal__reason">
                                                <span><?= $reason ?></span>
                                                <?php if ($movement->reference() !== null && $movement->reference() !== '') { ?>
                                                    <span class="inventory-modal__reference">
                                                        <?= htmlspecialchars(__('inventory.modal.reference', ['reference' => $movement->reference()]), ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                <?php } ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <button type="button" class="inventory-modal__backdrop" data-inventory-modal-close aria-label="<?= htmlspecialchars(__('inventory.modal.close'), ENT_QUOTES, 'UTF-8') ?>"></button>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    private function renderAssets(): string
    {
        return <<<'HTML'
<style>
.inventory-modal[hidden] { display: none; }
.inventory-modal { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; z-index: 1050; }
.inventory-modal__dialog { position: relative; background: #ffffff; border-radius: 1rem; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.25); width: min(90vw, 52rem); max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; }
.inventory-modal__header { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(15, 23, 42, 0.08); background: linear-gradient(135deg, #f8fafc, #eef2ff); }
.inventory-modal__title { margin: 0; font-size: 1.1rem; font-weight: 600; color: #0f172a; }
.inventory-modal__close { border: none; background: none; font-size: 1.75rem; line-height: 1; cursor: pointer; color: #64748b; padding: 0.25rem; border-radius: 0.375rem; }
.inventory-modal__close:hover, .inventory-modal__close:focus { color: #334155; background: rgba(148, 163, 184, 0.15); outline: none; }
.inventory-modal__body { padding: 1.5rem; overflow: auto; }
.inventory-modal__stock { margin: 0 0 1rem; font-weight: 600; color: #0f172a; }
.inventory-modal__empty { margin: 0; color: #475569; }
.inventory-modal__table-wrapper { max-height: 60vh; overflow: auto; border-radius: 0.75rem; border: 1px solid rgba(15, 23, 42, 0.08); }
.inventory-modal__table { width: 100%; border-collapse: collapse; min-width: 30rem; }
.inventory-modal__table thead { background: #f1f5f9; position: sticky; top: 0; z-index: 1; }
.inventory-modal__table th, .inventory-modal__table td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid rgba(15, 23, 42, 0.05); }
.inventory-modal__table tr:last-child td { border-bottom: none; }
.inventory-modal__table-quantity { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.inventory-modal__table-quantity[data-direction="in"] { color: #059669; }
.inventory-modal__table-quantity[data-direction="out"] { color: #dc2626; }
.inventory-modal__table-balance { color: #0f172a; font-weight: 600; }
.inventory-modal__reason { display: flex; flex-direction: column; gap: 0.25rem; color: #1f2937; }
.inventory-modal__reference { font-size: 0.85rem; color: #475569; }
.inventory-modal__backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); border: none; cursor: pointer; }
.inventory-modal__toggle { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0.9rem; border-radius: 9999px; border: 1px solid rgba(59, 130, 246, 0.4); background: linear-gradient(135deg, rgba(59, 130, 246, 0.12), rgba(59, 130, 246, 0.32)); color: #1d4ed8; font-weight: 600; cursor: pointer; transition: transform 0.12s ease, box-shadow 0.12s ease; }
.inventory-modal__toggle:hover, .inventory-modal__toggle:focus { transform: translateY(-1px); box-shadow: 0 10px 25px rgba(59, 130, 246, 0.25); outline: none; }
.inventory-modal__toggle:active { transform: translateY(0); box-shadow: none; }
.inventory-modal__sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0; }
@media (max-width: 640px) {
    .inventory-modal__dialog { width: 95vw; border-radius: 0.75rem; }
    .inventory-modal__body { padding: 1rem; }
    .inventory-modal__table th, .inventory-modal__table td { padding: 0.6rem 0.75rem; }
}
</style>
<script>
(() => {
    const OPEN_ATTR = 'data-inventory-modal-target';
    const CLOSE_ATTR = 'data-inventory-modal-close';
    const MODAL_ATTR = 'data-inventory-modal';

    const generateId = () => {
        if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
            return crypto.randomUUID();
        }

        return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;
    };

    const openModal = (modal, trigger) => {
        if (!modal) return;
        modal.removeAttribute('hidden');
        modal.setAttribute('aria-hidden', 'false');
        if (trigger instanceof HTMLElement) {
            modal.dataset.lastTriggerId = trigger.id || '';
            trigger.dataset.inventoryModalActive = 'true';
        }
        const focusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (focusable instanceof HTMLElement) {
            focusable.focus({ preventScroll: true });
        }
        document.body.classList.add('inventory-modal--open');
    };

    const closeModal = (modal) => {
        if (!modal) return;
        modal.setAttribute('hidden', 'hidden');
        modal.setAttribute('aria-hidden', 'true');
        const triggerId = modal.dataset.lastTriggerId || '';
        if (triggerId) {
            const trigger = document.getElementById(triggerId);
            if (trigger instanceof HTMLElement) {
                trigger.focus({ preventScroll: true });
                delete trigger.dataset.inventoryModalActive;
            }
        } else {
            const activeTrigger = document.querySelector(`[${OPEN_ATTR}][data-inventory-modal-active="true"]`);
            if (activeTrigger instanceof HTMLElement) {
                activeTrigger.focus({ preventScroll: true });
                delete activeTrigger.dataset.inventoryModalActive;
            }
        }
        document.body.classList.remove('inventory-modal--open');
    };

    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target.closest(`[${OPEN_ATTR}]`) : null;
        if (target instanceof HTMLElement) {
            event.preventDefault();
            const modalId = target.getAttribute(OPEN_ATTR);
            if (!modalId) return;
            const modal = document.getElementById(modalId);
            if (!modal) return;
            if (!target.id) {
                target.id = `inventory-modal-trigger-${generateId()}`;
            }
            openModal(modal, target);
            return;
        }

        const closer = event.target instanceof Element ? event.target.closest(`[${CLOSE_ATTR}]`) : null;
        if (closer instanceof HTMLElement) {
            event.preventDefault();
            const modal = closer.closest(`[${MODAL_ATTR}]`);
            closeModal(modal);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const openModalEl = document.querySelector(`[${MODAL_ATTR}]:not([hidden])`);
        if (openModalEl) {
            event.preventDefault();
            closeModal(openModalEl);
        }
    });
})();
</script>
HTML;
    }
}
