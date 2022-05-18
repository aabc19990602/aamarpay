<?php

namespace Modules\Aamarpay\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AddLandingPage
{


    /**
     * Handle the event.
     *
     * @param  Event $event
     * @return void
     */
    public function handle($event)
    {
        $event->user->landing_pages['paypal-standard.settings.edit'] = trans('aamarpay::general.name');
        
    }
}
