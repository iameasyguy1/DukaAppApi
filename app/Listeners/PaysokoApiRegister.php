<?php

namespace App\Listeners;

use App\Events\ApiRegistration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class PaysokoApiRegister
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\ApiRegistration  $event
     * @return void
     */
    public function handle(ApiRegistration $event)
    {

        $name_parts = explode(" ", optional($event->user_info)->name); // split the full name by space delimiter

        $first_name = $name_parts[0]; // first name is the first element of the array
        $last_name = $name_parts[count($name_parts)-1];

       $reg= register_vendor_to_paysoko($first_name,$last_name,optional($event->user_info)->phone,optional($event->user_info)->email,optional($event->user_info)->name,'dukaap.com');
       Log::info($reg);
    }
}
