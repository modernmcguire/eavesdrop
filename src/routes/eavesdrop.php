<?php

use Illuminate\Support\Facades\Route;
use ModernMcGuire\Eavesdrop\Http\Controllers\InboxController;
use ModernMcGuire\Eavesdrop\Http\Controllers\MessageController;

$endpoint = config('eavesdrop.endpoint');

Route::get($endpoint, [InboxController::class, 'index'])->name('index');
Route::get("{$endpoint}/list", [MessageController::class, 'index'])->name('messages.index');
Route::delete($endpoint, [MessageController::class, 'destroy'])->name('messages.destroy');
Route::get("{$endpoint}/{message}", [MessageController::class, 'show'])
    ->whereNumber('message')
    ->name('messages.show');
