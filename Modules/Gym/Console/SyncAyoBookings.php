<?php

namespace Modules\Gym\Console;

use Illuminate\Console\Command;
use App\Business;
use Modules\Gym\Services\AyoApiService;

class SyncAyoBookings extends Command
{
    protected $signature = 'gym:sync-ayo-bookings
                            {--business= : Specific business ID to sync}
                            {--days=7 : Number of days to look back}';

    protected $description = 'Sync bookings from AYO platform to local gym_bookings table';

    public function handle()
    {
        $specificBusiness = $this->option('business');
        $days = (int) $this->option('days');

        $this->info("Starting AYO bookings sync (looking back {$days} days)...");

        $query = Business::whereNotNull('gym_settings');
        if ($specificBusiness) {
            $query->where('id', $specificBusiness);
        }

        $businesses = $query->get();
        $totalSynced = 0;
        $totalCreated = 0;
        $totalUpdated = 0;
        $totalErrors = 0;

        foreach ($businesses as $business) {
            $settings = json_decode($business->gym_settings, true);
            $ayo = $settings['ayo'] ?? [];

            if (empty($ayo['api_token']) || empty($ayo['venue_code']) || empty($ayo['secret_key'])) {
                continue;
            }

            $this->info("Syncing business #{$business->id}: {$business->name}");

            $ayoService = new AyoApiService($business->id);

            $filters = [
                'start_date' => now()->subDays($days)->format('Y-m-d'),
                'end_date' => now()->addDays(1)->format('Y-m-d'),
            ];

            $result = $ayoService->syncBookingsToLocal($filters);

            if ($result['success']) {
                $synced = $result['synced'] ?? 0;
                $created = $result['created'] ?? 0;
                $updated = $result['updated'] ?? 0;
                $errors = count($result['errors'] ?? []);

                $this->info("  ✓ Synced: {$synced} (created: {$created}, updated: {$updated}, errors: {$errors})");

                $totalSynced += $synced;
                $totalCreated += $created;
                $totalUpdated += $updated;
                $totalErrors += $errors;

                if (!empty($result['errors'])) {
                    foreach ($result['errors'] as $err) {
                        $this->warn("    Error booking #{$err['booking_id']}: {$err['error']}");
                    }
                }
            } else {
                $this->error("  ✗ Failed: {$result['message']}");
                $totalErrors++;
            }
        }

        $this->info("=== Sync Complete ===");
        $this->info("Total synced: {$totalSynced} (created: {$totalCreated}, updated: {$totalUpdated})");
        if ($totalErrors > 0) {
            $this->warn("Total errors: {$totalErrors}");
        }

        return self::SUCCESS;
    }
}
