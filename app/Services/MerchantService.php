<?php

namespace App\Services;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class MerchantService
{
    /**
     * Register a new user and associated merchant.
     * Hint: Use the password field to store the API key.
     * Hint: Be sure to set the correct user type according to the constants in the User model.
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return Merchant
     */
    public function register(array $data): Merchant
    {
        try {
            $user = new User();
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = $data['api_key'];
            $user->type = User::TYPE_MERCHANT;
            if ($user->save()) {
                $merchant = new Merchant();
                $merchant->user_id = $user->id;
                $merchant->domain = $data['domain'];
                $merchant->display_name = $data['name'];
                if ($merchant->save()) {
                    return $merchant;
                } else {
                    Log::error(["Error" => "Merchant Not Created"]);
                }
            } else {
                Log::error(["Error" => "User Not Created"]);
            }
        } catch (\Exception $e) {
            Log::error(["Error" => $e->getMessage(), "Line" => $e->getLine()]);
        }
        return "";
    }

    /**
     * Update the user
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return void
     */
    public function updateMerchant(User $user, array $data)
    {
        try {
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = $data['api_key'];
            $user->merchant()->update(["display_name" => $data['name'], "domain" => $data['domain']]);
            $user->save();
        } catch (\Exception $e) {
            Log::error(["Error" => $e->getMessage(), "Line" => $e->getLine()]);
        }
    }

    /**
     * Find a merchant by their email.
     * Hint: You'll need to look up the user first.
     *
     * @param string $email
     * @return Merchant|null
     */
    public function findMerchantByEmail(string $email): ?Merchant
    {
        try {
            $user = User::where('email', $email)->first();
            if($user) {
                $merchant = Merchant::where('user_id', $user['id'])->first();
                if ($merchant) {
                    return $merchant;
                }
                return null;
            }
            return null;
        } catch (\Exception $e) {
            Log::error(["Error" => $e->getMessage(), "Line" => $e->getLine()]);
        }
    }

    /**
     * Pay out all of an affiliate's orders.
     * Hint: You'll need to dispatch the job for each unpaid order.
     *
     * @param Affiliate $affiliate
     * @return void
     */
    public function payout(Affiliate $affiliate)
    {
        //TODO: Complete this method
        $orders = $affiliate->orders;
        foreach ($orders as $order) {
            // Skip paid orders
            if ($order->payout_status !== Order::STATUS_UNPAID) {
                continue;
            }

            // Dispatched PayoutOrderJob for unpaid orders
            dispatch(new PayoutOrderJob($order));
        }
    }
}
