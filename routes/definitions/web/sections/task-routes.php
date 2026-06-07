<?php

use App\Http\Controllers\Task as TaskControllers;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::resource('projects.tasks', TaskControllers\TaskController::class)
        ->shallow()
        ->except(['show']);
});
