<?php

use App\Models\DomainEvent;
use App\Models\EmailLog;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Notification;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Inventory::factory()->create();
});

it("creates an order successfully", function () {
    Mail::fake();

    $response = $this->postJson('/api/orders', [
        'product_id' => 'PROD-001',
        'quantity' => 2,
        'total' => 2000.00,
    ]);
    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'order' => ['id', 'product_id', 'quantity', 'total', 'status'],
        ]);
    expect(Order::count())->toBe(1);

    $order = Order::first();
    expect($order->product_id)->toBe('PROD-001');
    expect($order->quantity)->toBe(2);
    expect($order->status)->toBe('pending');

    // only fire DomainEvent once
    expect(DomainEvent::count())->toBe(1);
    $event = DomainEvent::first();
    expect($event->event_type)->toBe('App\Events\OrderPlaced');
    expect($event->aggregate_type)->toBe('Order');
    expect($event->payload['product_id'])->toBe('PROD-001');

    // only decrements inventory once
    $inventory = Inventory::where('product_id', 'PROD-001')->first();
    expect($inventory->stock)->toBe(998);
    expect(InventoryTransaction::count())->toBe(1);

    // only send email once
    expect(EmailLog::count())->toBe(1);
    $emailLog = EmailLog::first();
    expect($emailLog->type)->toBe('order_confirmation');

    // only send notification once
    expect(Notification::count())->toBe(1);
    $notification = Notification::first();
    expect($notification->type)->toBe('order_placed');
});

it('replays event successfully without duplication', function () {
    Mail::fake();

    $response = $this->postJson('/api/orders', [
        'product_id' => 'PROD-001',
        'quantity' => 2,
        'total' => 2000.00,
    ]);

    $event = DomainEvent::first();
    $this->postJson("/api/events/{$event->id}/replay");

    expect(Order::count())->toBe(1);

    $order = Order::first();
    expect($order->product_id)->toBe('PROD-001');
    expect($order->quantity)->toBe(2);
    expect($order->status)->toBe('pending');

    // only fire DomainEvent once
    expect(DomainEvent::count())->toBe(1);
    $event = DomainEvent::first();
    expect($event->event_type)->toBe('App\Events\OrderPlaced');
    expect($event->aggregate_type)->toBe('Order');
    expect($event->payload['product_id'])->toBe('PROD-001');

    // only decrements inventory once
    $inventory = Inventory::where('product_id', 'PROD-001')->first();
    expect($inventory->stock)->toBe(998);
    expect(InventoryTransaction::count())->toBe(1);

    // only send email once
    expect(EmailLog::count())->toBe(1);
    $emailLog = EmailLog::first();
    expect($emailLog->type)->toBe('order_confirmation');

    // only send notification once
    expect(Notification::count())->toBe(1);
    $notification = Notification::first();
    expect($notification->type)->toBe('order_placed');
});

it('replays event when a DecrementInventory failed intially', function () {
    Mail::fake();

    // Simulate listener failure by manually creating incomplete state
    // Scenario: Order created, but DecrementInventory listener failed

    // Create order manually (simulating what controller does)
    $order = Order::create([
        'product_id' => 'PROD-001',
        'quantity' => 2,
        'total' => 2000.00,
        'status' => 'pending',
    ]);

    // Manually store the event (simulating StoreEventListener)
    $domainEvent = DomainEvent::create([
        'event_type' => 'App\Events\OrderPlaced',
        'aggregate_type' => 'Order',
        'aggregate_id' => (string) $order->id,
        'payload' => [
            'order_id' => $order->id,
            'product_id' => 'PROD-001',
            'quantity' => 2,
            'total' => '2000.00',
        ],
        'metadata' => [],
        'created_at' => now(),
        'processed_at' => now(),
    ]);

    // Manually create email log to simulate SendEmail succeed
    EmailLog::create([
        'order_id' => $order->id,
        'type' => 'order_confirmation',
        'sent_at' => now(),
    ]);

    // Manually create notification to simulate CreateNotification succeed
    Notification::create([
        'order_id' => $order->id,
        'type' => 'order_placed',
        'message' => "New order #{$order->id}",
    ]);

    // inventory transaction is not created to simulate DecrementInventory failed hence quantity is not being reduced
    $inventory = Inventory::where('product_id', 'PROD-001')->first();
    expect($inventory->stock)->toBe(1000);
    expect(InventoryTransaction::count())->toBe(0);

    expect(EmailLog::count())->toBe(1);
    expect(Notification::count())->toBe(1);
    expect(DomainEvent::count())->toBe(1);

    $response = $this->postJson("/api/events/{$domainEvent->id}/replay");
    $response->assertStatus(200);

    $inventory->refresh();
    expect($inventory->stock)->toBe(998);
    expect(InventoryTransaction::count())->toBe(1);

    // Other listeners should skip due to idempotency
    expect(EmailLog::count())->toBe(1);
    expect(Notification::count())->toBe(1);
    expect(DomainEvent::count())->toBe(1);
});

it('successfully processes failed email listener on replay', function () {
    Mail::fake();

    $order = Order::create([
        'product_id' => 'PROD-001',
        'quantity' => 2,
        'total' => 2000.00,
        'status' => 'pending',
    ]);

    $domainEvent = DomainEvent::create([
        'event_type' => 'App\Events\OrderPlaced',
        'aggregate_type' => 'Order',
        'aggregate_id' => (string) $order->id,
        'payload' => [
            'order_id' => $order->id,
            'product_id' => 'PROD-001',
            'quantity' => 2,
            'total' => '2000.00',
        ],
        'metadata' => [],
        'created_at' => now(),
        'processed_at' => now(),
    ]);

    expect(EmailLog::count())->toBe(0);

    $inventory = Inventory::where('product_id', 'PROD-001')->first();
    $inventory->update(['stock' => 998]);
    InventoryTransaction::create([
        'order_id' => $order->id,
        'product_id' => 'PROD-001',
        'quantity' => 2,
        'type' => 'order_placed',
        'processed_at' => now(),
    ]);

    Notification::create([
        'order_id' => $order->id,
        'type' => 'order_placed',
        'message' => "New order #{$order->id}",
    ]);

    expect($inventory->stock)->toBe(998);
    expect(InventoryTransaction::count())->toBe(1);
    expect(EmailLog::count())->toBe(0);
    expect(Notification::count())->toBe(1);

    $response = $this->postJson("/api/events/{$domainEvent->id}/replay");
    $response->assertStatus(200);

    expect(EmailLog::count())->toBe(1);

    $emailLog = EmailLog::first();
    expect($emailLog->order_id)->toBe($order->id);
    expect($emailLog->type)->toBe('order_confirmation');

    $inventory->refresh();
    expect($inventory->stock)->toBe(998);
    expect(InventoryTransaction::count())->toBe(1);
    expect(Notification::count())->toBe(1);
    expect(DomainEvent::count())->toBe(1);
});

it('successfully processes failed notification listener on replay', function () {
    Mail::fake();

    $order = Order::create([
        'product_id' => 'PROD-001',
        'quantity' => 2,
        'total' => 2000.00,
        'status' => 'pending',
    ]);

    $domainEvent = DomainEvent::create([
        'event_type' => 'App\Events\OrderPlaced',
        'aggregate_type' => 'Order',
        'aggregate_id' => (string) $order->id,
        'payload' => [
            'order_id' => $order->id,
            'product_id' => 'PROD-001',
            'quantity' => 2,
            'total' => '2000.00',
        ],
        'metadata' => [],
        'created_at' => now(),
        'processed_at' => now(),
    ]);

    EmailLog::create([
        'order_id' => $order->id,
        'type' => 'order_confirmation',
        'sent_at' => now(),
    ]);

    $inventory = Inventory::where('product_id', 'PROD-001')->first();
    $inventory->update(['stock' => 998]);
    InventoryTransaction::create([
        'order_id' => $order->id,
        'product_id' => 'PROD-001',
        'quantity' => 2,
        'type' => 'order_placed',
        'processed_at' => now(),
    ]);

    // Notification NOT created
    expect(Notification::count())->toBe(0);

    expect($inventory->stock)->toBe(998);
    expect(InventoryTransaction::count())->toBe(1);
    expect(EmailLog::count())->toBe(1);
    expect(Notification::count())->toBe(0); // Notification NOT created!

    $response = $this->postJson("/api/events/{$domainEvent->id}/replay");
    $response->assertStatus(200);

    expect(Notification::count())->toBe(1);

    $notification = Notification::first();
    expect($notification->order_id)->toBe($order->id);
    expect($notification->type)->toBe('order_placed');

    $inventory->refresh();
    expect($inventory->stock)->toBe(998);
    expect(InventoryTransaction::count())->toBe(1);
    expect(EmailLog::count())->toBe(1);
    expect(DomainEvent::count())->toBe(1);
});
