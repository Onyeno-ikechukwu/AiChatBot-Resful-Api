<?php

namespace App\Http\Controllers\Bot;

use App\Http\Controllers\Controller;
use App\Http\Requests\FlutterwaveRequest;
use App\Service\FlutterwaveService;
use App\Http\Resources\FlutterwaveResource;

class FlutterwaveController extends Controller
{

    public function paymentMethod(FlutterwaveService $flutterwave, FlutterwaveRequest $flatterwaveRequest)
    {
        $paymentMethod = $flatterwaveRequest->paymentMethod($flatterwaveRequest, $flutterwave);
        return $paymentMethod;
    }

    public function createPayment(FlutterwaveService $flutterwave, FlutterwaveRequest $flatterwaveRequest){
        $payment = $flatterwaveRequest->createPayment($flutterwave, $flatterwaveRequest);
        return response()->json(new FlutterwaveResource($payment));
    }

   

}
