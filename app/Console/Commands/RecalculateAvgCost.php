<?php

namespace App\Console\Commands;

use App\Business;
use App\Product;
use App\VariationLocationDetails;
use DB;
use Illuminate\Console\Command;

class RecalculateAvgCost extends Command
{
    protected $signature = 'pos:recalculateAvgCost {--business_id= : Limit to a specific business ID}';

    protected $description = 'Recalculate AVCO avg_cost in variation_location_details from historical purchase data.';

    public function handle()
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M');

        $businessId = $this->option('business_id');

        $query = Business::where('accounting_method', 'avco');
        if ($businessId) {
            $query->where('id', $businessId);
        }
        $businesses = $query->get();

        if ($businesses->isEmpty()) {
            $this->info('No businesses with AVCO accounting method found.');

            return;
        }

        foreach ($businesses as $business) {
            $this->info("Processing business: {$business->name} (ID: {$business->id})");

            // Get all variation+location combinations that exist for this business
            $vlds = VariationLocationDetails::whereIn(
                'location_id',
                DB::table('business_locations')->where('business_id', $business->id)->pluck('id')
            )->get();

            $bar = $this->output->createProgressBar($vlds->count());
            $bar->start();

            foreach ($vlds as $vld) {
                $this->recalculateForVariationLocation($vld, $business->id);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        }

        $this->info('Done.');
    }

    private function recalculateForVariationLocation(VariationLocationDetails $vld, int $businessId): void
    {
        // Verify the product has stock tracking enabled
        $product = Product::find($vld->product_id);
        if (empty($product) || $product->enable_stock != 1) {
            return;
        }

        // Fetch all stock-adding and stock-reducing events in chronological order
        $purchaseEvents = DB::table('transactions as t')
            ->join('purchase_lines as pl', 'pl.transaction_id', '=', 't.id')
            ->where('t.business_id', $businessId)
            ->where('t.location_id', $vld->location_id)
            ->where('pl.variation_id', $vld->variation_id)
            ->whereIn('t.type', ['purchase', 'opening_stock', 'purchase_transfer', 'production_purchase'])
            ->where('t.status', 'received')
            ->orderBy('t.transaction_date', 'asc')
            ->orderBy('t.id', 'asc')
            ->select('pl.quantity', 'pl.purchase_price', DB::raw("'in' as direction"), 't.transaction_date')
            ->get();

        $sellEvents = DB::table('transactions as t')
            ->join('transaction_sell_lines as sl', 'sl.transaction_id', '=', 't.id')
            ->where('t.business_id', $businessId)
            ->where('t.location_id', $vld->location_id)
            ->where('sl.variation_id', $vld->variation_id)
            ->whereIn('t.type', ['sell', 'sell_transfer'])
            ->where('t.status', 'final')
            ->orderBy('t.transaction_date', 'asc')
            ->orderBy('t.id', 'asc')
            ->select('sl.quantity', DB::raw('0 as purchase_price'), DB::raw("'out' as direction"), 't.transaction_date')
            ->get();

        // Merge and sort all events by date
        $events = $purchaseEvents->concat($sellEvents)
            ->sortBy('transaction_date')
            ->values();

        $runningQty = 0.0;
        $runningAvg = 0.0;

        foreach ($events as $event) {
            $qty = (float) $event->quantity;

            if ($event->direction === 'in') {
                $price = (float) $event->purchase_price;
                if (($runningQty + $qty) > 0) {
                    $runningAvg = ($runningQty * $runningAvg + $qty * $price) / ($runningQty + $qty);
                }
                $runningQty += $qty;
            } else {
                $runningQty = max(0.0, $runningQty - $qty);
                // avg_cost does not change on sale
            }
        }

        $vld->avg_cost = max(0, $runningAvg);
        $vld->save();
    }
}
