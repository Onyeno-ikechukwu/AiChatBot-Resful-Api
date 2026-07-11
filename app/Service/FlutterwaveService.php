<?php

namespace App\Service;

use Illuminate\Support\Facades\Http;

class FlutterwaveService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function getToken()
    {

        $response = Http::post(
            'https://developersandbox-api.flutterwave.com/oauth/token',
            [

                'client_id' => config('services.flutterwave.client_id'),

                'client_secret' => config('services.flutterwave.client_secret'),

                'grant_type' => 'client_credentials'

            ]
        );
        return $response;
    }

    public function createPayment(
        float $amount,
        string $email,
        string $name,
        string $txRef
    ) {

        $token = $this->getToken()['access_token'];

        $response = Http::withToken($token)

            ->post(
                'https://developersandbox-api.flutterwave.com/payments',

                [
                    'tx_ref' => $txRef,

                    "amount"=>$amount,

                    "currency"=>"NGN",

                    "reference"=>uniqid(),

                    "customer"=>[

                        "name"=>$name,

                        "email"=>$email

                    ],
                    'customizations' => [
                        'title' => config('app.name'),
                        'description' => 'Payment',
                        'logo' => asset('logo.png'), // optional
                    ],

                    "redirect_url"=>"https://crafty-automated-mourner.ngrok-free.dev/api/payment/callback"

                ]

            );

        return $response->json();

    }

    public function verify($transactionId)
    {

        $token = $this->getToken()['access_token'];

        $response = Http::withToken($token)

            ->get(

        "https://developersandbox-api.flutterwave.com/transactions/$transactionId"

        );

        return $response->json();

    }
}
