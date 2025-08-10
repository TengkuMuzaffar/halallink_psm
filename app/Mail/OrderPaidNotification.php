<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderPaidNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $items;
    public $buyer;
    public $companyItems;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, $items, User $buyer, $companyItems)
    {
        $this->order = $order;
        $this->items = $items;
        $this->buyer = $buyer;
        $this->companyItems = $companyItems;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('New Order Notification - Order #' . $this->order->orderID)
                    ->view('emails.order-paid-notification');
    }
}