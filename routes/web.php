<?php
use Illuminate\Support\Facades\Route;
use Smayt\UserGameServerCreator\Http\Controllers\ServerCreationController;
use Smayt\UserGameServerCreator\Http\Controllers\ServerDeletionController;
use Smayt\UserGameServerCreator\Http\Controllers\PermissionsController;

Route::post('/ugsc/create-server', [ServerCreationController::class, 'store'])->name('ugsc.create-server');
Route::delete('/ugsc/servers/{serverId}', [ServerDeletionController::class, 'destroy'])->name('ugsc.delete-server');
Route::post('/ugsc/permissions', [PermissionsController::class, 'save'])->name('ugsc.save-permissions');
