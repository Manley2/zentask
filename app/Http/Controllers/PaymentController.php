<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Midtrans\Snap;

class PaymentController extends Controller
{
    public function notification(Request $request)
    {
        // Ambil payload JSON dari Midtrans
        $payload = $request->all();

        // Signature verification (wajib)
        $serverKey = config('midtrans.server_key');
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $signatureKey = $payload['signature_key'] ?? '';

        $localSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if (!$orderId || !$statusCode || !$grossAmount || !$signatureKey) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        if (!hash_equals($localSignature, $signatureKey)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        // Mapping status Midtrans
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;

        // Untuk sekarang: log dulu (AMAN, tanpa DB)
        \Log::info('MIDTRANS_NOTIFICATION', [
            'order_id' => $orderId,
            'transaction_status' => $transactionStatus,
            'fraud_status' => $fraudStatus,
            'payload' => $payload,
        ]);

        // Balas 200 agar Midtrans anggap sukses terkirim
        return response()->json(['message' => 'OK']);
    }
    public function snapToken()
    {
        // Parameter dummy (sandbox)
        $params = [
            'transaction_details' => [
                'order_id' => 'ORDER-' . time(),
                'gross_amount' => 10000, // Rp 10.000
            ],
            'customer_details' => [
                'first_name' => 'Sandbox',
                'last_name' => 'User',
                'email' => 'sandbox@example.com',
            ],
        ];

        $snapToken = Snap::getSnapToken($params);

        return response()->json([
            'snap_token' => $snapToken
        ]);
    }
}
