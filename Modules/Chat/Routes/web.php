<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware('web', 'SetSessionData', 'auth', 'language', 'timezone', 'AdminSidebarMenu')->prefix('chat')->group(function () {
    Route::get('install', [\Modules\Chat\Http\Controllers\InstallController::class, 'index']);
    Route::get('install/uninstall', [\Modules\Chat\Http\Controllers\InstallController::class, 'uninstall']);
    Route::get('install/update', [\Modules\Chat\Http\Controllers\InstallController::class, 'update']);

    Route::get('/', [\Modules\Chat\Http\Controllers\ChatController::class, 'index'])->name('chat.index');
    Route::get('contacts', [\Modules\Chat\Http\Controllers\ChatController::class, 'contacts'])->name('chat.contacts');
    Route::get('unread-count', [\Modules\Chat\Http\Controllers\ChatController::class, 'unreadCount'])->name('chat.unread-count');
    Route::post('send', [\Modules\Chat\Http\Controllers\ChatController::class, 'send'])->name('chat.send');
    Route::get('messages/{user_id}', [\Modules\Chat\Http\Controllers\ChatController::class, 'messages'])->name('chat.messages');
});
