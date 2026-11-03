<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Command;

class HanguskanBooking extends Command
{
    protected $signature = 'booking:kedaluwarsa';

    protected $description = 'Hanguskan booking yang melewati batas waktu 1x24 jam dan lepaskan kamarnya';

    public function handle(BookingService $service): int
    {
        $this->info($service->hanguskanKedaluwarsa() . ' booking dihanguskan.');

        return self::SUCCESS;
    }
}
