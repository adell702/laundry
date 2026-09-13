<?php

namespace App\Services\Tripay;

use App\Exceptions\TripayException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TripayClient
{
    public function isConfigured(): bool
    {
        return (bool) config('services.tripay.enabled')
            && filled(config('services.tripay.api_key'))
            && filled(config('services.tripay.private_key'))
            && filled(config('services.tripay.merchant_code'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function paymentChannels(): array
    {
        $this->ensureConfigured();

        $cacheKey = 'tripay.channels.'.config('services.tripay.mode').'.'
            .hash('sha256', (string) config('services.tripay.api_key'));

        return Cache::remember($cacheKey, now()->addMinutes(10), function (): array {
            $data = $this->get('/merchant/payment-channel');

            return array_values(array_filter(
                $data,
                fn (mixed $channel): bool => is_array($channel) && ($channel['active'] ?? false) === true
            ));
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createTransaction(array $payload): array
    {
        $this->ensureConfigured();

        try {
            $response = Http::acceptJson()
                ->asForm()
                ->withToken((string) config('services.tripay.api_key'))
                ->timeout($this->timeout())
                ->post($this->baseUrl().'/transaction/create', $payload);
        } catch (ConnectionException $exception) {
            throw new TripayException('Tidak dapat terhubung ke penyedia pembayaran. Coba lagi beberapa saat.', previous: $exception);
        }

        return $this->responseData($response);
    }

    /**
     * @return array<string, mixed>
     */
    public function transactionDetail(string $reference): array
    {
        $this->ensureConfigured();

        return $this->get('/transaction/detail', ['reference' => $reference]);
    }

    public function transactionSignature(string $merchantRef, int $amount): string
    {
        return hash_hmac(
            'sha256',
            (string) config('services.tripay.merchant_code').$merchantRef.$amount,
            (string) config('services.tripay.private_key')
        );
    }

    public function callbackSignature(string $rawBody): string
    {
        return hash_hmac('sha256', $rawBody, (string) config('services.tripay.private_key'));
    }

    public function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new TripayException('Konfigurasi pembayaran online belum lengkap. Hubungi administrator aplikasi.');
        }
    }

    /**
     * @param  array<string, scalar>  $query
     * @return array<mixed>
     */
    private function get(string $path, array $query = []): array
    {
        try {
            $response = Http::acceptJson()
                ->withToken((string) config('services.tripay.api_key'))
                ->timeout($this->timeout())
                ->retry(2, 250, throw: false)
                ->get($this->baseUrl().$path, $query);
        } catch (ConnectionException $exception) {
            throw new TripayException('Tidak dapat terhubung ke penyedia pembayaran. Coba lagi beberapa saat.', previous: $exception);
        }

        return $this->responseData($response);
    }

    /**
     * @return array<mixed>
     */
    private function responseData(Response $response): array
    {
        $body = $response->json();

        if (! $response->successful() || ! is_array($body) || ($body['success'] ?? false) !== true) {
            $message = is_array($body) ? ($body['message'] ?? null) : null;

            throw new TripayException(
                is_string($message) && $message !== ''
                    ? 'Penyedia pembayaran: '.$message
                    : 'Penyedia pembayaran mengembalikan respons yang tidak valid.'
            );
        }

        $data = $body['data'] ?? null;

        if (! is_array($data)) {
            throw new TripayException('Penyedia pembayaran tidak mengirimkan data transaksi yang valid.');
        }

        return $data;
    }

    private function baseUrl(): string
    {
        return config('services.tripay.mode') === 'production'
            ? 'https://tripay.co.id/api'
            : 'https://tripay.co.id/api-sandbox';
    }

    private function timeout(): int
    {
        return max(5, min((int) config('services.tripay.timeout', 15), 60));
    }
}
