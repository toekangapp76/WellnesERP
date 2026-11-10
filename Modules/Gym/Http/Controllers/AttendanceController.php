<?php

namespace Modules\Gym\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Contact;
use App\Transaction;
use App\Business;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\ContactUtil;
use App\Utils\Util;
use App\Utils\ModuleUtil;
use Carbon\Carbon;
use Modules\Gym\Entities\GymAttendance;
use Modules\Gym\Entities\GymPackage;
use Modules\Gym\Services\SessionTrackingService;
use Illuminate\Support\Facades\Crypt;


class AttendanceController extends Controller
{
    protected $contactUtil;
    protected $commonUtil;
    protected $moduleUtil;
    protected $sessionTrackingService;

    public function __construct(
        ContactUtil $contactUtil,
        Util $commonUtil,
        ModuleUtil $moduleUtil,
        SessionTrackingService $sessionTrackingService,
    ) {
        $this->contactUtil = $contactUtil;
        $this->commonUtil = $commonUtil;
        $this->moduleUtil = $moduleUtil;
        $this->sessionTrackingService = $sessionTrackingService;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'gym_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (!auth()->user()->can('gym.manage_attendance')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            // Get filter date or default to today (expects Y-m-d format from datepicker)
            $filterDate = $request->filter_date ?? Carbon::today()->toDateString();
            
            // Validate date format
            try {
                $filterDate = Carbon::parse($filterDate)->toDateString();
            } catch (\Exception $e) {
                $filterDate = Carbon::today()->toDateString();
            }

            $query = GymAttendance::where('gym_attendances.contact_id', '!=', null)
                ->join('contacts', 'gym_attendances.contact_id', '=', 'contacts.id')
                ->where('contacts.business_id', $business_id)
                ->whereDate('gym_attendances.date', $filterDate)
                ->select([
                    'gym_attendances.*',
                    'contacts.name',
                    'contacts.email',
                    'contacts.mobile',
                ])
                ->orderBy('gym_attendances.id', 'desc');

            return Datatables::of($query)
                ->editColumn('date', function($row) {
                    return $this->commonUtil->format_date($row->date);
                })
                ->addColumn('in', function ($row) {
                    return $row->in_time ? $this->commonUtil->format_time($row->in_time) : '-';
                })
                ->addColumn('out', function ($row) {
                    return $row->out_time ? $this->commonUtil->format_time($row->out_time) : '-';
                })
                ->addColumn('duration', function ($row) {
                    if ($row->duration_minutes) {
                        $hours = floor($row->duration_minutes / 60);
                        $mins = $row->duration_minutes % 60;
                        return $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
                    }
                    return '-';
                })
                ->addColumn('action', function ($row) {
                    // Edit check-in button
                    $html = '<a type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-success btn-modal-in" href="' . action([\Modules\Gym\Http\Controllers\AttendanceController::class, 'get_in'], ['id' => $row->contact_id]) . '">
                        In
                    </a> ';
                    // Edit check-out button
                    $html .= '<a type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-warning btn-modal-out" href="' . action([\Modules\Gym\Http\Controllers\AttendanceController::class, 'get_out'], ['id' => $row->contact_id]) . '">
                        Out
                    </a> ';
                    // Delete button
                    $html .= '<button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-error delete-attendance" data-id="' . $row->id . '">
                        <i class="fa fa-trash"></i>
                    </button>';
                    return $html;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('gym::attendance.index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('gym::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('gym::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('gym::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    public function get_in($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'gym_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (!auth()->user()->can('gym.manage_attendance')) {
            abort(403, 'Unauthorized action.');
        }
        $member = Contact::findOrFail($id);
        
        $attendance = GymAttendance::where('contact_id', $id)->whereDate('date', Carbon::today())->first();

        return view('gym::attendance.in_time', compact('member', 'attendance'));
    }

    public function add_edit_in_time(Request $request)
    {

        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'gym_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if(!auth()->user()->can( 'gym.manage_attendance')){
            abort(403, 'Unauthorized action.');
        }


        try {
            // Check if member has active subscription first
            $sessionStatus = $this->sessionTrackingService->getMemberSessionStatus($request->contact_id, $business_id);
            
            if (!$sessionStatus['has_active_subscription']) {
                return response()->json([
                    'success' => false,
                    'msg' => __('gym::lang.no_active_subscription_checkin_denied'),
                ]);
            }
            
            // Check if quota is exhausted
            if ($sessionStatus['quota_exhausted'] ?? false) {
                return response()->json([
                    'success' => false,
                    'msg' => __('gym::lang.quota_exhausted_checkin_denied'),
                ]);
            }
            
            $inTime = $this->commonUtil->uf_time($request->in_time);
            
            // Use session tracking service for check-in
            $result = $this->sessionTrackingService->processCheckIn(
                $request->contact_id,
                $business_id,
                $inTime
            );

            $response = [
                'success' => 1,
                'msg' => __('lang_v1.success'),
            ];

            // Add session info to response
            if ($result['has_active_subscription']) {
                $response['has_subscription'] = true;
                $response['has_session_limit'] = $result['has_session_limit'] ?? false;
                $response['has_session_count_limit'] = $result['has_session_count_limit'] ?? false;
                if (isset($result['remaining_minutes'])) {
                    $response['remaining_minutes'] = $result['remaining_minutes'];
                }
                if (isset($result['remaining_sessions'])) {
                    $response['remaining_sessions'] = $result['remaining_sessions'];
                }
            } else {
                $response['has_subscription'] = false;
                $response['warning'] = __('gym::lang.no_active_subscription');
            }

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    public function get_out($id)
    {
        $business_id = request()->session()->get('user.business_id');
        
        $member = Contact::findOrFail($id);

        $attendance = GymAttendance::where('contact_id', $id)->whereDate('date', Carbon::today())->first();

        // Get session status for display
        $sessionStatus = $this->sessionTrackingService->getMemberSessionStatus($id, $business_id);

        return view('gym::attendance.out_time', compact('member', 'attendance', 'sessionStatus'));
    }

    public function add_edit_out_time(Request $request)
    {

        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'gym_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if(!auth()->user()->can( 'gym.manage_attendance')){
            abort(403, 'Unauthorized action.');
        }

        
        try {
            $outTime = $this->commonUtil->uf_time($request->out_time);
            
            // Use session tracking service for check-out
            $result = $this->sessionTrackingService->processCheckOut(
                $request->contact_id,
                $business_id,
                $outTime
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? __('messages.something_went_wrong'),
                ]);
            }

            $response = [
                'success' => 1,
                'msg' => __('lang_v1.success'),
                'duration_minutes' => $result['duration_minutes'] ?? 0,
            ];

            // Add session tracking info
            if ($result['session_deducted']) {
                $response['session_deducted'] = true;
                
                if (isset($result['remaining_minutes'])) {
                    $response['remaining_minutes'] = $result['remaining_minutes'];
                }
                if (isset($result['remaining_sessions'])) {
                    $response['remaining_sessions'] = $result['remaining_sessions'];
                }
                
                if (isset($result['package_exhausted']) && $result['package_exhausted']) {
                    $response['package_exhausted'] = true;
                    $response['warning'] = __('gym::lang.package_exhausted');
                }

                if (isset($result['overtime_minutes']) && $result['overtime_minutes'] > 0) {
                    $response['overtime_minutes'] = $result['overtime_minutes'];
                    $response['overtime_handled'] = $result['overtime_handled'] ?? 'recorded';
                }
            }

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete attendance record
     */
    public function deleteAttendance(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'gym_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if(!auth()->user()->can('gym.manage_attendance')){
            abort(403, 'Unauthorized action.');
        }

        try {
            $attendance = GymAttendance::findOrFail($request->id);
            $attendance->delete();

            return response()->json([
                'success' => true,
                'msg' => __('lang_v1.success'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Public attendance scanner page
     */
    public function publicScanner()
    {
        return view('gym::attendance.public_scanner');
    }

    /**
     * Process public check-in/check-out via QR code
     * Check-in requires OTP verification, Check-out is direct
     */
    public function publicCheckInOut(Request $request)
    {
        try {
            $memberId = Crypt::decryptString($request->member_id);
            $contact = Contact::findOrFail($memberId);
            $business_id = $contact->business_id;

            // Get business timezone
            $business = Business::find($business_id);
            $timezone = $business->time_zone ?? config('app.timezone');
            
            $today = Carbon::now($timezone)->toDateString();
            $now = Carbon::now($timezone)->format('H:i:s');

            // Get the latest attendance for today
            $latestAttendance = GymAttendance::where('contact_id', $memberId)
                ->whereDate('date', $today)
                ->orderBy('id', 'desc')
                ->first();

            // Determine action: check-in or check-out
            if (!$latestAttendance || ($latestAttendance->in_time && $latestAttendance->out_time)) {
                // No attendance today OR last session completed -> New check-in REQUIRES OTP
                
                // Check if member has active subscription first
                $sessionStatus = $this->sessionTrackingService->getMemberSessionStatus($memberId, $business_id);
                
                if (!$sessionStatus['has_active_subscription']) {
                    return response()->json([
                        'success' => false,
                        'action' => 'check_in_denied',
                        'message' => __('gym::lang.no_active_subscription_checkin_denied'),
                        'member_name' => $contact->name,
                        'time' => Carbon::now($timezone)->format('H:i'),
                    ]);
                }
                
                // Check if quota is exhausted
                if ($sessionStatus['quota_exhausted'] ?? false) {
                    return response()->json([
                        'success' => false,
                        'action' => 'check_in_denied',
                        'message' => __('gym::lang.quota_exhausted_checkin_denied'),
                        'member_name' => $contact->name,
                        'time' => Carbon::now($timezone)->format('H:i'),
                    ]);
                }
                
                // Check if member has email for OTP
                if (empty($contact->email)) {
                    return response()->json([
                        'success' => false,
                        'action' => 'check_in_denied',
                        'message' => __('gym::lang.no_email_for_otp'),
                        'member_name' => $contact->name,
                        'time' => Carbon::now($timezone)->format('H:i'),
                    ]);
                }
                
                // Check-in requires OTP - return status to show OTP form
                return response()->json([
                    'success' => true,
                    'action' => 'require_otp',
                    'member_name' => $contact->name,
                    'member_id' => $request->member_id, // encrypted
                    'email_hint' => $this->maskEmail($contact->email),
                    'time' => Carbon::now($timezone)->format('H:i'),
                    'message' => __('gym::lang.otp_required_for_checkin'),
                ]);

            } elseif ($latestAttendance->in_time && !$latestAttendance->out_time) {
                // Has check-in but no check-out -> Check-out (NO OTP REQUIRED)
                $action = 'check_out';

                $result = $this->sessionTrackingService->processCheckOut(
                    $memberId,
                    $business_id,
                    $now,
                    $today
                );

                $message = __('gym::lang.check_out_success');

                // Calculate duration
                $durationInfo = null;
                if (isset($result['duration_minutes'])) {
                    $hours = floor($result['duration_minutes'] / 60);
                    $mins = $result['duration_minutes'] % 60;
                    $durationInfo = $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
                }

                // Remaining info
                $remainingInfo = null;
                $infoArr = [];
                if (isset($result['remaining_minutes'])) {
                    $hours = floor($result['remaining_minutes'] / 60);
                    $mins = $result['remaining_minutes'] % 60;
                    $infoArr[] = "{$hours}h {$mins}m";
                }
                if (isset($result['remaining_sessions']) && $result['remaining_sessions'] !== null) {
                    $infoArr[] = $result['remaining_sessions'] . ' ' . __('gym::lang.sessions');
                }
                if (!empty($infoArr)) {
                    $remainingInfo = implode(' | ', $infoArr) . ' ' . __('gym::lang.remaining');
                }

                // Get active subscription info for display
                $subscription = $this->sessionTrackingService->getActiveSubscription($memberId, $business_id);
                $hasSubscription = $subscription !== null;
                
                return response()->json([
                    'success' => true,
                    'action' => $action,
                    'message' => $message,
                    'member_name' => $contact->name,
                    'time' => $this->commonUtil->format_time($now),
                    'duration' => $durationInfo,
                    'remaining_info' => $remainingInfo,
                    'has_subscription' => $hasSubscription,
                    'package_exhausted' => $result['package_exhausted'] ?? false,
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Mask email for display (e.g., j***@gmail.com)
     */
    private function maskEmail($email)
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return '***@***';
        
        $name = $parts[0];
        $domain = $parts[1];
        
        if (strlen($name) <= 2) {
            $masked = $name[0] . '***';
        } else {
            $masked = $name[0] . str_repeat('*', strlen($name) - 2) . substr($name, -1);
        }
        
        return $masked . '@' . $domain;
    }

    /**
     * Process check-in after OTP verification (called from scanner)
     */
    public function publicCheckInWithOtp(Request $request)
    {
        try {
            $memberId = Crypt::decryptString($request->member_id);
            $otp = $request->otp;
            
            $contact = Contact::findOrFail($memberId);
            $business_id = $contact->business_id;

            // Verify OTP
            $otpRecord = \DB::table('gym_otp_codes')
                ->where('contact_id', $memberId)
                ->where('business_id', $business_id)
                ->where('otp_code', $otp)
                ->where('expires_at', '>', Carbon::now())
                ->where('is_used', false)
                ->first();

            if (!$otpRecord) {
                return response()->json([
                    'success' => false,
                    'action' => 'otp_invalid',
                    'message' => __('gym::lang.otp_invalid_or_expired'),
                ]);
            }

            // Mark OTP as used
            \DB::table('gym_otp_codes')
                ->where('id', $otpRecord->id)
                ->update(['is_used' => true]);

            // Get business timezone
            $business = Business::find($business_id);
            $timezone = $business->time_zone ?? config('app.timezone');
            
            $today = Carbon::now($timezone)->toDateString();
            $now = Carbon::now($timezone)->format('H:i:s');

            // Process check-in
            $result = $this->sessionTrackingService->processCheckIn(
                $memberId,
                $business_id,
                $now,
                $today
            );

            $message = __('gym::lang.check_in_success');

            // Get session info
            $sessionInfo = null;
            if ($result['has_active_subscription']) {
                $infoArr = [];
                if (isset($result['remaining_minutes'])) {
                    $hours = floor($result['remaining_minutes'] / 60);
                    $mins = $result['remaining_minutes'] % 60;
                    $infoArr[] = "{$hours}h {$mins}m";
                }
                if (isset($result['remaining_sessions']) && $result['remaining_sessions'] !== null) {
                    $infoArr[] = $result['remaining_sessions'] . ' ' . __('gym::lang.sessions');
                }
                if (!empty($infoArr)) {
                    $sessionInfo = implode(' | ', $infoArr) . ' ' . __('gym::lang.remaining');
                }
            }

            return response()->json([
                'success' => true,
                'action' => 'check_in',
                'message' => $message,
                'member_name' => $contact->name,
                'time' => $this->commonUtil->format_time($now),
                'has_subscription' => $result['has_active_subscription'],
                'session_info' => $sessionInfo,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Send OTP for public scanner check-in
     */
    public function publicSendOtp(Request $request)
    {
        try {
            \Log::info('Public Send OTP - Start', ['member_id_encrypted' => $request->member_id]);
            
            $memberId = Crypt::decryptString($request->member_id);
            \Log::info('Public Send OTP - Decrypted member ID', ['member_id' => $memberId]);
            
            $contact = Contact::findOrFail($memberId);
            \Log::info('Public Send OTP - Contact found', ['contact_id' => $contact->id, 'email' => $contact->email]);
            
            $business_id = $contact->business_id;

            if (empty($contact->email)) {
                return response()->json([
                    'success' => false,
                    'message' => __('gym::lang.no_email_for_otp'),
                ]);
            }

            // Generate 6-digit OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            \Log::info('Public Send OTP - OTP generated', ['otp' => $otp]);

            // Delete any existing unused OTPs for this contact
            \Log::info('Public Send OTP - Deleting old OTPs', ['contact_id' => $memberId]);
            \DB::table('gym_otp_codes')
                ->where('contact_id', $memberId)
                ->where('is_used', false)
                ->delete();
            \Log::info('Public Send OTP - Old OTPs deleted');

            // Store OTP (valid for 10 minutes)
            \Log::info('Public Send OTP - Inserting new OTP', [
                'business_id' => $business_id,
                'contact_id' => $memberId,
                'expires_at' => Carbon::now()->addMinutes(10)->toDateTimeString()
            ]);
            \DB::table('gym_otp_codes')->insert([
                'business_id' => $business_id,
                'contact_id' => $memberId,
                'otp_code' => $otp,
                'expires_at' => Carbon::now()->addMinutes(10),
                'is_used' => false,
                'created_at' => Carbon::now(),
            ]);
            \Log::info('Public Send OTP - OTP inserted successfully');

            // Get business and configure email from business settings
            $business = Business::find($business_id);
            \Log::info('Public Send OTP - Business found', ['business_name' => $business->name ?? 'N/A']);
            
            // Configure email settings from business database
            $this->configureEmailFromBusiness($business);
            
            $businessName = $business->name ?? 'Fitness Center';
            $email_settings = $business->email_settings ?? [];
            $fromAddress = !empty($email_settings['mail_from_address']) ? $email_settings['mail_from_address'] : config('mail.from.address');
            $fromName = !empty($email_settings['mail_from_name']) ? $email_settings['mail_from_name'] : $businessName;
            
            \Log::info('Public Send OTP - Sending email', [
                'to' => $contact->email,
                'from_address' => $fromAddress,
                'from_name' => $fromName
            ]);
            
            \Mail::send([], [], function ($message) use ($contact, $otp, $businessName, $fromAddress, $fromName) {
                $message->from($fromAddress, $fromName)
                    ->to($contact->email, $contact->name)
                    ->subject('Kode Verifikasi Check-in - ' . $businessName)
                    ->html("
                        <div style='font-family: Arial, Helvetica, sans-serif; max-width: 500px; margin: 0 auto; padding: 20px;'>
                            
                            <div style='text-align: center; padding: 20px 0; border-bottom: 2px solid #3b82f6;'>
                                <h2 style='color: #1e293b; margin: 0; font-size: 20px; font-weight: 600;'>{$businessName}</h2>
                            </div>
                            
                            <div style='padding: 30px 0;'>
                                <p style='color: #334155; font-size: 15px; margin: 0 0 20px; line-height: 1.5;'>
                                    Halo <strong>{$contact->name}</strong>,
                                </p>
                                
                                <p style='color: #475569; font-size: 14px; margin: 0 0 25px; line-height: 1.5;'>
                                    Gunakan kode verifikasi berikut untuk melakukan check-in:
                                </p>
                                
                                <div style='background: #f1f5f9; border-radius: 8px; padding: 25px; text-align: center; margin: 0 0 25px;'>
                                    <p style='color: #64748b; font-size: 12px; margin: 0 0 10px; text-transform: uppercase; letter-spacing: 1px;'>Kode OTP Anda</p>
                                    <p style='color: #1e40af; font-size: 32px; font-weight: 700; letter-spacing: 8px; margin: 0; font-family: Consolas, Monaco, monospace;'>{$otp}</p>
                                </div>
                                
                                <p style='color: #dc2626; font-size: 13px; margin: 0 0 20px; background: #fef2f2; padding: 12px 15px; border-radius: 6px;'>
                                    ⏱ Kode ini berlaku selama <strong>10 menit</strong>
                                </p>
                                
                                <p style='color: #64748b; font-size: 13px; margin: 0; line-height: 1.5;'>
                                    Jika Anda tidak meminta kode ini, abaikan email ini.
                                </p>
                            </div>
                            
                            <div style='border-top: 1px solid #e2e8f0; padding-top: 20px; text-align: center;'>
                                <p style='color: #94a3b8; font-size: 12px; margin: 0;'>
                                    {$businessName}<br>
                                    Email ini dikirim secara otomatis
                                </p>
                            </div>
                            
                        </div>
                    ");
            });
            \Log::info('Public Send OTP - Email sent successfully');

            return response()->json([
                'success' => true,
                'message' => __('gym::lang.otp_sent_to_email'),
                'email_hint' => $this->maskEmail($contact->email),
            ]);

        } catch (\Exception $e) {
            \Log::error('Public Send OTP Error: ' . $e->getMessage());
            \Log::error('Public Send OTP Stack Trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => __('messages.something_went_wrong'),
            ]);
        }
    }

    /**
     * Configure email settings from business database
     */
    private function configureEmailFromBusiness($business)
    {
        $email_settings = $business->email_settings ?? [];
        
        // Check if using superadmin settings
        $is_superadmin_settings_allowed = \App\System::getProperty('allow_email_settings_to_businesses');
        
        if (!empty($is_superadmin_settings_allowed) && !empty($email_settings['use_superadmin_settings'])) {
            // Use superadmin settings from config - don't modify config
            return;
        }
        
        // Get email settings from business
        $mail_host = $email_settings['mail_host'] ?? config('mail.mailers.smtp.host');
        $mail_port = $email_settings['mail_port'] ?? config('mail.mailers.smtp.port');
        $mail_username = $email_settings['mail_username'] ?? config('mail.mailers.smtp.username');
        $mail_password = $email_settings['mail_password'] ?? config('mail.mailers.smtp.password');
        $mail_encryption = $email_settings['mail_encryption'] ?? config('mail.mailers.smtp.encryption');
        $mail_from_address = $email_settings['mail_from_address'] ?? config('mail.from.address');
        $mail_from_name = $email_settings['mail_from_name'] ?? config('mail.from.name');
        
        // Set complete SMTP configuration
        \Config::set('mail.default', 'smtp');
        \Config::set('mail.mailers.smtp', [
            'transport' => 'smtp',
            'host' => $mail_host,
            'port' => $mail_port,
            'encryption' => $mail_encryption,
            'username' => $mail_username,
            'password' => $mail_password,
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ]);
        \Config::set('mail.from.address', $mail_from_address);
        \Config::set('mail.from.name', $mail_from_name);
        
        // Purge the mail manager to apply new settings
        app()->forgetInstance('mail.manager');
        
        \Log::info('Email configured from business settings', [
            'mail_host' => $mail_host,
            'mail_port' => $mail_port,
            'mail_from_address' => $mail_from_address,
            'mail_from_name' => $mail_from_name,
        ]);
    }

    /**
     * Get member attendance status for display
     */
    public function getMemberStatus(Request $request)
    {
        try {
            $memberId = Crypt::decryptString($request->member_id);
            $contact = Contact::findOrFail($memberId);
            $business_id = $contact->business_id;

            $today = Carbon::today()->toDateString();

            // Get today's attendances
            $todayAttendances = GymAttendance::where('contact_id', $memberId)
                ->whereDate('date', $today)
                ->orderBy('id', 'desc')
                ->get();

            // Get session status
            $sessionStatus = $this->sessionTrackingService->getMemberSessionStatus($memberId, $business_id);

            // Determine current status
            $latestAttendance = $todayAttendances->first();
            $currentStatus = 'not_checked_in';
            
            if ($latestAttendance) {
                if ($latestAttendance->in_time && !$latestAttendance->out_time) {
                    $currentStatus = 'checked_in';
                } elseif ($latestAttendance->in_time && $latestAttendance->out_time) {
                    $currentStatus = 'checked_out';
                }
            }

            return response()->json([
                'success' => true,
                'member_name' => $contact->name,
                'member_photo' => $contact->gym_member_profile_photo,
                'current_status' => $currentStatus,
                'today_sessions' => $todayAttendances->count(),
                'session_status' => $sessionStatus,
                'next_action' => $currentStatus === 'checked_in' ? 'check_out' : 'check_in',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Send OTP to member's email for check-in verification
     */
    public function sendOtp(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $contact_id = $request->contact_id;

            $contact = Contact::where('business_id', $business_id)
                ->where('id', $contact_id)
                ->first();

            if (!$contact) {
                return response()->json([
                    'success' => false,
                    'message' => __('gym::lang.member_not_found'),
                ]);
            }

            if (empty($contact->email)) {
                return response()->json([
                    'success' => false,
                    'message' => __('gym::lang.email_not_available'),
                ]);
            }

            // Generate 6-digit OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = now()->addMinutes(10);

            // Create or update attendance record with OTP
            $attendance = GymAttendance::updateOrCreate(
                [
                    'contact_id' => $contact_id,
                    'date' => now()->format('Y-m-d'),
                    'out_time' => null,
                ],
                [
                    'otp_code' => $otp,
                    'otp_expires_at' => $expiresAt,
                    'otp_verified' => false,
                ]
            );

            // Send email with OTP
            $business = \App\Business::find($business_id);
            
            // Configure email settings from business database
            $this->configureEmailFromBusiness($business);
            
            $businessName = $business->name ?? 'Fitness Center';
            $email_settings = $business->email_settings ?? [];
            $fromAddress = !empty($email_settings['mail_from_address']) ? $email_settings['mail_from_address'] : config('mail.from.address');
            $fromName = !empty($email_settings['mail_from_name']) ? $email_settings['mail_from_name'] : $businessName;
            
            \Mail::send([], [], function ($message) use ($contact, $otp, $businessName, $fromAddress, $fromName) {
                $message->from($fromAddress, $fromName)
                    ->to($contact->email, $contact->name)
                    ->subject('Kode Verifikasi Check-in - ' . $businessName)
                    ->html("
                        <div style='font-family: Arial, Helvetica, sans-serif; max-width: 500px; margin: 0 auto; padding: 20px;'>
                            
                            <div style='text-align: center; padding: 20px 0; border-bottom: 2px solid #3b82f6;'>
                                <h2 style='color: #1e293b; margin: 0; font-size: 20px; font-weight: 600;'>{$businessName}</h2>
                            </div>
                            
                            <div style='padding: 30px 0;'>
                                <p style='color: #334155; font-size: 15px; margin: 0 0 20px; line-height: 1.5;'>
                                    Halo <strong>{$contact->name}</strong>,
                                </p>
                                
                                <p style='color: #475569; font-size: 14px; margin: 0 0 25px; line-height: 1.5;'>
                                    Gunakan kode verifikasi berikut untuk melakukan check-in:
                                </p>
                                
                                <div style='background: #f1f5f9; border-radius: 8px; padding: 25px; text-align: center; margin: 0 0 25px;'>
                                    <p style='color: #64748b; font-size: 12px; margin: 0 0 10px; text-transform: uppercase; letter-spacing: 1px;'>Kode OTP Anda</p>
                                    <p style='color: #1e40af; font-size: 32px; font-weight: 700; letter-spacing: 8px; margin: 0; font-family: Consolas, Monaco, monospace;'>{$otp}</p>
                                </div>
                                
                                <p style='color: #dc2626; font-size: 13px; margin: 0 0 20px; background: #fef2f2; padding: 12px 15px; border-radius: 6px;'>
                                    ⏱ Kode ini berlaku selama <strong>10 menit</strong>
                                </p>
                                
                                <p style='color: #64748b; font-size: 13px; margin: 0; line-height: 1.5;'>
                                    Jika Anda tidak meminta kode ini, abaikan email ini.
                                </p>
                            </div>
                            
                            <div style='border-top: 1px solid #e2e8f0; padding-top: 20px; text-align: center;'>
                                <p style='color: #94a3b8; font-size: 12px; margin: 0;'>
                                    {$businessName}<br>
                                    Email ini dikirim secara otomatis
                                </p>
                            </div>
                            
                        </div>
                    ");
            });

            return response()->json([
                'success' => true,
                'message' => __('gym::lang.otp_sent_to_email'),
            ]);

        } catch (\Exception $e) {
            \Log::error('Send OTP Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.something_went_wrong'),
            ]);
        }
    }

    /**
     * Verify OTP for check-in
     */
    public function verifyOtp(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $contact_id = $request->contact_id;
            $otp = $request->otp;

            $contact = Contact::where('business_id', $business_id)
                ->where('id', $contact_id)
                ->first();

            if (!$contact) {
                return response()->json([
                    'success' => false,
                    'message' => __('gym::lang.member_not_found'),
                ]);
            }

            $attendance = GymAttendance::where('contact_id', $contact_id)
                ->where('date', now()->format('Y-m-d'))
                ->whereNull('out_time')
                ->first();

            if (!$attendance || $attendance->otp_code !== $otp) {
                return response()->json([
                    'success' => false,
                    'message' => __('gym::lang.otp_invalid'),
                ]);
            }

            if ($attendance->otp_expires_at && now()->isAfter($attendance->otp_expires_at)) {
                return response()->json([
                    'success' => false,
                    'message' => __('gym::lang.otp_expired'),
                ]);
            }

            // Mark OTP as verified and complete check-in
            $attendance->otp_verified = true;
            $attendance->in_time = now()->format('H:i:s');
            $attendance->save();

            return response()->json([
                'success' => true,
                'message' => __('gym::lang.otp_verified'),
                'action' => 'check_in',
                'member_name' => $contact->name,
                'time' => now()->format('H:i'),
            ]);

        } catch (\Exception $e) {
            \Log::error('Verify OTP Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.something_went_wrong'),
            ]);
        }
    }
}
