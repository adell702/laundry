<?php

namespace App\Http\Controllers;

use App\Exceptions\TripayException;
use App\Services\Tripay\TripayClient;
use App\Services\Tripay\TripayPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use JsonException;

class TripayCallbackController extends Controller
{
    public function __invoke(
        Request $request,
        TripayClient $client,
        TripayPaymentService $payments
    ): JsonResponse {
        if (! $client->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Payment gateway is not configured.',
            ], 503);
        }

        $rawBody = $request->getContent();
        $providedSignature = (string) $request->header('X-Callback-Signature', '');

        if ($providedSignature === ''
            || ! hash_equals($client->callbackSignature($rawBody), $providedSignature)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid signature.',
            ], 401);
        }

        if ($request->header('X-Callback-Event') !== 'payment_status') {
            return response()->json([
                'success' => false,
                'message' => 'Unrecognized callback event.',
            ], 422);
        }

        try {
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid JSON payload.',
            ], 400);
        }

        if (! is_array($payload)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid callback payload.',
            ], 422);
        }

        $validator = Validator::make($payload, [
            'reference' => ['required', 'string', 'max:100'],
            'merchant_ref' => ['required', 'string', 'max:100'],
            'payment_method' => ['required', 'string', 'max:255'],
            'payment_method_code' => ['required', 'string', 'max:50'],
            'total_amount' => ['required', 'integer', 'min:1'],
            'fee_merchant' => ['required', 'integer', 'min:0'],
            'fee_customer' => ['required', 'integer', 'min:0'],
            'total_fee' => ['required', 'integer', 'min:0'],
            'amount_received' => ['required', 'integer', 'min:0'],
            'is_closed_payment' => ['required', 'integer', 'in:1'],
            'status' => ['required', 'string', 'in:PAID,FAILED,EXPIRED,REFUND'],
            'paid_at' => ['nullable', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid callback payload.',
            ], 422);
        }

        try {
            $payments->handleCallback($validator->validated());
        } catch (TripayException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 409);
        }

        return response()->json(['success' => true]);
    }
}
