<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function orderPlaced(Order $order): void
    {
        $this->mail('Order '.$order->order_number.' placed', $this->customerBody($order, 'We have received your Cash on Delivery order.'));
        $this->mail(
            'New COD order '.$order->order_number,
            'A new order of '.money($order->total).' was placed by '.$order->customer_name.'.',
            setting('contact_email', config('shop.admin_email'))
        );
        $this->whatsapp('customer.order_received', $order);
        $this->whatsapp('admin.new_order', $order);
    }

    public function orderStatusChanged(Order $order): void
    {
        $this->mail(
            'Order '.$order->order_number.' is '.$order->status->label(),
            $this->customerBody($order, 'Your order status is now: '.$order->status->label().'.')
        );
        $this->whatsapp('customer.status_'.$order->status->value, $order);
    }

    private function customerBody(Order $order, string $intro): string
    {
        return implode("\n", [
            'Hi '.$order->customer_name.',',
            $intro,
            'Order: '.$order->order_number,
            'Total: '.money($order->total),
            'Payment: Cash on Delivery',
            'Track: '.url('/track-order'),
        ]);
    }

    private function mail(string $subject, string $body, ?string $to = null): void
    {
        $to ??= setting('contact_email');
        if (! $to) {
            return;
        }

        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::error('Email notification failed', ['error' => $e->getMessage()]);
        }
    }

    private function whatsapp(string $event, Order $order): void
    {
        Log::info('WhatsApp notification queued', [
            'event' => $event,
            'order' => $order->order_number,
        ]);
    }
}
