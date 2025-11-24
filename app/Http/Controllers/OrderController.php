<?php

namespace App\Http\Controllers;

use App\Events\OrderPlaced;
use App\Models\DomainEvent;
use App\Models\Inventory;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'total' => 'required|numeric|min:0',
        ]);

        $inventory = Inventory::where('product_id', $validated['product_id'])->first();

        if (!$inventory) {
            return response()->json([
                'error' => 'Product not found in inventory'
            ], 404);
        }

        if ($inventory->stock < $validated['quantity']) {
            return response()->json([
                'error' => 'Insufficient stock',
                'available' => $inventory->stock,
                'requested' => $validated['quantity']
            ], 400);
        }

        $order = Order::create([
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'total' => $validated['total'],
            'status' => 'pending',
        ]);

        event(new OrderPlaced(
            orderId: $order->id,
            productId: $order->product_id,
            quantity: $order->quantity,
            total: $order->total,
        ));

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order,
        ], 201);
    }

    public function replayEvent(DomainEvent $event)
    {
        $eventClass = $event->event_type;

        if (!class_exists($eventClass)) {
            // event_type is fully qualified name of the event class
            // we dont have to reconstruct the fully qualified name
            return response()->json(['error' => 'Event class not found'], 500);
        }

        // eventClass can be whatever event stored in the domainEvent table, hence why we use variable
        // instead of doing something like OrderPlaced::fromArray
        $eventInstance = $eventClass::fromArray($event->payload, isReplay: true);

        event($eventInstance);

        return response()->json([
            'message' => 'Event replayed successfully',
            'event_id' => $event->id,
            'event_type' => $eventClass,
        ]);
    }
}
