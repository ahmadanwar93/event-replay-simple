<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\DomainEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class StoreEventListener
{
    public $event = OrderPlaced::class;
    public function handle(OrderPlaced $event): void
    {
        // when OrderPlaced event is dispatch
        // will call all the eventListener and passing in the OrderPlaced event in as a param

        if ($event->isReplay) {
            // for idempotency 
            Log::info("Skipping event storage for replay");
            return;
        }
        DomainEvent::create([
            'event_type' => get_class($event),
            'aggregate_type' => 'Order',
            'aggregate_id' => (string) $event->orderId,
            'payload' => $event->toArray(),
            'metadata' => [
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
            'created_at' => now(),
            'processed_at' => now(),
        ]);
    }
}
