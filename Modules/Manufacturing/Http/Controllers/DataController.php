<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Routing\Controller;
use Menu;

class DataController extends Controller
{
    /**
     * Defines module as a superadmin package.
     * @return Array
     */
    public function superadmin_package()
    {
        return [
            [
                'name' => 'manufacturing_module',
                'label' => __('manufacturing::lang.manufacturing_module'),
                'default' => false
            ]
        ];
    }

    /**
     * Adds Manufacturing menu to the admin sidebar.
     *
     * @return null
     */
    public function modifyAdminMenu()
    {
        $business_id = session()->get('user.business_id');
        $module_util = new ModuleUtil();

        $is_manufacturing_enabled = (bool) $module_util->hasThePermissionInSubscription($business_id, 'manufacturing_module');

        if ($is_manufacturing_enabled && (auth()->user()->can('manufacturing.access_recipe') || auth()->user()->can('manufacturing.access_production'))) {
            Menu::modify(
                'admin-sidebar-menu',
                function ($menu) {
                    $menu->dropdown(
                        __('manufacturing::lang.manufacturing'),
                        function ($sub) {
                            if (auth()->user()->can('manufacturing.access_recipe')) {
                                $sub->url(
                                    action([\Modules\Manufacturing\Http\Controllers\RecipeController::class, 'index']),
                                    __('manufacturing::lang.recipe'),
                                    ['icon' => '', 'active' => request()->segment(1) == 'manufacturing' && in_array(request()->segment(2), ['recipe', 'add-ingredient'])]
                                );
                            }
                            if (auth()->user()->can('manufacturing.access_production')) {
                                $sub->url(
                                    action([\Modules\Manufacturing\Http\Controllers\ProductionController::class, 'index']),
                                    __('manufacturing::lang.production'),
                                    ['icon' => '', 'active' => request()->segment(1) == 'manufacturing' && request()->segment(2) == 'production' && empty(request()->segment(3))]
                                );
                                $sub->url(
                                    action([\Modules\Manufacturing\Http\Controllers\ProductionController::class, 'create']),
                                    __('manufacturing::lang.add_production'),
                                    ['icon' => '', 'active' => request()->segment(1) == 'manufacturing' && request()->segment(2) == 'production' && request()->segment(3) == 'create']
                                );
                                $sub->url(
                                    action([\Modules\Manufacturing\Http\Controllers\ProductionController::class, 'getManufacturingReport']),
                                    __('manufacturing::lang.manufacturing_report'),
                                    ['icon' => '', 'active' => request()->segment(1) == 'manufacturing' && request()->segment(2) == 'report']
                                );
                                $sub->url(
                                    action([\Modules\Manufacturing\Http\Controllers\SettingsController::class, 'index']),
                                    __('messages.settings'),
                                    ['icon' => '', 'active' => request()->segment(1) == 'manufacturing' && request()->segment(2) == 'settings']
                                );
                            }
                        },
                        ['icon' => 'fas fa-industry fa']
                    )->order(28);
                }
            );
        }
    }

    /**
     * Defines user permissions for the module.
     * @return array
     */
    public function user_permissions()
    {
        return [
            [
                'value' => 'manufacturing.access_recipe',
                'label' => __('manufacturing::lang.access_recipe'),
                'default' => false
            ],
            [
                'value' => 'manufacturing.add_recipe',
                'label' => __('manufacturing::lang.add_recipe'),
                'default' => false
            ],
            [
                'value' => 'manufacturing.edit_recipe',
                'label' => __('manufacturing::lang.edit_recipe'),
                'default' => false
            ],
            [
                'value' => 'manufacturing.access_production',
                'label' => __('manufacturing::lang.access_production'),
                'default' => false
            ]
        ];
    }
}
