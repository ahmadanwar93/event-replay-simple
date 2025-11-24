<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\EmailLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationEmail
{
    public $event = OrderPlaced::class;
    public function handle(OrderPlaced $event): void
    {
        // for idempotentcy, it will be useful to avoid double sending email when event replay
        $alreadySent = EmailLog::where('order_id', $event->orderId)
            ->where('type', 'order_confirmation')
            ->exists();

        if ($alreadySent) {
            Log::info("Email already sent for order {$event->orderId}, skipping");
            return;
        }

        // just a crude Mail to let us assert mail sent later
        Mail::raw(
            "Order Confirmation\n\nOrder #{$event->orderId}\nProduct: {$event->productId}\nQuantity: {$event->quantity}\nTotal: \${$event->total}",
            function ($message) use ($event) {
                $message->to('customer@example.com')
                    ->subject("Order Confirmation #{$event->orderId}");
            }
        );

        EmailLog::create([
            'order_id' => $event->orderId,
            'type' => 'order_confirmation',
            'sent_at' => now(),
        ]);

        Log::info("Email logged for order {$event->orderId}");
    }
}
