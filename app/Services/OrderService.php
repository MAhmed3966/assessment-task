<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class OrderService
{
    protected AffiliateService $affiliateService;

    public function __construct(
        AffiliateService $affiliateService
    )
    {
        $this->affiliateService = $affiliateService;
    }

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
        try {
            $user = User::create(['name' => $data['customer_name'],
                'email' => $data['customer_email'],
                'type' => 'affiliate',
            ]);
            $merchant = Merchant::updateOrCreate(["domain" => $data['merchant_domain']],
                ['user_id' => $user->id,
                    "display_name" => $data['customer_name']]);
            $this->affiliateService->register($merchant, $data['customer_email'], $data['customer_name'], 0.1);
            $affiliate = Affiliate::updateOrCreate([
                'merchant_id' => $user->merchant->id
            ], [
                'user_id' => $user->id, 'commission_rate' => $merchant->default_commission_rate,
                'discount_code' => $data['discount_code']]);
            Order::create(
                [
                    'merchant_id' => $user->merchant->id,
                    'affiliate_id' => $affiliate->id,
                    'merchant_id' => $merchant->id,
                    'subtotal' => $data['subtotal_price'],
                    'commission_owed' => $data['subtotal_price'] * $merchant->default_commission_rate,
                ]);
        } catch (\Exception $e) {
            Log::error(["Error"=> $e->getMessage(), "Line" => $e->getLine()]);
        }
    }
}
