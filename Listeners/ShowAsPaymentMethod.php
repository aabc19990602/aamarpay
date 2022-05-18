<?php

namespace Modules\Aamarpay\Listeners;


use App\Events\Auth\LandingPageShowing as Event;

class ShowAsPaymentMethod
{


    /**
     * Handle the event.
     *
     * @param  Event $event
     * @return void
     */
    public function handle($event)
    {
    
            $method = setting('aamarpay');
    
            $method['code'] = 'aamarpay';
    
            $event->modules->payment_methods[] = $method;
    
        
    }
}
