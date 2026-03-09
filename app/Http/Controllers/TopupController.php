<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Topup;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TopupController extends Controller
{
    protected function getAccessibleDevices()
    {
        $user = auth()->user();
        return $user->isSuperAdmin() ? Device::all() : $user->devices;
    }

    public function index(Request $request)
    {
        $devices = $this->getAccessibleDevices();
        $deviceId = $request->get('device_id', $devices->first()?->id);
        
        $device = null;
        if ($deviceId !== 'all') {
            $device = $devices->firstWhere('id', $deviceId);
            if (!$device) {
                return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke device ini.');
            }
        }

        $query = Topup::with(['device', 'user'])->latest();
        if ($deviceId !== 'all') {
            $query->where('device_id', $deviceId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $query->whereIn('device_id', $devices->pluck('id'));
        }

        $topups = $query->paginate(15);

        return view('topups.index', compact('devices', 'device', 'deviceId', 'topups'));
    }

    /**
     * Create manual top-up (Superadmin only)
     */
    public function manualStore(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) abort(403);

        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'amount' => 'required|numeric|min:1',
        ]);

        $device = Device::findOrFail($request->device_id);
        $user = auth()->user();
        $orderId = 'MANUAL-' . $device->id . '-' . time() . '-' . rand(100, 999);

        // Langsung credit balance
        $device->increment('balance', $request->amount);

        Topup::create([
            'device_id' => $device->id,
            'user_id' => $user->id,
            'order_id' => $orderId,
            'amount' => $request->amount,
            'payment_type' => 'manual',
            'status' => 'settlement',
            'paid_at' => now(),
        ]);

        return back()->with('success', 'Manual Top Up berhasil ditambahkan ke device: ' . $device->name);
    }

    /**
     * Create top-up transaction
     */
    public function store(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'amount' => 'required|numeric|min:10000|max:100000000',
        ]);

        $devices = $this->getAccessibleDevices();
        $device = $devices->firstWhere('id', $request->device_id);

        if (!$device) {
            return back()->with('error', 'Anda tidak memiliki akses ke device ini.');
        }

        $user = auth()->user();
        $orderId = 'TOPUP-' . $device->id . '-' . time() . '-' . rand(100, 999);

        // Create Midtrans transaction
        $midtrans = new MidtransService();
        $params = $midtrans->buildParams(
            $orderId,
            $request->amount,
            $device->name,
            $user->name,
            $user->email
        );

        $result = $midtrans->createSnapUrl($params);

        if (!$result['success']) {
            return back()->with('error', 'Gagal membuat transaksi: ' . $result['error']);
        }

        // Save topup record
        $topup = Topup::create([
            'device_id' => $device->id,
            'user_id' => $user->id,
            'order_id' => $orderId,
            'amount' => $request->amount,
            'status' => 'pending',
            'snap_token' => $result['snap_token'],
            'redirect_url' => $result['redirect_url'],
        ]);

        return view('topups.pay', [
            'topup' => $topup,
            'device' => $device,
            'snapToken' => $result['snap_token'],
            'clientKey' => MidtransService::getClientKey(),
            'isProduction' => MidtransService::isProduction(),
        ]);
    }

    /**
     * Midtrans notification handler (webhook)
     */
    public function notification(Request $request)
    {
        Log::info('Midtrans Webhook Received', $request->all());

        try {
            $midtrans = new MidtransService();
            $notification = $midtrans->handleNotification();

            $orderId = $notification->order_id;
            $transactionStatus = $notification->transaction_status;
            $paymentType = $notification->payment_type;
            $fraudStatus = $notification->fraud_status ?? null;
            $transactionId = $notification->transaction_id ?? null;

            $topup = Topup::where('order_id', $orderId)->first();

            if (!$topup) {
                Log::warning('Midtrans notification: Order not found', ['order_id' => $orderId]);
                return response()->json(['status' => 'order not found'], 404);
            }

            $topup->update([
                'payment_type' => $paymentType,
                'transaction_id' => $transactionId,
                'midtrans_response' => json_decode($notification->getResponse()->getBody(), true),
            ]);

            if ($transactionStatus === 'capture' || $transactionStatus === 'settlement') {
                if ($fraudStatus === 'accept' || $fraudStatus === null) {
                    if ($topup->status !== 'settlement' && $topup->status !== 'capture') {
                        // Credit balance to device
                        $topup->device->increment('balance', $topup->amount);
                        $topup->update([
                            'status' => $transactionStatus,
                            'paid_at' => now(),
                        ]);
                    }
                }
            } elseif ($transactionStatus === 'pending') {
                $topup->update(['status' => 'pending']);
            } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'])) {
                $topup->update(['status' => $transactionStatus]);
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Midtrans notification error: ' . $e->getMessage());
            
            // Allow Midtrans dashboard test notifications (which use fake transaction IDs that result in 404) to pass
            if (str_contains($e->getMessage(), '404') || str_contains($e->getMessage(), 'not found')) {
                return response()->json(['status' => 'ok', 'message' => 'Ignoring test exception']);
            }
            
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Payment finished callback (from Snap redirect)
     */
    public function finish(Request $request)
    {
        $orderId = $request->get('order_id');
        $topup = $orderId ? Topup::where('order_id', $orderId)->first() : null;

        return view('topups.finish', ['topup' => $topup]);
    }
}
