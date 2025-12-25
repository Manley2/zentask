<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionIntent;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $plans = [
            'free' => [
                'name' => 'Free',
                'price' => 0,
                'description' => 'Plan dasar untuk mulai membangun ritme kerja.',
                'features' => ['Tanpa voice recorder', 'Kolaborasi terbatas'],
            ],
            'pro' => [
                'name' => 'Pro',
                'price' => 45000,
                'description' => 'Semua fitur produktivitas utama untuk fokus maksimal.',
                'features' => ['Voice recorder aktif', 'Hingga 10 kolaborator'],
            ],
            'plus' => [
                'name' => 'Plus',
                'price' => 65000,
                'description' => 'Fitur premium untuk tim yang berkembang lebih cepat.',
                'features' => ['Semua fitur Pro', 'Hingga 30 kolaborator'],
            ],
        ];

        return view('subscription.plans', compact('user', 'plans'));
    }

    public function adminActivate(Request $request, SubscriptionService $service)
    {
        $user = $request->user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $validator = Validator::make($request->all(), [
            'plan' => ['required', 'in:free,pro,plus'],
        ]);

        if ($validator->fails()) {
            return back()->with('error', 'Invalid plan selection.');
        }

        $service->activatePlan($user, $request->plan);

        return back()->with('success', 'Plan activated (admin access).');
    }

    public function updatePlan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan' => ['required', 'in:free,pro,plus'],
        ]);

        if ($validator->fails()) {
            return back()->with('error', 'Invalid plan selection.');
        }

        $user = $request->user();
        $selected = $request->plan;

        if ($user->subscription_plan === $selected) {
            return back()->with('success', 'You are already on this plan.');
        }

        if (in_array($selected, ['pro', 'plus'], true)) {
            return back()->with('error', 'Upgrade Pro/Plus harus melalui pembayaran.');
        }

        $service = new SubscriptionService();
        $service->activatePlan($user, $selected);

        return back()->with('success', 'Plan updated successfully!');
    }

    public function checkout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan' => ['required', 'in:pro,plus'],
        ]);

        if ($validator->fails()) {
            return back()->with('error', 'Invalid plan selection.');
        }

        $user = $request->user();
        $selected = $request->plan;
        $price = $selected === 'plus' ? 65000 : 45000;

        if ($user->isAdmin()) {
            $service = new SubscriptionService();
            $service->activatePlan($user, $selected);
            return redirect()->route('subscription.plans')
                ->with('success', 'Plan activated (admin access).');
        }

        if ($user->subscription_plan === $selected) {
            return back()->with('success', 'You are already on this plan.');
        }

        $intent = SubscriptionIntent::create([
            'user_id' => $user->id,
            'plan' => $selected,
            'price' => $price,
            'status' => 'pending',
            'order_id' => 'ZT-' . $user->id . '-' . Str::uuid(),
            'payment_token' => null,
            'redirect_url' => null,
        ]);

        if (!config('services.midtrans.server_key')) {
            return back()->with('error', 'Midtrans belum dikonfigurasi.');
        }

        $this->initMidtrans();
        $transaction = $this->buildTransactionParams($intent, $user);
        $snapToken = \Midtrans\Snap::getSnapToken($transaction);

        $intent->update([
            'payment_token' => $snapToken,
        ]);

        return redirect()->route('subscription.payment', ['order' => $intent->order_id]);
    }

    public function payment(Request $request, string $order)
    {
        $intent = SubscriptionIntent::where('order_id', $order)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return view('subscription.payment', [
            'intent' => $intent,
        ]);
    }

    public function midtransCallback(Request $request)
    {
        $orderId = $request->input('order_id');
        $status = $request->input('transaction_status');
        $statusCode = (string) $request->input('status_code');
        $grossAmount = (string) $request->input('gross_amount');

        if (!$this->isValidMidtransSignature($orderId, $statusCode, $grossAmount, (string) $request->input('signature_key'))) {
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $intent = SubscriptionIntent::where('order_id', $orderId)->first();
        if (!$intent) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $intent->payload = $request->all();
        $intent->midtrans_status = $status;

        if (in_array($status, ['capture', 'settlement'], true)) {
            $intent->status = 'paid';
            $intent->paid_at = now();

            $user = $intent->user;
            if ($user) {
                $user->subscription_plan = $intent->plan;
                $user->plan_started_at = now();
                $user->is_subscribed = true;
                $user->subscribed_until = now()->addDays(30);
                $user->save();
            }
        } elseif ($status === 'pending') {
            $intent->status = 'pending';
        } else {
            $intent->status = 'failed';
        }

        $intent->save();

        return response()->json(['message' => 'OK']);
    }

    public function checkFeature(Request $request)
    {
        return response()->json([
            'plan' => $request->user()->subscription_plan,
        ]);
    }

    public function billing()
    {
        abort(404);
    }

    public function cancel()
    {
        abort(404);
    }

    private function initMidtrans(): void
    {
        \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
        \Midtrans\Config::$isProduction = (bool) config('services.midtrans.is_production');
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;
    }

    private function buildTransactionParams(SubscriptionIntent $intent, $user): array
    {
        return [
            'transaction_details' => [
                'order_id' => $intent->order_id,
                'gross_amount' => (int) $intent->price,
            ],
            'item_details' => [
                [
                    'id' => $intent->plan,
                    'price' => (int) $intent->price,
                    'quantity' => 1,
                    'name' => strtoupper($intent->plan) . ' Plan',
                ],
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
        ];
    }

    private function isValidMidtransSignature(string $orderId, string $statusCode, string $grossAmount, string $signature): bool
    {
        $serverKey = config('services.midtrans.server_key');
        if (!$serverKey || !$signature) {
            return false;
        }

        $payload = $orderId . $statusCode . $grossAmount . $serverKey;
        $expected = hash('sha512', $payload);

        return hash_equals($expected, $signature);
    }
}
