<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class DecrementInventory
{
    public $event = OrderPlaced::class;
    public function handle(OrderPlaced $event): void
    {
        // idempotency check for event replay later
        $alreadyProcessed = InventoryTransaction::where('order_id', $event->orderId)
            ->where('product_id', $event->productId)
            ->where('type', 'order_placed')
            ->exists();

        if ($alreadyProcessed) {
            Log::info("Inventory already decremented for order {$event->orderId}, skipping");
            return;
        }

        $inventory = Inventory::where('product_id', $event->productId)->first();

        if (!$inventory) {
            Log::error("Inventory not found for product {$event->productId}");
            return;
        }

        if ($inventory->stock < $event->quantity) {
            Log::error("Insufficient stock for product {$event->productId}. Available: {$inventory->stock}, Needed: {$event->quantity}");
            return;
        }

        $inventory->decrement('stock', $event->quantity);

        InventoryTransaction::create([
            'order_id' => $event->orderId,
            'product_id' => $event->productId,
            'quantity' => $event->quantity,
            'type' => 'order_placed',
            'processed_at' => now(),
        ]);

        Log::info("Decremented inventory for order {$event->orderId}. Product: {$event->productId}, Quantity: {$event->quantity}");
    }
}
