<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlutterwaveRequest;
use App\Models\Payment;
use App\Service\FlutterwaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FlutterwaveController extends Controller
{
    /**
     * Initialize a payment checkout with Flutterwave.
     *
     * Creates a payment session with Flutterwave and returns a payment link for the user to complete checkout.
     * Accepts amount, email, and name in the request body.
     */
    public function checkout(FlutterwaveService $flutterwave, FlutterwaveRequest $request)
    {
        $validated = $request->validated();
        $user = $validated->user();
        $txRef = 'PAY-' . Str::uuid();

        $response = $flutterwave->createPayment(
            amount: $validated['amount'],   
            email: $validated['email'],   
            name: $validated['name'],   
            txRef: $txRef,
        );

        if (
            isset($response['status']) &&
            $response['status'] === 'success'
        ) {
            return response()->json([
                'message' => 'Payment initialized successfully.',
                'payment_link' => $response['data']['link'],
                'tx_ref' => $txRef,
            ]);
        }

        return response()->json([
            'message' => 'Unable to initialize payment.',
            'error' => $response
        ], 400);

    }

    /**
     * Handle Flutterwave payment callback/verification.
     *
     * Verifies a payment transaction ID with Flutterwave, determines the subscription plan
     * based on the amount paid, and stores the payment record. Plans: 5000 (Monthly/30 days),
     * 25000 (6 Months), 45000 (Yearly).
     */
    public function callback(Request $request, FlutterwaveService $flutterwave)
    {
        // Verify payment
        $payment = $flutterwave->verify($request->transaction_id);

        $amount = $payment['data']['amount'];

        switch ($amount) {

            case 5000:
                $expiresAt = now()->addMonth();
                $plan = 'Monthly';
                break;

            case 25000:
                $expiresAt = now()->addMonths(6);
                $plan = '6 Months';
                break;

            case 45000:
                $expiresAt = now()->addYear();
                $plan = 'Yearly';
                break;

            default:
                return response()->json([
                    'message' => 'Invalid subscription amount.'
                ], 422);
        }

        // Check if Flutterwave confirms the payment
        if (
            isset($payment['data']['status']) &&
            $payment['data']['status'] === 'successful'
        ) {

            Payment::create([
                'user_id'        => auth::id(), 
                'tx_ref'         => $request->tx_ref,
                'transaction_id' => $request->transaction_id,
                'amount'         => $payment['data']['amount'],
                'currency'       => $payment['data']['currency'],
                'status'         => $payment['data']['status'],
                'plan'           => $plan,
                'starts_at'      => now(),
                'expires_at'     => $expiresAt,
            ]);

            return response()->json([
                'message' => 'Payment Successful'
            ]);
        }

        return response()->json([
            'message' => 'Payment Failed'
        ], 400);
    }

}
