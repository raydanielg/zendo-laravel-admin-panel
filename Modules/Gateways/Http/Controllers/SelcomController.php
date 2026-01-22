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

class SelcomController extends Controller
{
    use Processor;

    private PaymentRequest $payment;

    public function __construct(PaymentRequest $payment)
    {
        $this->payment = $payment;
    }

    private function config(): ?object
    {
        $config = $this->paymentConfig('seclome', PAYMENT_CONFIG);
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

    private function baseUrl(?string $baseUrl): string
    {
        $url = $baseUrl ? rtrim($baseUrl, '/') : 'https://apigw.selcommobile.com';
        return $url;
    }

    private function timestamp(): string
    {
        return now()->toIso8601String();
    }

    private function authorization(string $apiKey): string
    {
        return base64_encode($apiKey);
    }

    private function computeDigest(array $parameters, string $signedFields, string $timestamp, string $apiSecret): string
    {
        $fieldsOrder = array_map('trim', explode(',', $signedFields));
        $signData = 'timestamp=' . $timestamp;
        foreach ($fieldsOrder as $key) {
            $value = $parameters[$key] ?? '';
            $signData .= '&' . $key . '=' . $value;
        }
        return base64_encode(hash_hmac('sha256', $signData, $apiSecret, true));
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
        if (!$cfg || empty($cfg->vendor) || empty($cfg->api_key) || empty($cfg->api_secret)) {
            return $this->paymentResponse($data, 'fail');
        }

        $payer = json_decode($data->payer_information);

        $order = [
            'vendor' => (string)$cfg->vendor,
            'order_id' => (string)$data->id,
            'buyer_email' => $payer->email ?? '',
            'buyer_name' => $payer->name ?? '',
            'buyer_user_id' => (string)($data->payer_id ?? ''),
            'buyer_phone' => $payer->phone ?? '',
            'amount' => (float)$data->payment_amount,
            'currency' => $data->currency_code,
            'payment_methods' => 'ALL',
            'redirect_url' => route('selcom.callback'),
            'cancel_url' => route('payment-cancel'),
            'webhook' => route('selcom.webhook'),
        ];

        $signedFields = implode(',', array_keys($order));
        $timestamp = $this->timestamp();
        $digest = $this->computeDigest($order, $signedFields, $timestamp, (string)$cfg->api_secret);

        $url = $this->baseUrl($cfg->base_url ?? null) . '/v1/vcn/create';

        $res = Http::acceptJson()->asJson()->withHeaders([
            'Authorization' => 'SELCOM ' . $this->authorization((string)$cfg->api_key),
            'Digest-Method' => 'HS256',
            'Digest' => $digest,
            'Timestamp' => $timestamp,
            'Signed-Fields' => $signedFields,
        ])->post($url, $order);

        if (!$res->successful()) {
            return $this->paymentResponse($data, 'fail');
        }

        $json = $res->json();
        $paymentGatewayUrl = $json['data'][0]['payment_gateway_url'] ?? null;
        $reference = $json['reference'] ?? null;

        if (!$paymentGatewayUrl) {
            return $this->paymentResponse($data, 'fail');
        }

        $data->update([
            'payment_method' => 'seclome',
            'transaction_id' => $reference,
        ]);

        return redirect()->away($paymentGatewayUrl);
    }

    public function callback(Request $request): RedirectResponse
    {
        $paymentId = $request->get('order_id') ?? $request->get('orderId') ?? $request->get('order') ?? $request->get('payment_id');
        if (!$paymentId) {
            return redirect()->route('payment-fail');
        }

        $payment = $this->payment::query()->where('id', $paymentId)->first();
        if (!$payment) {
            return redirect()->route('payment-fail');
        }

        return $this->paymentResponse($payment, $payment->is_paid ? 'success' : 'fail');
    }

    public function webhook(Request $request): JsonResponse
    {
        $cfg = $this->config();
        if (!$cfg || empty($cfg->api_key) || empty($cfg->api_secret)) {
            return response()->json(['message' => 'Invalid config'], 403);
        }

        $authorization = $request->header('Authorization');
        if (!$authorization) {
            return response()->json(['message' => 'Missing Authorization'], 403);
        }

        $signedFields = (string)$request->header('Signed-Fields', '');
        $timestamp = (string)$request->header('Timestamp', '');
        $digestHeader = (string)$request->header('Digest', '');

        $payload = $request->all();
        $fields = array_map('trim', explode(',', $signedFields));
        $flat = [];
        foreach ($fields as $f) {
            if ($f === '') {
                continue;
            }
            $flat[$f] = $payload[$f] ?? '';
        }

        if ($signedFields && $timestamp && $digestHeader) {
            $computed = $this->computeDigest($flat, $signedFields, $timestamp, (string)$cfg->api_secret);
            if (!hash_equals($digestHeader, $computed)) {
                return response()->json(['message' => 'Invalid signature'], 403);
            }
        }

        $orderId = $payload['order_id'] ?? null;
        $paymentStatus = strtoupper((string)($payload['payment_status'] ?? ''));
        $result = strtoupper((string)($payload['result'] ?? ''));

        if (!$orderId) {
            return response()->json(['message' => 'Missing order_id'], 422);
        }

        $payment = $this->payment::query()->where(['id' => $orderId])->first();
        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        if ($result === 'SUCCESS' && in_array($paymentStatus, ['COMPLETED', 'SUCCESS'], true)) {
            $payment->update([
                'payment_method' => 'seclome',
                'is_paid' => 1,
                'transaction_id' => $payload['transid'] ?? $payment->transaction_id,
            ]);
            $payment = $this->payment::query()->where(['id' => $orderId])->first();
            if (isset($payment) && function_exists($payment->hook)) {
                call_user_func($payment->hook, $payment);
            }
        }

        return response()->json(['message' => 'OK'], 200);
    }
}
