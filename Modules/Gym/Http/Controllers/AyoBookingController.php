<?php

namespace Modules\Gym\Http\Controllers;

use App\Business;
use App\Utils\ModuleUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Gym\Entities\AyoBookingLog;
use Modules\Gym\Entities\GymBooking;
use Modules\Gym\Entities\GymCourt;
use Modules\Gym\Services\AyoApiService;

class AyoBookingController extends Controller
{
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Main AYO Bookings dashboard / listing page
     */
    public function index(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $this->checkPermission($business_id);

        $ayoService = new AyoApiService($business_id);
        $configStatus = $ayoService->getConfigStatus();

        // Get recent API logs
        $recentLogs = AyoBookingLog::forBusiness($business_id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('gym::ayo.index', compact('configStatus', 'recentLogs'));
    }

    /**
     * AYO Settings page
     */
    public function settings(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $this->checkPermission($business_id);

        $business = Business::findOrFail($business_id);
        $settings = json_decode($business->gym_settings, true);
        $ayo = $settings['ayo'] ?? [];

        // Get courts for mapping UI
        $courts = GymCourt::where('business_id', $business_id)
            ->where('is_active', true)
            ->with('gymClass')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Get accounting accounts for AYO config
        $accounts = collect();
        if (class_exists('\Modules\Accounting\Entities\AccountingAccount')) {
            $accounts = \Modules\Accounting\Entities\AccountingAccount::where('business_id', $business_id)
                ->whereNull('parent_account_id')
                ->orWhere('business_id', $business_id)
                ->orderBy('gl_code')
                ->get(['id', 'name', 'gl_code']);
        }

        return view('gym::ayo.settings', compact('ayo', 'courts', 'accounts'));
    }

    /**
     * Save AYO Settings
     */
    public function saveSettings(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $this->checkPermission($business_id);

        $request->validate([
            'base_url' => 'required|url',
            'api_token' => 'required|string',
            'venue_code' => 'required|string|max:20',
            'secret_key' => 'required|string',
        ]);

        try {
            $business = Business::findOrFail($business_id);
            $gym_settings = json_decode($business->gym_settings, true) ?? [];

            $gym_settings['ayo'] = [
                'base_url' => rtrim($request->base_url, '/'),
                'api_token' => $request->api_token,
                'venue_code' => $request->venue_code,
                'secret_key' => $request->secret_key,
                'notify_url' => $request->notify_url ?? '',
            ];

            $business->gym_settings = json_encode($gym_settings);
            $business->save();

            return redirect()
                ->action([self::class, 'settings'])
                ->with('status', [
                    'success' => 1,
                    'msg' => __('gym::lang.ayo_settings_saved'),
                ]);
        } catch (\Exception $e) {
            \Log::error('AYO Settings Error: ' . $e->getMessage());

            return back()->with('status', [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ])->withInput();
        }
    }

    /**
     * Test AYO connection
     */
    public function testConnection(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $ayoService = new AyoApiService($business_id);

        if (!$ayoService->isConfigured()) {
            return response()->json([
                'success' => false,
                'msg' => __('gym::lang.ayo_not_configured'),
            ]);
        }

        // Test by fetching venue fields
        $result = $ayoService->getVenueFields();

        return response()->json([
            'success' => $result['success'],
            'msg' => $result['success']
                ? __('gym::lang.ayo_connection_success')
                : __('gym::lang.ayo_connection_failed') . ': ' . $result['message'],
            'data' => $result['data'] ?? [],
        ]);
    }

    // =========================================================================
    // BOOKINGS
    // =========================================================================

    /**
     * Fetch & display AYO bookings
     */
    public function getBookings(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $ayoService = new AyoApiService($business_id);

        if (!$ayoService->isConfigured()) {
            return response()->json([
                'success' => false,
                'msg' => __('gym::lang.ayo_not_configured'),
            ]);
        }

        $filters = array_filter([
            'booking_id' => $request->booking_id,
            'field_name' => $request->field_name,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'booking_source' => $request->booking_source,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status,
        ]);

        $result = $ayoService->getBookings($filters);

        return response()->json([
            'success' => $result['success'],
            'msg' => $result['success'] ? '' : $result['message'],
            'data' => $result['data'],
        ]);
    }

    /**
     * Sync AYO bookings to local system
     */
    public function syncBookings(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $ayoService = new AyoApiService($business_id);

        if (!$ayoService->isConfigured()) {
            return response()->json([
                'success' => false,
                'msg' => __('gym::lang.ayo_not_configured'),
            ]);
        }

        $filters = array_filter([
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        $result = $ayoService->syncBookingsToLocal($filters);

        return response()->json($result);
    }

    // =========================================================================
    // VENUE FIELDS
    // =========================================================================

    /**
     * Get venue fields from AYO
     */
    public function getVenueFields(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $ayoService = new AyoApiService($business_id);

        if (!$ayoService->isConfigured()) {
            return response()->json([
                'success' => false,
                'msg' => __('gym::lang.ayo_not_configured'),
            ]);
        }

        $result = $ayoService->getVenueFields();

        return response()->json([
            'success' => $result['success'],
            'msg' => $result['success'] ? '' : $result['message'],
            'data' => $result['data'],
        ]);
    }

    // =========================================================================
    // CREATE RESERVATION
    // =========================================================================

    /**
     * Show create reservation form
     */
    public function createReservation(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $this->checkPermission($business_id);

        $ayoService = new AyoApiService($business_id);

        if (!$ayoService->isConfigured()) {
            return response()->json([
                'success' => false,
                'msg' => __('gym::lang.ayo_not_configured'),
            ]);
        }

        // Fetch venue fields for the form
        $fieldsResult = $ayoService->getVenueFields();
        $fields = $fieldsResult['success'] ? $fieldsResult['data'] : [];

        return view('gym::ayo.create_reservation', compact('fields'));
    }

    /**
     * Store reservation to AYO
     */
    public function storeReservation(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $request->validate([
            'field_id' => 'required|integer',
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required',
            'end_time' => 'required',
            'total_price' => 'required|numeric|min:0',
        ]);

        $ayoService = new AyoApiService($business_id);

        if (!$ayoService->isConfigured()) {
            return response()->json([
                'success' => false,
                'msg' => __('gym::lang.ayo_not_configured'),
            ]);
        }

        $data = [
            'field_id' => $request->field_id,
            'date' => $request->date,
            'start_time' => $request->start_time . ':00',
            'end_time' => $request->end_time . ':00',
            'total_price' => (int) $request->total_price,
        ];

        // Optional booking_id
        if ($request->filled('booking_id')) {
            $data['booking_id'] = $request->booking_id;
        }

        // Optional user info
        if ($request->filled('user_name') || $request->filled('user_email') || $request->filled('user_phone')) {
            $data['user'] = array_filter([
                'name' => $request->user_name,
                'email' => $request->user_email,
                'phone' => $request->user_phone,
            ]);
        }

        $result = $ayoService->createReservation($data);

        // If AYO reservation was successful, also create a local booking
        if ($result['success']) {
            try {
                $bookingStart = Carbon::parse($request->date . ' ' . $request->start_time . ':00');
                $bookingEnd = Carbon::parse($request->date . ' ' . $request->end_time . ':00');

                // Resolve local court from AYO field_id
                $court = GymCourt::findByAyoFieldId($business_id, (int) $request->field_id);

                GymBooking::create([
                    'business_id' => $business_id,
                    'gym_class_id' => $court->gym_class_id ?? null,
                    'court_id' => $court->id ?? null,
                    'booking_start' => $bookingStart,
                    'booking_end' => $bookingEnd,
                    'duration_minutes' => $bookingStart->diffInMinutes($bookingEnd),
                    'booking_status' => GymBooking::STATUS_CONFIRMED,
                    'walkin_name' => $request->user_name ?? 'AYO Reservation',
                    'walkin_phone' => $request->user_phone ?? '-',
                    'booking_note' => 'Created via AYO Reservation',
                    'ayo_booking_id' => $result['data']['booking_id'] ?? null,
                    'ayo_order_detail_id' => $result['data']['order_detail_id'] ?? null,
                    'ayo_booking_source' => 'internal',
                    'ayo_status' => 'SUCCESS',
                    'ayo_total_price' => (int) $request->total_price,
                    'ayo_synced_at' => now(),
                    'created_by' => auth()->id(),
                ]);
            } catch (\Exception $e) {
                \Log::warning('Failed to create local booking from AYO reservation: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => $result['success'],
            'msg' => $result['success']
                ? __('gym::lang.ayo_reservation_created')
                : __('gym::lang.ayo_reservation_failed') . ': ' . $result['message'],
            'data' => $result['data'],
        ]);
    }

    // =========================================================================
    // CANCEL RESERVATION
    // =========================================================================

    /**
     * Cancel a reservation on AYO
     */
    public function cancelReservation(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $request->validate([
            'order_detail_id' => 'required|integer',
        ]);

        $ayoService = new AyoApiService($business_id);

        if (!$ayoService->isConfigured()) {
            return response()->json([
                'success' => false,
                'msg' => __('gym::lang.ayo_not_configured'),
            ]);
        }

        $result = $ayoService->cancelReservation((int) $request->order_detail_id);

        return response()->json([
            'success' => $result['success'],
            'msg' => $result['success']
                ? __('gym::lang.ayo_reservation_cancelled')
                : __('gym::lang.ayo_cancel_failed') . ': ' . $result['message'],
            'data' => $result['data'],
        ]);
    }

    // =========================================================================
    // WEBHOOK (Notify Booking - receives from AYO)
    // =========================================================================

    /**
     * Webhook endpoint for AYO to send booking notifications
     * This is a public endpoint (no auth middleware)
     */
    public function webhook(Request $request, $venue_code)
    {
        // Find business by venue code
        $businesses = Business::whereNotNull('gym_settings')->get();
        $business = null;

        foreach ($businesses as $biz) {
            $settings = json_decode($biz->gym_settings, true);
            if (($settings['ayo']['venue_code'] ?? '') === $venue_code) {
                $business = $biz;
                break;
            }
        }

        if (!$business) {
            return response()->json([
                'error' => true,
                'status_code' => 401,
                'message' => 'Invalid venue code',
            ], 401);
        }

        $ayoService = new AyoApiService($business->id);
        $bookingData = $request->all();

        // Ensure it's an array of bookings
        if (!isset($bookingData[0])) {
            $bookingData = [$bookingData];
        }

        $result = $ayoService->handleBookingNotification($bookingData);

        return response()->json([
            'error' => $result['error'] ?? false,
            'status_code' => $result['status_code'] ?? 200,
            'message' => $result['message'] ?? 'Success',
        ], $result['status_code'] ?? 200);
    }

    // =========================================================================
    // API LOGS
    // =========================================================================

    /**
     * Get API logs
     */
    public function getLogs(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $query = AyoBookingLog::forBusiness($business_id)
            ->orderBy('created_at', 'desc');

        if ($request->action) {
            $query->where('action', $request->action);
        }
        if ($request->has('is_success')) {
            $query->where('is_success', $request->is_success);
        }

        $logs = $query->paginate(20);

        return response()->json($logs);
    }

    /**
     * View single log detail
     */
    public function logDetail(Request $request, $id)
    {
        $business_id = $request->session()->get('user.business_id');

        $log = AyoBookingLog::forBusiness($business_id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $log->id,
                'action' => $log->action,
                'ayo_booking_id' => $log->ayo_booking_id,
                'request_payload' => json_decode($log->request_payload, true),
                'response_payload' => json_decode($log->response_payload, true),
                'http_status_code' => $log->http_status_code,
                'is_success' => $log->is_success,
                'error_message' => $log->error_message,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Check permission helper
     */
    protected function checkPermission($business_id)
    {
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'gym_module'))) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Save court-to-AYO-field mapping
     */
    public function saveCourtMapping(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $this->checkPermission($business_id);

        try {
            $mappings = $request->input('mapping', []);

            foreach ($mappings as $courtId => $ayoFieldId) {
                $court = GymCourt::where('business_id', $business_id)->find($courtId);
                if ($court) {
                    $court->update(['ayo_field_id' => $ayoFieldId ?: null]);
                }
            }

            // Also save AYO accounting accounts if provided
            if ($request->has('ayo_asset_account_id') || $request->has('ayo_income_account_id')) {
                $business = Business::findOrFail($business_id);
                $gym_settings = json_decode($business->gym_settings, true) ?? [];
                $gym_settings['ayo']['asset_account_id'] = $request->ayo_asset_account_id ?: null;
                $gym_settings['ayo']['income_account_id'] = $request->ayo_income_account_id ?: null;
                $business->gym_settings = json_encode($gym_settings);
                $business->save();
            }

            return response()->json([
                'success' => true,
                'msg' => __('gym::lang.court_mapping_saved'),
            ]);
        } catch (\Exception $e) {
            \Log::error('Court mapping save error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 500);
        }
    }
}
