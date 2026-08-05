<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $user = collect($request->attributes->get('user'));

        $payments = Cache::remember('user_payments_' . $user->get('id'), 60, function () use ($user) {
            return Payment::query()->where('user_id', $user->get('id'))->get()->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'user_id' => $payment->user_id,
                    'order_id' => $payment->order_id,
                    'payment_number' => $payment->payment_number,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'payment_method' => $payment->getAttribute('payment_method')->label(),
                    'status' => $payment->getAttribute('status')->label(),
                    'created_at' => $payment->created_at?->toIso8601String(),
                    'updated_at' => $payment->updated_at?->toIso8601String(),
                ];
            })->toArray();
        });

        return response()->json([
            'message' => 'Payments retrieved successfully',
            'data_count' => count($payments),
            'data' => $payments,
        ]);
    }

    public function store(Request $request)
    {
        $user = collect($request->attributes->get('user'));
        $request->merge(['user_id' => $user->get('id')]);

        $validatedData = $request->validate([
            'user_id' => 'required|integer',
            'order_id' => 'required|integer',
            'amount' => 'required|numeric',
            'currency' => 'required|string|max:3',
            'payment_method' => 'required|in:credit_card,debit_card,paypal',
        ]);

        $payment = Payment::create($validatedData);

        Cache::forget('user_payments_' . $payment->user_id);

        return response()->json([
            'message' => 'Payment created successfully',
            'data' => $payment,
        ], 201);
    }

    public function show(Request $request, int $id)
    {
        $user = collect($request->attributes->get('user'));
        $payment = Payment::where('user_id', $user->get('id'))->find($id);

        if (!$payment) {
            return response()->json([
                'message' => 'Payment not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Payment retrieved successfully',
            'data' => $payment,
        ]);
    }

    public function getByNumber(Request $request, string $paymentNumber)
    {
        $user = collect($request->attributes->get('user'));
        $payment = Payment::where('user_id', $user->get('id'))->where('payment_number', $paymentNumber)->first();

        if (!$payment) {
            return response()->json([
                'message' => 'Payment not found: ' . $paymentNumber,
            ], 404);
        }

        return response()->json([
            'message' => 'Payment retrieved successfully',
            'data' => $payment,
        ]);
    }

    public function showOrder(Request $request, int $id)
    {
        $user = collect($request->attributes->get('user'));
        $payment = Payment::where('user_id', $user->get('id'))->find($id);

        if (!$payment) {
            return response()->json([
                'message' => 'Payment non retrouvé',
            ], 404);
        }

        // Fetch order details from the Order Management Microservice
        $orderServiceBaseUrl = config('services.order_service.base_url');
        $token = $request->bearerToken();

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get("{$orderServiceBaseUrl}/api/orders/{$payment->order_id}");

        if ($response->failed()) {
            return response()->json([
                'message' => 'Impossible de récupérer les détails de la commande',
                'error' => $response->body(),
            ], $response->status());
        }

        return response()->json([
            'message' => 'Order details retrieved successfully',
            'payment' => [
                ...$payment->toArray(),
                'order_details' => $response->json()
            ],
        ]);
    }
}
