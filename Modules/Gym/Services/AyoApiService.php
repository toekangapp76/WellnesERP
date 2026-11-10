<?php

namespace Modules\Gym\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Gym\Entities\AyoBookingLog;
use Modules\Gym\Entities\GymBooking;
use Modules\Gym\Entities\GymCourt;

class AyoApiService
{
    /**
     * AYO API Configuration
     */
    protected $baseUrl;
    protected $token;
    protected $venueCode;
    protected $secretKey;
    protected $businessId;

    public function __construct(?int $businessId = null)
    {
        $this->businessId = $businessId ?? session('user.business_id');
        $this->loadConfig();
    }

    /**
     * Load AYO configuration from business settings
     */
    protected function loadConfig()
    {
        if (!$this->businessId) {
            return;
        }

        $business = \App\Business::find($this->businessId);
        if (!$business) {
            return;
        }

        $settings = json_decode($business->gym_settings, true);
        $ayo = $settings['ayo'] ?? [];

        $this->baseUrl = rtrim($ayo['base_url'] ?? 'https://sandbox.ayodev.xyz/api/v2/third-party', '/');
        $this->token = $ayo['api_token'] ?? '';
        $this->venueCode = $ayo['venue_code'] ?? '';
        $this->secretKey = $ayo['secret_key'] ?? '';
    }

    /**
     * Check if AYO integration is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->token) && !empty($this->venueCode) && !empty($this->secretKey);
    }

    /**
     * Get current configuration status
     */
    public function getConfigStatus(): array
    {
        return [
            'is_configured' => $this->isConfigured(),
            'base_url' => $this->baseUrl ?? '',
            'venue_code' => $this->venueCode ?? '',
            'has_token' => !empty($this->token),
            'has_secret_key' => !empty($this->secretKey),
        ];
    }

    // =========================================================================
    // SIGNATURE GENERATION
    // =========================================================================

    /**
     * Generate HMAC-SHA512 signature from request data
     * 
     * Steps:
     * 1. Remove 'signature' key from data
     * 2. Sort data by key ascending
     * 3. Convert to URL-encoded query string
     * 4. Generate HMAC-SHA512 hash using the secret key
     */
    public function generateSignature(array $data): string
    {
        // Remove signature from data if present
        unset($data['signature']);

        // Sort by key ascending
        ksort($data);

        // Convert to query string
        $queryString = http_build_query($data);

        // Generate HMAC-SHA512
        return hash_hmac('sha512', $queryString, $this->secretKey);
    }

    /**
     * Verify a signature from AYO response
     */
    public function verifySignature(array $data, string $signature): bool
    {
        $computed = $this->generateSignature($data);
        return hash_equals($computed, $signature);
    }

    // =========================================================================
    // API #1: GET BOOKINGS (List Bookings)
    // =========================================================================

    /**
     * Get bookings from AYO platform
     * 
     * @param array $filters Optional filters: booking_id, field_name, date, start_time, end_time, booking_source, start_date, end_date, status
     * @return array
     */
    public function getBookings(array $filters = []): array
    {
        $payload = [
            'token' => $this->token,
        ];

        // Add optional filters
        $optionalFields = ['booking_id', 'field_name', 'date', 'start_time', 'end_time', 'booking_source', 'start_date', 'end_date', 'status'];
        foreach ($optionalFields as $field) {
            if (!empty($filters[$field])) {
                $payload[$field] = $filters[$field];
            }
        }

        // Generate signature
        $payload['signature'] = $this->generateSignature($payload);

        $url = $this->baseUrl . '/list-bookings/' . $this->venueCode;

        return $this->makeRequest('GET', $url, $payload, 'list_bookings');
    }

    // =========================================================================
    // API #2: NOTIFY BOOKING (Webhook Receiver)
    // =========================================================================

    /**
     * Handle incoming booking notification from AYO
     * This is the webhook/callback endpoint that AYO calls
     * 
     * @param array $bookingData The booking data sent by AYO
     * @return array
     */
    public function handleBookingNotification(array $bookingData): array
    {
        try {
            // Log the notification
            AyoBookingLog::logApiCall(
                $this->businessId,
                'notify_booking',
                $bookingData[0]['booking_id'] ?? null,
                $bookingData,
                ['received' => true],
                200,
                true
            );

            // Process each booking in the notification
            $processed = [];
            foreach ($bookingData as $booking) {
                $processed[] = $this->processNotifiedBooking($booking);
            }

            return [
                'error' => false,
                'status_code' => 200,
                'message' => 'Success',
                'processed' => $processed,
            ];
        } catch (\Exception $e) {
            Log::error('AYO Notification Error: ' . $e->getMessage());

            AyoBookingLog::logApiCall(
                $this->businessId,
                'notify_booking',
                $bookingData[0]['booking_id'] ?? null,
                $bookingData,
                null,
                500,
                false,
                $e->getMessage()
            );

            return [
                'error' => true,
                'status_code' => 500,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process individual booking from notification
     * Creates new local booking if not found, updates status if exists
     */
    protected function processNotifiedBooking(array $booking): array
    {
        $statusMap = [
            'SUCCESS' => GymBooking::STATUS_CONFIRMED,
            'FINISHED' => GymBooking::STATUS_COMPLETED,
            'PENDING' => GymBooking::STATUS_PENDING,
            'CANCELLED' => GymBooking::STATUS_CANCELLED,
        ];

        $existingBooking = GymBooking::where('ayo_booking_id', $booking['booking_id'])->first();

        if ($existingBooking) {
            $oldStatus = $existingBooking->ayo_status;
            $newStatus = $booking['status'] ?? null;

            $existingBooking->update([
                'ayo_status' => $newStatus,
                'booking_status' => $statusMap[$newStatus] ?? $existingBooking->booking_status,
                'ayo_total_price' => $booking['total_price'] ?? $existingBooking->ayo_total_price,
                'ayo_synced_at' => now(),
            ]);

            // Create accounting entry if status changed to SUCCESS (paid)
            if ($oldStatus !== 'SUCCESS' && $newStatus === 'SUCCESS') {
                $this->createAyoPaymentAccounting($existingBooking);
            }

            return ['booking_id' => $booking['booking_id'], 'action' => 'updated'];
        }

        // === CREATE NEW LOCAL BOOKING FROM AYO DATA ===
        try {
            $newBooking = $this->createLocalBookingFromAyo($booking, $statusMap);

            if ($newBooking && ($booking['status'] ?? '') === 'SUCCESS') {
                $this->createAyoPaymentAccounting($newBooking);
            }

            return [
                'booking_id' => $booking['booking_id'],
                'action' => $newBooking ? 'created' : 'skipped',
                'local_id' => $newBooking->id ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create local booking from AYO notification: ' . $e->getMessage());
            return ['booking_id' => $booking['booking_id'], 'action' => 'error', 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a local GymBooking from AYO booking data
     */
    protected function createLocalBookingFromAyo(array $ayoBooking, array $statusMap): ?GymBooking
    {
        // Resolve court from AYO field info
        $court = $this->resolveCourtFromAyo($ayoBooking);
        $classId = $court ? $court->gym_class_id : null;

        $bookingStart = \Carbon\Carbon::parse($ayoBooking['date'] . ' ' . $ayoBooking['start_time']);
        $bookingEnd = \Carbon\Carbon::parse($ayoBooking['date'] . ' ' . $ayoBooking['end_time']);

        $bookingData = [
            'business_id' => $this->businessId,
            'gym_class_id' => $classId,
            'court_id' => $court->id ?? null,
            'booking_start' => $bookingStart,
            'booking_end' => $bookingEnd,
            'duration_minutes' => $bookingStart->diffInMinutes($bookingEnd),
            'booking_status' => $statusMap[$ayoBooking['status']] ?? GymBooking::STATUS_PENDING,
            'walkin_name' => $ayoBooking['booker_name'] ?? 'AYO Customer',
            'walkin_phone' => $ayoBooking['booker_phone'] ?? '-',
            'booking_note' => 'AYO Booking: ' . ($ayoBooking['field_name'] ?? '') . ' | ' . ($ayoBooking['booker_email'] ?? ''),
            'ayo_booking_id' => $ayoBooking['booking_id'],
            'ayo_order_detail_id' => $ayoBooking['order_detail_id'] ?? null,
            'ayo_booking_source' => $ayoBooking['booking_source'] ?? 'ayo',
            'ayo_status' => $ayoBooking['status'] ?? null,
            'ayo_total_price' => $ayoBooking['total_price'] ?? null,
            'ayo_synced_at' => now(),
            'created_by' => auth()->id() ?? 1,
        ];

        return GymBooking::create($bookingData);
    }

    /**
     * Resolve local court from AYO booking data
     * Priority: ayo_field_id mapping > field_name matching
     */
    protected function resolveCourtFromAyo(array $ayoBooking): ?GymCourt
    {
        // Try exact field_id mapping first
        if (!empty($ayoBooking['field_id'])) {
            $court = GymCourt::findByAyoFieldId($this->businessId, (int) $ayoBooking['field_id']);
            if ($court) {
                return $court;
            }
        }

        // Fallback: try matching by field_name
        if (!empty($ayoBooking['field_name'])) {
            $court = GymCourt::findByAyoFieldName($this->businessId, $ayoBooking['field_name']);
            if ($court) {
                return $court;
            }
        }

        return null;
    }

    // =========================================================================
    // API #3: CREATE RESERVATION
    // =========================================================================

    /**
     * Create a reservation on AYO platform
     * 
     * @param array $data Reservation data: field_id, date, start_time, end_time, total_price, user (optional), booking_id (optional)
     * @return array
     */
    public function createReservation(array $data): array
    {
        $payload = [
            'token' => $this->token,
            'field_id' => (int) $data['field_id'],
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'total_price' => (int) $data['total_price'],
        ];

        // Optional booking_id
        if (!empty($data['booking_id'])) {
            $payload['booking_id'] = $data['booking_id'];
        }

        // Optional user info
        if (!empty($data['user'])) {
            $userInfo = (array)$data['user']; // Ensure it's an array
            ksort($userInfo); // Sort keys alphabetically for signature consistency
            $payload['user'] = $userInfo;
        } else {
            $payload['user'] = new \stdClass(); // empty object for internal reservation
        }

        // Generate signature (include 'user' object if present)
        $signatureData = $payload;
        $payload['signature'] = $this->generateSignature($signatureData);

        $url = $this->baseUrl . '/create-reservation';

        return $this->makeRequest('POST', $url, $payload, 'create_reservation');
    }

    // =========================================================================
    // API #4: CANCEL RESERVATION
    // =========================================================================

    /**
     * Cancel a reservation on AYO platform
     * 
     * @param int $orderDetailId The order_detail_id of the reservation
     * @return array
     */
    public function cancelReservation(int $orderDetailId): array
    {
        $payload = [
            'token' => $this->token,
            'order_detail_id' => $orderDetailId,
        ];

        // Generate signature
        $payload['signature'] = $this->generateSignature($payload);

        $url = $this->baseUrl . '/cancel-reservation';

        return $this->makeRequest('POST', $url, $payload, 'cancel_reservation');
    }

    // =========================================================================
    // API #5: GET VENUE FIELDS
    // =========================================================================

    /**
     * Get venue fields (courts/fields) from AYO platform
     * 
     * @return array
     */
    public function getVenueFields(): array
    {
        $payload = [
            'token' => $this->token,
        ];

        // Generate signature
        $payload['signature'] = $this->generateSignature($payload);

        $url = $this->baseUrl . '/list-fields';

        return $this->makeRequest('GET', $url, $payload, 'list_fields');
    }

    // =========================================================================
    // HTTP REQUEST HELPER
    // =========================================================================

    /**
     * Make HTTP request to AYO API
     */
    protected function makeRequest(string $method, string $url, array $payload, string $action): array
    {
        $ayoBookingId = $payload['booking_id'] ?? $payload['order_detail_id'] ?? null;

        try {
            $http = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(30);

            if (strtoupper($method) === 'GET') {
                // GET requests with body (as per AYO API spec)
                $response = $http->withBody(json_encode($payload), 'application/json')->get($url);
            } else {
                $response = $http->post($url, $payload);
            }

            $responseData = $response->json() ?? [];
            $statusCode = $response->status();
            $isSuccess = $response->successful() && ($responseData['error'] ?? true) === false;

            // Log the API call
            AyoBookingLog::logApiCall(
                $this->businessId,
                $action,
                $ayoBookingId,
                $payload,
                $responseData,
                $statusCode,
                $isSuccess,
                $isSuccess ? null : ($responseData['message'] ?? 'Unknown error')
            );

            return [
                'success' => $isSuccess,
                'status_code' => $statusCode,
                'data' => $responseData['data'] ?? [],
                'message' => $responseData['message'] ?? '',
                'signature' => $responseData['signature'] ?? null,
                'raw' => $responseData,
            ];
        } catch (\Exception $e) {
            Log::error("AYO API Error [{$action}]: " . $e->getMessage());

            AyoBookingLog::logApiCall(
                $this->businessId,
                $action,
                $ayoBookingId,
                $payload,
                null,
                null,
                false,
                $e->getMessage()
            );

            return [
                'success' => false,
                'status_code' => 0,
                'data' => [],
                'message' => $e->getMessage(),
                'signature' => null,
                'raw' => [],
            ];
        }
    }

    // =========================================================================
    // SYNC HELPERS
    // =========================================================================

    /**
     * Sync AYO bookings to local gym_bookings table
     * Enhanced: includes court mapping, price tracking, and accounting
     */
    public function syncBookingsToLocal(array $filters = []): array
    {
        $result = $this->getBookings($filters);

        if (!$result['success']) {
            return [
                'success' => false,
                'message' => $result['message'],
                'synced' => 0,
            ];
        }

        $statusMap = [
            'SUCCESS' => GymBooking::STATUS_CONFIRMED,
            'FINISHED' => GymBooking::STATUS_COMPLETED,
            'PENDING' => GymBooking::STATUS_PENDING,
            'CANCELLED' => GymBooking::STATUS_CANCELLED,
        ];

        $synced = 0;
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($result['data'] as $ayoBooking) {
            try {
                $existing = GymBooking::where('ayo_booking_id', $ayoBooking['booking_id'])->first();

                if ($existing) {
                    $oldStatus = $existing->ayo_status;
                    $newStatus = $ayoBooking['status'] ?? null;

                    $existing->update([
                        'ayo_order_detail_id' => $ayoBooking['order_detail_id'] ?? $existing->ayo_order_detail_id,
                        'ayo_booking_source' => $ayoBooking['booking_source'] ?? $existing->ayo_booking_source,
                        'ayo_status' => $newStatus,
                        'ayo_total_price' => $ayoBooking['total_price'] ?? $existing->ayo_total_price,
                        'booking_status' => $statusMap[$newStatus] ?? $existing->booking_status,
                        'ayo_synced_at' => now(),
                    ]);

                    // Create accounting if newly paid
                    if ($oldStatus !== 'SUCCESS' && $newStatus === 'SUCCESS') {
                        $this->createAyoPaymentAccounting($existing);
                    }

                    $updated++;
                } else {
                    // Create new local booking from AYO data
                    $newBooking = $this->createLocalBookingFromAyo($ayoBooking, $statusMap);

                    if ($newBooking && ($ayoBooking['status'] ?? '') === 'SUCCESS') {
                        $this->createAyoPaymentAccounting($newBooking);
                    }

                    $created++;
                }

                $synced++;
            } catch (\Exception $e) {
                $errors[] = [
                    'booking_id' => $ayoBooking['booking_id'],
                    'error' => $e->getMessage(),
                ];
                Log::error('AYO Sync Error: ' . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'message' => "Synced {$synced} bookings (created: {$created}, updated: {$updated})",
            'synced' => $synced,
            'created' => $created,
            'updated' => $updated,
            'total' => count($result['data']),
            'errors' => $errors,
        ];
    }

    // =========================================================================
    // ACCOUNTING INTEGRATION
    // =========================================================================

    /**
     * Create GL journal entry for AYO payment
     * Dr. Bank/Kas (asset)    → amount
     * Cr. Pendapatan Sewa (income) → amount
     *
     * Uses accounting accounts from gym_settings or gym_packages
     */
    public function createAyoPaymentAccounting(GymBooking $booking): void
    {
        try {
            $amount = (float) ($booking->ayo_total_price ?? 0);
            if ($amount <= 0) {
                return;
            }

            // Check if accounting module exists
            if (!class_exists('\Modules\Accounting\Entities\AccountingAccountsTransaction')) {
                Log::info('Accounting module not available, skipping AYO payment GL entry for booking #' . $booking->id);
                return;
            }

            // Resolve accounting accounts
            $accounts = $this->resolveAyoAccountingAccounts($booking);
            if (!$accounts['asset_account_id'] || !$accounts['income_account_id']) {
                Log::warning('AYO accounting accounts not configured, skipping GL entry for booking #' . $booking->id);
                return;
            }

            $operationDate = $booking->booking_start->format('Y-m-d');
            $note = 'AYO Booking Payment: #' . ($booking->ayo_booking_id ?? $booking->id)
                . ' | ' . ($booking->walkin_name ?? 'Customer')
                . ' | ' . $booking->booking_start->format('d/m/Y H:i');

            // Dr. Bank/Kas (increase asset)
            \Modules\Accounting\Entities\AccountingAccountsTransaction::create([
                'accounting_account_id' => $accounts['asset_account_id'],
                'transaction_id' => null,
                'transaction_payment_id' => null,
                'amount' => $amount,
                'type' => 'debit',
                'sub_type' => 'ayo_booking_payment',
                'map_type' => 'ayo_payment',
                'created_by' => auth()->id() ?? 1,
                'operation_date' => $operationDate,
                'note' => $note,
            ]);

            // Cr. Pendapatan Sewa (increase income)
            \Modules\Accounting\Entities\AccountingAccountsTransaction::create([
                'accounting_account_id' => $accounts['income_account_id'],
                'transaction_id' => null,
                'transaction_payment_id' => null,
                'amount' => $amount,
                'type' => 'credit',
                'sub_type' => 'ayo_booking_payment',
                'map_type' => 'ayo_revenue',
                'created_by' => auth()->id() ?? 1,
                'operation_date' => $operationDate,
                'note' => $note,
            ]);

            Log::info('AYO payment GL entry created for booking #' . $booking->id . ', amount: ' . $amount);

        } catch (\Exception $e) {
            Log::error('Failed to create AYO payment GL entry for booking #' . $booking->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Resolve accounting account IDs for AYO payment entries
     * Priority: AYO-specific settings > court's class package accounts > defaults
     */
    protected function resolveAyoAccountingAccounts(GymBooking $booking): array
    {
        $assetAccountId = null;
        $incomeAccountId = null;

        // 1. Try AYO-specific accounts from gym_settings
        $business = \App\Business::find($this->businessId);
        if ($business) {
            $settings = json_decode($business->gym_settings, true);
            $ayo = $settings['ayo'] ?? [];
            $assetAccountId = $ayo['asset_account_id'] ?? null;
            $incomeAccountId = $ayo['income_account_id'] ?? null;
        }

        // 2. Fallback: use gym_package deposit/revenue accounts if court has a class
        if ((!$assetAccountId || !$incomeAccountId) && $booking->court_id) {
            $court = GymCourt::find($booking->court_id);
            if ($court && $court->gym_class_id) {
                $package = \Modules\Gym\Entities\GymPackage::where('business_id', $this->businessId)
                    ->where('enable_deferred_revenue', true)
                    ->first();
                if ($package) {
                    $assetAccountId = $assetAccountId ?? $package->deposit_account_id;
                    $incomeAccountId = $incomeAccountId ?? $package->revenue_account_id;
                }
            }
        }

        return [
            'asset_account_id' => $assetAccountId,
            'income_account_id' => $incomeAccountId,
        ];
    }
}
