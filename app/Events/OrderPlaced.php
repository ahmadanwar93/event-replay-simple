<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPlaced
{
    use Dispatchable;
    // dispatchable trait is such that i can fire the event using dispatch statuc method
    // in this case, since we are passing in primitives, no need to serializeModels trait

    /**
     * Create a new event instance.
     */
    public function __construct(public int $orderId, public string $productId, public int $quantity, public string $total, public bool $isReplay = false) {}
    // isReplay is for replayEvent purpose

    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'total' => $this->total,
        ];
    }
    public static function fromArray(array $data, $isReplay = false): self
    {
        return new self(
            orderId: $data['order_id'],
            productId: $data['product_id'],
            quantity: $data['quantity'],
            total: $data['total'],
            isReplay: $isReplay, // need this for event replay
        );
    }
}
