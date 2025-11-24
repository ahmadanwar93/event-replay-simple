<?php

use App\Events\OrderPlaced;
use App\Listeners\CreateWarehouseNotification;
use App\Listeners\DecrementInventory;
use App\Listeners\SendOrderConfirmationEmail;
use App\Listeners\StoreEventListener;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Event;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->booted(function () {
        // we dont need to manual registering the event here, we are using auto discovery
    })
    ->create();
