<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CreateWarehouseNotification
{
    public $event = OrderPlaced::class;
    public function handle(OrderPlaced $event): void
    {
        $exists = Notification::where('order_id', $event->orderId)
            ->where('type', 'order_placed')
            ->exists();

        if ($exists) {
            Log::info("Notification already exists for order {$event->orderId}, skipping");
            return;
        }

        // for high concurrency, can do try catch, with unique constraint check at db level

        Notification::create([
            'order_id' => $event->orderId,
            'type' => 'order_placed',
            'message' => "New order #{$event->orderId}: {$event->quantity}x {$event->productId} (Total: \${$event->total})",
        ]);

        Log::info("Created warehouse notification for order {$event->orderId}");
    }
}
