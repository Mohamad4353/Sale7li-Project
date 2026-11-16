<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('sale7li:health', function () {
    $this->info('SALE7LI application is healthy.');
})->purpose('Verify the SALE7LI application is available.');
