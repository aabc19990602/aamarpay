<?php

namespace Modules\Aamarpay\Listeners;

use App\Events\Module\PaymentMethodShowing as Event;


class ShowAsPaymentMethod
{
    /**
     * Handle the event.
     *
     * @param  Event $event
     * @return void
     */
    public function handle(Event $event)
    {
        $method = setting('aamarpay');
        $method['code'] = 'aamarpay';
        $event->modules->payment_methods[] = $method;
    }
}
