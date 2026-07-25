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
    public function getToken(): string
    {
        $response = Http::asForm()->post(
            'https://idp.flutterwave.com/realms/flutterwave/protocol/openid-connect/token',
            [
                'client_id' => config('services.flutterwave.client_id'),
                'client_secret' => config('services.flutterwave.client_secret'),
                'grant_type' => 'client_credentials',
            ]
        );

        $response->throw();

        return $response->json('access_token');
    }

    private function encryptAES(string $data, string $nonce): string
    {
        $key = base64_decode(env('FLW_ENCRYPTION_KEY'));

        $tag = '';

        $encrypted = openssl_encrypt(
            $data,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );

        return base64_encode($encrypted . $tag);
    }

    private function generateNonce(): string
    {
        return strtoupper(substr(str_shuffle(
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
        ), 0, 12));
    }
    

    public function createCustomer(string $email, string $name){
        $token = $this->getToken();
        
         $response = Http::withToken($token)
            ->post(
                'https://developersandbox-api.flutterwave.com/customers',
                [
                    "email"=> $email,
                    "name" => [
                        "first" => $name,
                        "last" => "ViewGenerator",
                    ],
                ]
            );
        return $response->json();
    }

    public function getPaymentMethods($cardNumber, $expiryMonth, $expiryYear, $cvv)
    {
        $token = $this->getToken();
        $nonce = $this->generateNonce();

        $encryptedCardNumber = $this->encryptAES(
            $cardNumber,
            $nonce
        );

        $encryptedExpiryMonth = $this->encryptAES(
            $expiryMonth,
            $nonce
        );

        $encryptedExpiryYear = $this->encryptAES(
            $expiryYear,
            $nonce
        );

        $encryptedCvv = $this->encryptAES(
            $cvv,
            $nonce
        );


        $response = Http::withToken($token)
        ->post(
            'https://developersandbox-api.flutterwave.com/payment-methods',
            [
                'type' => 'card',

                'card' => [
                    'nonce' => $nonce,

                    'encrypted_card_number' => $encryptedCardNumber,

                    'encrypted_expiry_month' => $encryptedExpiryMonth,

                    'encrypted_expiry_year' => $encryptedExpiryYear,

                    'encrypted_cvv' => $encryptedCvv,
                ]
            ]
        );

        return $response['data']['id'];
    }

    public function createPayment(
        float $amount,
        string $email,
        string $name,
        string $txRef,
        string $package,
        string $customerId,
        string $paymentMethod
    ) {
        $token = $this->getToken();
        
        $response = Http::withToken($token)
            ->post(
                'https://developersandbox-api.flutterwave.com/orders',
                [
                    'amount' => $amount,
                    'currency' => 'NGN',
                    'description' => 'Payment for ViewGenerator, For the package of ' . $package,
                    'package' => $package,
                    'reference' => $txRef,
                    'customer_id' => $customerId,
                    'payment_method_id' => $paymentMethod,
                    'customer' => [
                        'email' => $email,
                        'name' => [
                            'first' => $name,
                        ],
                    ],
                    'redirect_url' => route('payment.callback'),
                ]
            );
            
        return $response->json();

    }

    public function verify($transactionId)
    {
        $token = $this->getToken();

        $response = Http::withToken($token)
            ->acceptJson()
            ->get(
                "https://developersandbox-api.flutterwave.com/orders/{$transactionId}"
            );

        $response->throw();

        return $response->json();
    }
}
