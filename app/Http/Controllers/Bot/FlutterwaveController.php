<?php

namespace App\Http\Controllers\Bot;

use App\Http\Controllers\Controller;
use App\Http\Requests\FlutterwaveRequest;
use App\Service\FlutterwaveService;
use App\Http\Resources\FlutterwaveResource;
use Illuminate\Http\JsonResponse;

/**
 * Handles Flutterwave payment operations.
 *
 * Manages the payment lifecycle: retrieving card payment methods (GET /bot/paymentmethod)
 * and creating orders (POST /bot/createpayment). Delegates business logic to
 * FlutterwaveService and validation to FlutterwaveRequest.
 *
 * @package App\Http\Controllers\Bot
 */
class FlutterwaveController extends Controller
{
    /**
     * Get available payment methods for a card.
     *
     * Validates card details (card_number, expiry_month, expiry_year, cvv) and
     * returns the payment method ID from Flutterwave for subsequent payment creation.
     *
     * @param  FlutterwaveService  $flutterwave
     * @param  FlutterwaveRequest  $flatterwaveRequest
     * @return string
     */
    public function paymentMethod(FlutterwaveService $flutterwave, FlutterwaveRequest $flatterwaveRequest)
    {
        $paymentMethod = $flatterwaveRequest->paymentMethod($flatterwaveRequest, $flutterwave);
        return $paymentMethod;
    }

    /**
     * Create a new Flutterwave payment order.
     *
     * Validates amount/package business rules, creates a customer on Flutterwave,
     * initiates the order, verifies the transaction, and persists the payment
     * record to the database.
     *
     * @param  FlutterwaveService  $flutterwave
     * @param  FlutterwaveRequest  $flatterwaveRequest
     * @return JsonResponse
     */
    public function createPayment(FlutterwaveService $flutterwave, FlutterwaveRequest $flatterwaveRequest){
        $payment = $flatterwaveRequest->createPayment($flutterwave, $flatterwaveRequest);
        return response()->json(new FlutterwaveResource($payment));
    }
}