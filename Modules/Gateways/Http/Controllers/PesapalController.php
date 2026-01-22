<?php

namespace Modules\Gateways\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Modules\Gateways\Entities\PaymentRequest;
use Modules\Gateways\Traits\Processor;

class PesapalController extends Controller
{
    use Processor;

    private PaymentRequest $payment;

    public function __construct(PaymentRequest $payment)
    {
        $this->payment = $payment;
    }

    private function config(): ?object
    {
        $config = $this->paymentConfig('pesapal', PAYMENT_CONFIG);
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

    private function baseUrl(string $mode): string
    {
        return $mode === 'live'
            ? 'https://pay.pesapal.com/v3/api'
            : 'https://cybqa.pesapal.com/pesapalv3/api';
    }

    private function requestToken(string $mode, string $consumerKey, string $consumerSecret): ?string
    {
        $url = $this->baseUrl($mode) . '/Auth/RequestToken';

        $res = Http::acceptJson()->asJson()->post($url, [
            'consumer_key' => $consumerKey,
            'consumer_secret' => $consumerSecret,
        ]);

        if (!$res->successful()) {
            return null;
        }

        $json = $res->json();
        return $json['token'] ?? null;
    }

    public function pay(Request $request): RedirectResponse|JsonResponse
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
        if (!$cfg || empty($cfg->consumer_key) || empty($cfg->consumer_secret) || empty($cfg->ipn_id)) {
            return $this->paymentResponse($data, 'fail');
        }

        $token = $this->requestToken($cfg->mode ?? 'test', $cfg->consumer_key, $cfg->consumer_secret);
        if (!$token) {
            return $this->paymentResponse($data, 'fail');
        }

        $payer = json_decode($data->payer_information);

        $payload = [
            'id' => (string)$data->id,
            'currency' => $data->currency_code,
            'amount' => (float)$data->payment_amount,
            'description' => 'Payment',
            'callback_url' => route('pesapal.callback'),
            'notification_id' => $cfg->ipn_id,
            'branch' => 'Zendo',
            'billing_address' => [
                'email_address' => $payer->email ?? '',
                'phone_number' => $payer->phone ?? '',
                'first_name' => $payer->name ?? '',
                'last_name' => $payer->name ?? '',
                'country_code' => $cfg->country_code ?? 'TZ',
            ],
        ];

        $url = $this->baseUrl($cfg->mode ?? 'test') . '/Transactions/SubmitOrderRequest';
        $res = Http::acceptJson()
            ->asJson()
            ->withToken($token)
            ->post($url, $payload);

        if (!$res->successful()) {
            return $this->paymentResponse($data, 'fail');
        }

        $json = $res->json();
        $orderTrackingId = $json['order_tracking_id'] ?? null;
        $redirectUrl = $json['redirect_url'] ?? null;

        if (!$orderTrackingId || !$redirectUrl) {
            return $this->paymentResponse($data, 'fail');
        }

        $data->update([
            'payment_method' => 'pesapal',
            'transaction_id' => $orderTrackingId,
        ]);

        return redirect()->away($redirectUrl);
    }

    public function callback(Request $request): RedirectResponse
    {
        $orderTrackingId = $request->get('OrderTrackingId');

        if (!$orderTrackingId) {
            return redirect()->route('payment-fail');
        }

        $payment = $this->payment::query()->where('transaction_id', $orderTrackingId)->first();
        if (!$payment) {
            return redirect()->route('payment-fail');
        }

        $flag = $this->verifyAndFinalize($orderTrackingId);
        $payment = $this->payment::query()->where('transaction_id', $orderTrackingId)->first();
        return $this->paymentResponse($payment, $flag);
    }

    public function ipn(Request $request): JsonResponse
    {
        $orderTrackingId = $request->get('OrderTrackingId') ?? $request->get('orderTrackingId') ?? $request->get('order_tracking_id');

        if (!$orderTrackingId) {
            return response()->json(['status' => 500], 200);
        }

        $flag = $this->verifyAndFinalize($orderTrackingId);

        return response()->json([
            'orderNotificationType' => 'IPNCHANGE',
            'orderTrackingId' => $orderTrackingId,
            'status' => $flag === 'success' ? 200 : 500,
        ], 200);
    }

    private function verifyAndFinalize(string $orderTrackingId): string
    {
        $cfg = $this->config();
        if (!$cfg || empty($cfg->consumer_key) || empty($cfg->consumer_secret)) {
            return 'fail';
        }

        $token = $this->requestToken($cfg->mode ?? 'test', $cfg->consumer_key, $cfg->consumer_secret);
        if (!$token) {
            return 'fail';
        }

        $url = $this->baseUrl($cfg->mode ?? 'test') . '/Transactions/GetTransactionStatus?orderTrackingId=' . urlencode($orderTrackingId);
        $res = Http::acceptJson()->withToken($token)->get($url);

        if (!$res->successful()) {
            return 'fail';
        }

        $json = $res->json();
        $statusDesc = strtoupper((string)($json['payment_status_description'] ?? ''));

        $payment = $this->payment::query()->where('transaction_id', $orderTrackingId)->first();
        if (!$payment) {
            return 'fail';
        }

        if (str_contains($statusDesc, 'COMPLET') || str_contains($statusDesc, 'SUCCESS')) {
            $payment->update([
                'payment_method' => 'pesapal',
                'is_paid' => 1,
            ]);
            $payment = $this->payment::query()->where('transaction_id', $orderTrackingId)->first();
            if (isset($payment) && function_exists($payment->hook)) {
                call_user_func($payment->hook, $payment);
            }
            return 'success';
        }

        return 'fail';
    }
}
