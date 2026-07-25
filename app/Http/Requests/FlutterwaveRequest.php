<?php

namespace App\Http\Requests;

use App\Models\Payment;
use GuzzleHttp\Psr7\Request;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FlutterwaveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('get')) {
            return [
                'card_number' => ['required', 'digits_between:13,19'],
                'expiry_month' => ['required', 'digits:2'],
                'expiry_year' => ['required', 'digits:2'],
                'cvv' => ['required', 'digits_between:3,4'],
            ];
        }
        if ($this->isMethod('post')) {
            return [
                'amount' => ['required', 'numeric'],
                'payment_package' => ['required', 'string'],
                'payment_method_id' => ['required', 'string'],
            ];
        }
    }
    public function paymentMethod($flutterwaveRequest, $flutterwave){
        $validated = $flutterwaveRequest->validated();
        
        $paymentMethods = $flutterwave->getPaymentMethods($validated['card_number'], $validated['expiry_month'], $validated['expiry_year'], $validated['cvv']);

        return $paymentMethods;
    }

    public function createPayment($flutterwave, $flutterwaveRequest){
        $user = Auth::user();
        $validated = $flutterwaveRequest->validated();
        $txRef = 'PAY-' . Str::uuid();

        if($validated['amount'] < 1000){
            return response()->json(['message' => 'Amount must be above 1000 or more for any package']);
        }
        if($validated['amount'] >= 1000 && $validated['amount'] <= 4999 && $validated['payment_package'] != 'small'){
            return response()->json(['message' => 'Amount between 1000–4999 must use small package']);
        }

        if($validated['amount'] >= 5000 && $validated['amount'] <= 9999 && $validated['payment_package'] != 'medium'){
            return response()->json(['message' => 'Amount between 5000–9999 must use medium package']);
        }

        if($validated['amount'] >= 10000 && $validated['payment_package'] != 'large'){
            return response()->json(['message' => 'Amount 10000+ must use large package']);
        }

        $customer = $flutterwave->createCustomer($user->email, $user->name);
        $customerId = $customer['data']['id'];

        $response = $flutterwave->createPayment(
            amount: $validated['amount'],   
            email: $user->email,
            name: $user->name,   
            txRef: $txRef,
            package: $validated['payment_package'],
            customerId: $customerId,
            paymentMethod: $validated['payment_method_id'],
        );
        if (
        isset($response['status']) &&
        $response['status'] === 'success'
        ) {
    
            $payment = $flutterwave->verify($response['data']['id']);

            $data = Payment::create([
                'user_id'        => auth::id(), 
                'tx_ref'         => $payment['data']['reference'],
                'transaction_id' => $payment['data']['id'],
                'amount'         => $payment['data']['amount'],
                'currency'       => $payment['data']['currency'],
                'status'         => $payment['status'],
                'plan'           => $validated['payment_package']
            ]);
            return $data;
        }
    }
}
