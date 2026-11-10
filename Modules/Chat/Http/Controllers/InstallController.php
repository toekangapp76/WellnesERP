<?php

namespace Modules\Chat\Http\Controllers;

use App\System;
use Composer\Semver\Comparator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class InstallController extends Controller
{
    protected $module_name = 'chat';

    protected $appVersion;

    public function __construct()
    {
        $this->appVersion = config('chat.module_version');
    }

    public function index()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M');

        try {
            $is_installed = System::getProperty($this->module_name.'_version');
            if (! empty($is_installed)) {
                abort(404);
            }

            Artisan::call('config:clear');
            DB::statement('SET default_storage_engine=INNODB;');
            Artisan::call('module:migrate', ['module' => 'Chat', '--force' => true]);
            Artisan::call('module:publish', ['module' => 'Chat']);
            System::addProperty($this->module_name.'_version', $this->appVersion);

            $output = ['success' => 1,
                'msg' => 'Chat module installed succesfully',
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => $e->getMessage(),
            ];
        }

        return redirect()
            ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
            ->with('status', $output);
    }

    public function uninstall()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            System::removeProperty($this->module_name.'_version');

            $output = ['success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            $output = ['success' => false,
                'msg' => $e->getMessage(),
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    public function update()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M');

        $installed_version = System::getProperty($this->module_name.'_version');
        if (! Comparator::greaterThan($this->appVersion, $installed_version)) {
            abort(404);
        }

        try {
            Artisan::call('config:clear');
            DB::statement('SET default_storage_engine=INNODB;');
            Artisan::call('module:migrate', ['module' => 'Chat', '--force' => true]);
            Artisan::call('module:publish', ['module' => 'Chat']);
            System::setProperty($this->module_name.'_version', $this->appVersion);

            $output = ['success' => 1,
                'msg' => 'Chat module updated succesfully to version '.$this->appVersion.' !!',
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => $e->getMessage(),
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }
}
