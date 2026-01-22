<?php

namespace Modules\Gateways\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Modules\Gateways\Entities\PaymentRequest;
use Modules\Gateways\Traits\Processor;

class ZenopayController extends Controller
{
    use Processor;

    private PaymentRequest $payment;

    public function __construct(PaymentRequest $payment)
    {
        $this->payment = $payment;
    }

    private function config(): ?object
    {
        $config = $this->paymentConfig('zenopay', PAYMENT_CONFIG);
        if (is_null($config)) {
            return null;
        }

        $values = null;
        if ($config->mode === 'live') {
            $values = $config->live_values;
        } elseif ($config->mode === 'test') {
            $values = $config->test_values;
        }

        if (!is_array($values)) {
            return null;
        }

        return (object)$values;
    }

    public function pay(Request $request): View|Application|Factory|JsonResponse|\Illuminate\Contracts\Foundation\Application
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json($this->responseFormatter(GATEWAYS_DEFAULT_400, null, $this->errorProcessor($validator)), 400);
        }

        $data = $this->payment::query()->where(['id' => $request->payment_id, 'is_paid' => 0])->first();
        if (!isset($data)) {
            return response()->json($this->responseFormatter(GATEWAYS_DEFAULT_204), 200);
        }

        $cfg = $this->config();
        if (!$cfg || empty($cfg->account_id)) {
            return response()->json($this->responseFormatter(GATEWAYS_DEFAULT_400, null, [['error_code' => 'config', 'message' => 'Zenopay is not configured']]), 400);
        }

        $payer = json_decode($data->payer_information);

        $payload = [
            'order_id' => (string)$data->id,
            'buyer_email' => $payer->email ?? '',
            'buyer_name' => $payer->name ?? '',
            'buyer_phone' => $payer->phone ?? '',
            'amount' => (float)$data->payment_amount,
            'webhook_url' => route('zenopay.webhook'),
        ];

        $baseUrl = !empty($cfg->base_url) ? rtrim($cfg->base_url, '/') : 'https://api.zenopay.com';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-api-key' => $cfg->account_id,
        ])->post($baseUrl . '/mobile_money_tanzania', $payload);

        if (!$response->successful()) {
            return response()->json($this->responseFormatter(GATEWAYS_DEFAULT_400, null, [['error_code' => 'request', 'message' => 'Zenopay request failed']]), 400);
        }

        $res = $response->json();

        $data->update([
            'payment_method' => 'zenopay',
            'transaction_id' => $res['order_id'] ?? null,
        ]);

        return view('Gateways::payment.zenopay', ['payment_id' => (string)$data->id]);
    }

    public function status(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json($this->responseFormatter(GATEWAYS_DEFAULT_400, null, $this->errorProcessor($validator)), 400);
        }

        $payment = $this->payment::query()->where(['id' => $request->payment_id])->first();
        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        if ((int)$payment->is_paid === 1) {
            return response()->json(['message' => 'Payment completed', 'redirect' => route('payment-success')], 200);
        }

        $cfg = $this->config();
        if (!$cfg || empty($cfg->account_id) || empty($payment->transaction_id)) {
            return response()->json(['message' => 'Waiting for confirmation...'], 200);
        }

        $baseUrl = !empty($cfg->base_url) ? rtrim($cfg->base_url, '/') : 'https://api.zenopay.com';
        $res = Http::withHeaders([
            'x-api-key' => $cfg->account_id,
        ])->get($baseUrl . '/order-status', [
            'order_id' => $payment->transaction_id,
        ]);

        if (!$res->successful()) {
            return response()->json(['message' => 'Waiting for confirmation...'], 200);
        }

        $json = $res->json();
        $status = strtoupper((string)($json['data'][0]['payment_status'] ?? ''));
        $reference = $json['data'][0]['reference'] ?? null;

        if (in_array($status, ['COMPLETED', 'SUCCESS'], true)) {
            $payment->update([
                'is_paid' => 1,
                'payment_method' => 'zenopay',
                'transaction_id' => $reference ?? $payment->transaction_id,
            ]);

            $payment = $this->payment::query()->where(['id' => $request->payment_id])->first();
            if (isset($payment) && function_exists($payment->hook)) {
                call_user_func($payment->hook, $payment);
            }

            return response()->json(['message' => 'Payment completed', 'redirect' => route('payment-success')], 200);
        }

        return response()->json(['message' => 'Waiting for confirmation...'], 200);
    }

    public function webhook(Request $request): JsonResponse
    {
        $cfg = $this->config();
        if (!$cfg || empty($cfg->account_id)) {
            return response()->json(['message' => 'Invalid config'], 403);
        }

        $apiKey = $request->header('X-API-KEY') ?? $request->header('x-api-key');
        if ($apiKey !== $cfg->account_id) {
            return response()->json(['message' => 'Invalid API key'], 403);
        }

        $payload = $request->all();
        $orderId = $payload['order_id'] ?? null;
        $paymentStatus = strtoupper((string)($payload['payment_status'] ?? ''));

        if (!$orderId) {
            return response()->json(['message' => 'Missing order_id'], 422);
        }

        $payment = $this->payment::query()->where(['id' => $orderId])->first();
        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        if (in_array($paymentStatus, ['COMPLETED', 'SUCCESS'], true)) {
            $payment->update([
                'payment_method' => 'zenopay',
                'is_paid' => 1,
                'transaction_id' => $payload['reference'] ?? $payment->transaction_id,
            ]);
            $payment = $this->payment::query()->where(['id' => $orderId])->first();
            if (isset($payment) && function_exists($payment->hook)) {
                call_user_func($payment->hook, $payment);
            }
            return response()->json(['message' => 'OK'], 200);
        }

        return response()->json(['message' => 'Ignored'], 200);
    }
}
