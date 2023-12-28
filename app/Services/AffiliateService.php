<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AffiliateService
{
    public ApiService $apiService;

    public function __construct(
        ApiService $apiService
    )
    {
        $this->apiService = $apiService;
    }

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param Merchant $merchant
     * @param string $email
     * @param string $name
     * @param float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        try {
//            dump( $email, $name, $commissionRate);
            $user =  User::where("email", $email)->first();
            if (Affiliate::where('merchant_id', $merchant->id)->count() === 0
                && !$user
            ) {
                $affiliate = new Affiliate();
                $affiliate->user_id = $merchant->user_id;
                $affiliate->merchant_id = $merchant->id;
                $affiliate->commission_rate = $commissionRate;
                $affiliate->discount_code = $this->apiService->createDiscountCode($merchant)['code'];
                if ($affiliate->save()) {
                    User::updateOrCreate(
                        ['email' => $email],
                        ['name' => $name, 'type' => 'merchant']
                    );
                    Mail::to($email)->send(new AffiliateCreated($affiliate));
                    return $affiliate;
                } else {
                    throw new AffiliateCreateException("Affiliate not created");
                }
            } else {
                throw new AffiliateCreateException("Affiliate not created");
            }

        } catch (\Exception $e){
            Log::error(["Error"=> $e->getMessage(), "Line" => $e->getLine()]);
            throw new AffiliateCreateException("Affiliate not created", 0, $e);
        }
    }

}
