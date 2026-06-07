<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Project as ProjectControllers;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::resource('projects', ProjectControllers\ProjectController::class);
});
