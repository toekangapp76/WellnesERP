<?php
use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Public routes for QR scanner
Route::get('/gym-member-scanner', [Modules\Gym\Http\Controllers\MemberController::class, 'member_scanner'])->name('member_scanner');
Route::get('/get-signed-route', [Modules\Gym\Http\Controllers\MemberController::class, 'get_signed_route'])->name('get_signed_route');

// Public attendance scanner routes
Route::get('/gym-attendance-scanner', [Modules\Gym\Http\Controllers\AttendanceController::class, 'publicScanner'])->name('gym.public_scanner');
Route::post('/gym-public-checkinout', [Modules\Gym\Http\Controllers\AttendanceController::class, 'publicCheckInOut'])->name('gym.public_checkinout');
Route::post('/gym-member-status', [Modules\Gym\Http\Controllers\AttendanceController::class, 'getMemberStatus'])->name('gym.member_status');
Route::post('/gym-public-send-otp', [Modules\Gym\Http\Controllers\AttendanceController::class, 'publicSendOtp'])->name('gym.public_send_otp');
Route::post('/gym-public-checkin-otp', [Modules\Gym\Http\Controllers\AttendanceController::class, 'publicCheckInWithOtp'])->name('gym.public_checkin_otp');

Route::get('/member-details/{id}', [Modules\Gym\Http\Controllers\MemberController::class, 'show_member_details'])->name('show_member_details');

Route::get('/member-details/{id}', [Modules\Gym\Http\Controllers\MemberController::class, 'show_member_details'])->name('show_member_details')->middleware('signed');

Route::middleware('web', 'auth', 'language', 'AdminSidebarMenu')->prefix('gym')->group(function () {
    Route::get('dashboard', [Modules\Gym\Http\Controllers\DashBoardController::class, 'index']);

    Route::resource('members', Modules\Gym\Http\Controllers\MemberController::class);
    
    Route::resource('gym-categories', Modules\Gym\Http\Controllers\GymCategoryController::class);

    Route::get('member/{id}/profile', [Modules\Gym\Http\Controllers\MemberController::class, 'member_profile']);
    Route::get('member/{id}/id-card', [Modules\Gym\Http\Controllers\MemberController::class, 'id_card']);
    Route::get('members/report', [Modules\Gym\Http\Controllers\MemberController::class, 'memberReport'])->name('gym.members.report');

    Route::get('member/{id}/health', [Modules\Gym\Http\Controllers\MemberController::class, 'add_health']);
    Route::post('member/{id}/health-store/', [Modules\Gym\Http\Controllers\MemberController::class, 'store_health']);

    Route::get('attendance', [Modules\Gym\Http\Controllers\AttendanceController::class, 'index']);
    Route::get('get-in/{id}', [Modules\Gym\Http\Controllers\AttendanceController::class, 'get_in']);
    Route::get('get-out/{id}', [Modules\Gym\Http\Controllers\AttendanceController::class, 'get_out']);
    Route::post('add-edit-in-time', [Modules\Gym\Http\Controllers\AttendanceController::class, 'add_edit_in_time']);
    Route::post('add-edit-out-time', [Modules\Gym\Http\Controllers\AttendanceController::class, 'add_edit_out_time']);
    Route::post('delete-attendance', [Modules\Gym\Http\Controllers\AttendanceController::class, 'deleteAttendance'])->name('gym.delete_attendance');
    
    Route::resource('gym-packages', Modules\Gym\Http\Controllers\PackageController::class);
    Route::get('/gym-packages/{id}/destroy', [Modules\Gym\Http\Controllers\PackageController::class, 'destroy'])->name('delete_package');

    Route::get('add-subscription/{id}', [Modules\Gym\Http\Controllers\SubscriptionController::class, 'add_subscription']);
    Route::get('/get-end_date', [Modules\Gym\Http\Controllers\SubscriptionController::class, 'get_end_date'])->name('get_end_date');
    Route::resource('/subscriptions', Modules\Gym\Http\Controllers\SubscriptionController::class);
    
    // Subscription Renewal & Invoice
    Route::get('subscriptions/{id}/renew', [Modules\Gym\Http\Controllers\SubscriptionController::class, 'renewForm'])->name('gym.subscriptions.renew');
    Route::post('subscriptions/{id}/renew', [Modules\Gym\Http\Controllers\SubscriptionController::class, 'renew'])->name('gym.subscriptions.renew.store');
    Route::get('subscriptions/{id}/invoice', [Modules\Gym\Http\Controllers\SubscriptionController::class, 'printInvoice'])->name('gym.subscriptions.invoice');

    // OTP Verification for Check-in
    Route::post('attendance/send-otp', [Modules\Gym\Http\Controllers\AttendanceController::class, 'sendOtp'])->name('gym.attendance.send_otp');
    Route::post('attendance/verify-otp', [Modules\Gym\Http\Controllers\AttendanceController::class, 'verifyOtp'])->name('gym.attendance.verify_otp');

    // Deferred Revenue Management
    Route::get('deferred-revenue', [Modules\Gym\Http\Controllers\DeferredRevenueController::class, 'index'])->name('gym.deferred-revenue.index');
    Route::post('deferred-revenue/process', [Modules\Gym\Http\Controllers\DeferredRevenueController::class, 'process'])->name('gym.deferred-revenue.process');
    Route::post('deferred-revenue/{id}/process', [Modules\Gym\Http\Controllers\DeferredRevenueController::class, 'processSingle'])->name('gym.deferred-revenue.process-single');
    Route::get('deferred-revenue/schedule/{transaction_id}', [Modules\Gym\Http\Controllers\DeferredRevenueController::class, 'viewSchedule'])->name('gym.deferred-revenue.schedule');
    Route::post('deferred-revenue/generate-missing', [Modules\Gym\Http\Controllers\DeferredRevenueController::class, 'generateMissing'])->name('gym.deferred-revenue.generate-missing');
    Route::get('deferred-revenue/diagnostic', [Modules\Gym\Http\Controllers\DeferredRevenueController::class, 'diagnostic'])->name('gym.deferred-revenue.diagnostic');

    Route::resource('settings', Modules\Gym\Http\Controllers\SettingController::class);

    Route::resource('classes', Modules\Gym\Http\Controllers\ClassController::class);
    Route::get('/class/{id}/destroy', [Modules\Gym\Http\Controllers\ClassController::class, 'destroy'])->name('delete_class');

    // Courts management
    Route::resource('courts', Modules\Gym\Http\Controllers\CourtController::class);
    Route::delete('/courts/{id}', [Modules\Gym\Http\Controllers\CourtController::class, 'destroy'])->name('gym.courts.destroy');

    // Bookings management - specific routes first
    Route::get('bookings/todays', [Modules\Gym\Http\Controllers\BookingController::class, 'getTodaysBookings'])->name('gym.bookings.todays');
    Route::get('bookings/get-courts/{class_id}', [Modules\Gym\Http\Controllers\BookingController::class, 'getCourts'])->name('gym.bookings.courts');
    Route::get('bookings/get-subscriptions/{contact_id}', [Modules\Gym\Http\Controllers\BookingController::class, 'getMemberSubscriptions'])->name('gym.bookings.subscriptions');
    Route::post('bookings/check-availability', [Modules\Gym\Http\Controllers\BookingController::class, 'checkAvailability'])->name('gym.bookings.check_availability');
    Route::resource('bookings', Modules\Gym\Http\Controllers\BookingController::class);

    // Topup Hours management
    Route::get('topups/subscriptions/{contact_id}', [Modules\Gym\Http\Controllers\TopupController::class, 'getMemberSubscriptions'])->name('gym.topups.subscriptions');
    Route::resource('topups', Modules\Gym\Http\Controllers\TopupController::class);

    Route::resource('diets', Modules\Gym\Http\Controllers\MemberDietController::class);

    // AYO Integration routes
    Route::prefix('ayo')->group(function () {
        Route::get('/', [Modules\Gym\Http\Controllers\AyoBookingController::class, 'index'])->name('gym.ayo.index');
        Route::get('/settings', [Modules\Gym\Http\Controllers\AyoBookingController::class, 'settings'])->name('gym.ayo.settings');
        Route::post('/settings', [Modules\Gym\Http\Controllers\AyoBookingController::class, 'saveSettings'])->name('gym.ayo.settings.save');
        Route::post('/test-connection', [Modules\Gym\Http\Controllers\AyoBookingController::class, 'testConnection'])->name('gym.ayo.test_connection');

        // AYO Bookings API
        Route::get('/bookings', [Modules\Gym\Http\Controllers\AyoBookingController::class, 'getBookings'])->name('gym.ayo.bookings');
        Route::post('/bookings/sync', [Modules\Gym\Http\Controllers\AyoBookingController::class, 'syncBookings'])->name('gym.ayo.bookings.sync');

        // AYO Venue Fields API
        Route::get('/venue-fields', [Modules\Gym\Http\Controllers\AyoBookingController::class, 'getVenueFields'])->name('gym.ayo.venue_fields');

        // AYO Create Reservation
        Route::get('/reservation/create', [Modules\Gym\Http\Controllers\AyoBookingController::class, 'createReservation'])->name('gym.ayo.reservation.create');
        Route::post('/reservation', [Modules\Gym\Http\Controllers\AyoBookingController::class, 'storeReservation'])->name('gym.ayo.reservation.store');

        // AYO Cancel Reservation
        Route::post('/reservation/cancel', [Modules\Gym\Http\Controllers\AyoBookingController::class, 'cancelReservation'])->name('gym.ayo.reservation.cancel');

        // AYO API Logs
        Route::get('/logs', [Modules\Gym\Http\Controllers\AyoBookingController::class, 'getLogs'])->name('gym.ayo.logs');
        Route::get('/logs/{id}', [Modules\Gym\Http\Controllers\AyoBookingController::class, 'logDetail'])->name('gym.ayo.logs.detail');

        // AYO Court-to-Field Mapping
        Route::post('/court-mapping', [Modules\Gym\Http\Controllers\AyoBookingController::class, 'saveCourtMapping'])->name('gym.ayo.court_mapping.save');
    });

    Route::get('install', [\Modules\Gym\Http\Controllers\InstallController::class, 'index']);
    Route::post('install', [\Modules\Gym\Http\Controllers\InstallController::class, 'install']);
    Route::get('install/uninstall', [\Modules\Gym\Http\Controllers\InstallController::class, 'uninstall']);
    Route::get('install/update', [\Modules\Gym\Http\Controllers\InstallController::class, 'update']);
});

// AYO Webhook (public - no auth) for receiving booking notifications from AYO
Route::post('/gym/ayo/webhook/{venue_code}', [Modules\Gym\Http\Controllers\AyoBookingController::class, 'webhook'])
    ->name('gym.ayo.webhook')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
