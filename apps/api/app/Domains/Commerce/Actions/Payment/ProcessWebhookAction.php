<?php

namespace App\Domains\Commerce\Actions\Payment;

use App\Domains\Commerce\Contracts\PaymentGateway;
use App\Domains\Commerce\Enums\OrderStatus;
use App\Domains\Commerce\Enums\TransactionStatus;
use App\Domains\Commerce\Events\OrderPaid;
use App\Domains\Commerce\Events\PaymentFailed;
use App\Domains\Commerce\Models\Order;
use App\Domains\Commerce\Models\PaymentWebhookEvent;
use App\Shared\Actions\BaseAction;

/**
 * Verifies + parses a provider webhook and advances the order state exactly once (dedup by
 * event id). Marks the charge transaction succeeded/failed and dispatches OrderPaid/PaymentFailed.
 */
class ProcessWebhookAction extends BaseAction
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function execute(string $payload, ?string $signature): void
    {
        $event = $this->gateway->parseWebhook($payload, $signature); // throws on bad signature

        $paidOrder = $this->transaction(function () use ($event, $payload): ?Order {
            // Dedup: unique event_id makes replays a no-op.
            $record = PaymentWebhookEvent::firstOrCreate(
                ['event_id' => $event->id],
                [
                    'provider' => (string) config('commerce.payment.provider'),
                    'type' => $event->type,
                    'payload' => json_decode($payload, true),
                ],
            );

            if ($record->processed_at !== null) {
                return null; // already handled
            }

            $order = Order::where('public_id', $event->orderReference)->lockForUpdate()->first();

            if ($order !== null && $order->status === OrderStatus::Pending) {
                if ($event->type === 'payment.succeeded') {
                    $order->forceFill(['status' => OrderStatus::Paid->value, 'paid_at' => now()])->save();
                    $order->transactions()->where('type', 'charge')->update(['status' => TransactionStatus::Succeeded->value]);
                    $order->invoice?->forceFill(['status' => 'paid', 'paid_at' => now()])->save();
                    $record->forceFill(['processed_at' => now()])->save();

                    return $order;
                }

                if ($event->type === 'payment.failed') {
                    $order->forceFill(['status' => OrderStatus::Failed->value])->save();
                    $order->transactions()->where('type', 'charge')->update(['status' => TransactionStatus::Failed->value]);
                    $record->forceFill(['processed_at' => now()])->save();
                    PaymentFailed::dispatch($order);

                    return null;
                }
            }

            $record->forceFill(['processed_at' => now()])->save();

            return null;
        });

        if ($paidOrder !== null) {
            OrderPaid::dispatch($paidOrder);
        }
    }
}
