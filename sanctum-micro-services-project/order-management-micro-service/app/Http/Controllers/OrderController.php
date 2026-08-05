<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OrderController extends Controller
{
    public function index(Request $request)
    {

        $user = collect($request->attributes->get('user'));

        $orders = Cache::remember('user_orders_' . $user->get('id'), 60, function () use ($user) {
            return Order::query()->where('user_id', $user->get('id'))->get()->map(function ($order) {
                return [
                    'id' => $order->id,
                    'user_id' => $order->user_id,
                    'order_number' => $order->order_number,
                    'grand_total' => $order->calculateGrandTotal(),
                    "currency" => $order->currency,
                    'shipping_address' => $order->shipping_address,
                    'billing_address' => $order->billing_address,
                    'payment_method' => $order->getAttribute('payment_method')->label(),
                    'created_at' => $order->created_at?->toIso8601String(),
                    'updated_at' => $order->updated_at?->toIso8601String(),
                ];
            })->toArray();
        });

        return response()->json([
            'message' => 'Orders retrieved successfully',
            "data_count" => count($orders),
            'data' => $orders,
        ]);
    }

    public function store(Request $request)
    {

        $user = collect($request->attributes->get('user'));
        $request->merge(['user_id' => $user->get('id')]);

        $validatedData = $request->validate([
            'user_id' => 'required|integer',
            'total_amount' => 'required|numeric',
            'tax_amount' => 'required|numeric',
            'shipping_amount' => 'required|numeric',
            'discount_amount' => 'nullable|numeric',
            'currency' => 'required|string|max:3',
            'shipping_address' => 'required|string|max:255',
            'billing_address' => 'required|string|max:255',
            'payment_method' => 'required|in:credit_card,debit_card,paypal',
        ]);
        $validatedData['grand_total'] = 0;

        $order = Order::create($validatedData);
        $order->calculateGrandTotal();
        $order->save();

        Cache::forget('user_orders_' . $order->user_id);

        return response()->json([
            'message' => 'Order created successfully',
            'data' => $order,
        ], 201);
    }

    public function show(int $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Order retrieved successfully',
            'data' => $order,
        ]);
    }
    public function getByNumber(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found: ' . $orderNumber,
            ], 404);
        }

        return response()->json([
            'message' => 'Order retrieved successfully',
            'data' => $order,
        ]);
    }
}
