<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    // Dashboard controller will be added in Slice 5
});
