<?php

namespace Modules\Chat\Http\Controllers;

use Illuminate\Routing\Controller;
use Menu;

class DataController extends Controller
{
    /**
     * Adds the module's sidebar menu entry.
     *
     * @return null
     */
    public function modifyAdminMenu()
    {
        if (auth()->user()->can('chat.access_chat_module')) {
            Menu::modify(
                'admin-sidebar-menu',
                function ($menu) {
                    $menu->url(
                        action([\Modules\Chat\Http\Controllers\ChatController::class, 'index']),
                        __('chat::lang.chat'),
                        ['icon' => 'fas fa-comments fa', 'active' => request()->segment(1) == 'chat']
                    )->order(6);
                }
            );
        }
    }

    /**
     * Defines user permissions for the module.
     *
     * @return array
     */
    public function user_permissions()
    {
        return [
            [
                'value' => 'chat.access_chat_module',
                'label' => __('chat::lang.access_chat_module'),
                'default' => false,
            ],
        ];
    }
}
