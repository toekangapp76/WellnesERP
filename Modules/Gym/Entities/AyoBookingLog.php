<?php

namespace Modules\Gym\Entities;

use Illuminate\Database\Eloquent\Model;

class AyoBookingLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_success' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    /**
     * Scopes
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('is_success', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('is_success', false);
    }

    /**
     * Log an API call
     */
    public static function logApiCall(
        int $businessId,
        string $action,
        ?string $ayoBookingId,
        $requestPayload,
        $responsePayload,
        ?int $httpStatusCode,
        bool $isSuccess,
        ?string $errorMessage = null
    ): self {
        return self::create([
            'business_id' => $businessId,
            'action' => $action,
            'ayo_booking_id' => $ayoBookingId,
            'request_payload' => is_array($requestPayload) ? json_encode($requestPayload) : $requestPayload,
            'response_payload' => is_array($responsePayload) ? json_encode($responsePayload) : $responsePayload,
            'http_status_code' => $httpStatusCode,
            'is_success' => $isSuccess,
            'error_message' => $errorMessage,
        ]);
    }
}
