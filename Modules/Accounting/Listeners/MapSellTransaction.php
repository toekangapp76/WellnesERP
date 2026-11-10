<?php

namespace Modules\Accounting\Listeners;

use App\Events\SellCreatedOrModified;
use App\BusinessLocation;
use App\VariationLocationDetails;
use App\Variation;

class MapSellTransaction
{
    public function __construct()
    {
        //
    }

    public function handle(SellCreatedOrModified $event)
    {
        $accountingUtil = new \Modules\Accounting\Utils\AccountingUtil();

        if (isset($event->isDeleted) && $event->isDeleted) {
            $accountingUtil->deleteMap($event->transaction->id, null);

            return;
        }

        $business_location = BusinessLocation::find($event->transaction->location_id);
        $accounting_default_map = json_decode($business_location->accounting_default_map, true);

        $deposit_to         = $accounting_default_map['sale']['deposit_to']        ?? null;
        $payment_account     = $accounting_default_map['sale']['payment_account']   ?? null;
        $default_cogs_account = $accounting_default_map['sale']['cogs_account']     ?? null;
        $default_inventory_account = $accounting_default_map['sale']['inventory_account'] ?? null;
        $default_discount_account = $accounting_default_map['sale']['discount_account'] ?? null;

        if (is_null($deposit_to) || is_null($payment_account)) {
            return;
        }

        $transaction = $event->transaction;
        $transaction->load(['sell_lines.product.category', 'sell_lines.product.sub_category']);

        $revenue_by_account = [];
        $discount_by_account = [];
        $cogs_by_account = [];
        $inventory_by_account = [];
        $computed_subtotal = 0.0;
        $line_revenue_shares = [];

        foreach ($transaction->sell_lines as $line) {
            $qty = max(0, (float) $line->quantity - (float) $line->quantity_returned);
            if ($qty <= 0) {
                continue;
            }

            $product = $line->product;

            $revenue_account_id = (! empty($product) ? $product->resolvedRevenueAccountId() : null) ?: $deposit_to;
            $cogs_account_id = (! empty($product) ? $product->resolvedCogsAccountId() : null) ?: $default_cogs_account;
            $inventory_account_id = (! empty($product) ? $product->resolvedInventoryAccountId() : null) ?: $default_inventory_account;
            $discount_account_id = (! empty($product) ? $product->resolvedSalesDiscountAccountId() : null) ?: $default_discount_account;

            $line_revenue = $qty * (float) $line->unit_price;

            $revenue_by_account[$revenue_account_id] = ($revenue_by_account[$revenue_account_id] ?? 0) + $line_revenue;
            $computed_subtotal += $line_revenue;

            $line_revenue_shares[] = [
                'revenue' => $line_revenue,
                'discount_account_id' => $discount_account_id,
            ];

            if (! is_null($cogs_account_id) && ! is_null($inventory_account_id)) {
                $line_cogs = $qty * $this->getUnitCost($line, $transaction->location_id);

                $cogs_by_account[$cogs_account_id] = ($cogs_by_account[$cogs_account_id] ?? 0) + $line_cogs;
                $inventory_by_account[$inventory_account_id] = ($inventory_by_account[$inventory_account_id] ?? 0) + $line_cogs;
            }
        }

        $final_total = (float) $transaction->final_total;

        if ($computed_subtotal > 0) {
            $discount_value_total = 0.0;
            if ($transaction->discount_type == 'fixed') {
                $discount_value_total = (float) $transaction->discount_amount;
            } elseif ($transaction->discount_type == 'percentage') {
                $discount_value_total = $computed_subtotal * (float) $transaction->discount_amount / 100;
            }

            if ($discount_value_total > 0) {
                foreach ($line_revenue_shares as $share) {
                    $line_discount = $discount_value_total * ($share['revenue'] / $computed_subtotal);
                    if (! is_null($share['discount_account_id']) && $line_discount > 0) {
                        $discount_by_account[$share['discount_account_id']] = ($discount_by_account[$share['discount_account_id']] ?? 0) + $line_discount;
                    }
                }
            }

            // Invoice-level tax/shipping/rounding aren't tracked per account, so pro-rate
            // revenue+discount to the actual final_total to keep the entry balanced
            // (payment_account debit == final_total == sum(revenue credits) - sum(discount debits)).
            $net_expected = $computed_subtotal - array_sum($discount_by_account);

            if ($net_expected > 0 && abs($net_expected - $final_total) > 0.0001) {
                $ratio = $final_total / $net_expected;
                foreach ($revenue_by_account as $account_id => $amount) {
                    $revenue_by_account[$account_id] = $amount * $ratio;
                }
                foreach ($discount_by_account as $account_id => $amount) {
                    $discount_by_account[$account_id] = $amount * $ratio;
                }
            } elseif ($net_expected <= 0 && $final_total > 0) {
                // Discount wipes out (or exceeds) the subtotal - fall back to a single
                // revenue line against the location default so the sale is still balanced.
                $revenue_by_account = [$deposit_to => $final_total];
                $discount_by_account = [];
            }
        } elseif ($final_total > 0) {
            // No usable line data (e.g. all lines free/zero-priced) - fall back to a single
            // revenue line against the location default so the sale is still balanced.
            $revenue_by_account = [$deposit_to => $final_total];
            $discount_by_account = [];
        }

        $accountingUtil->saveSellDetailedMap(
            $transaction->id,
            request()->session()->get('user.id'),
            $transaction->business_id,
            null,
            $payment_account,
            $final_total,
            $revenue_by_account,
            $discount_by_account,
            $cogs_by_account,
            $inventory_by_account
        );
    }

    private function getUnitCost($line, $location_id)
    {
        // Use avg_cost from location stock if available, else fall back to purchase price
        $vld = VariationLocationDetails::where('variation_id', $line->variation_id)
                    ->where('location_id', $location_id)
                    ->first();

        if ($vld && $vld->avg_cost > 0) {
            return (float) $vld->avg_cost;
        }

        $variation = Variation::find($line->variation_id);

        return $variation ? (float) $variation->dpp_inc_tax : 0;
    }
}
