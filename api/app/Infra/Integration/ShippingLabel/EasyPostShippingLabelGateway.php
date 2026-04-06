<?php

namespace App\Infra\Integration\ShippingLabel;

use App\Core\Domain\Contracts\ShippingLabel\ShippingLabelGateway;
use App\Core\Domain\Exceptions\ShippingProviderAuthenticationException;
use App\Core\Domain\Exceptions\ShippingProviderRequestException;
use App\Core\Domain\Exceptions\ShippingProviderUnavailableException;
use App\Core\Domain\Exceptions\ShippingProviderUnexpectedResponseException;
use App\Core\Domain\ShippingLabel\PurchasedShippingLabel;
use App\Core\Domain\ShippingLabel\ShippingLabelPurchase;
use App\Core\Domain\ShippingLabel\ShippingLabelQuote;
use App\Core\Domain\ShippingLabel\ShippingLabelRate;
use App\Core\Domain\ShippingLabel\ShippingLabelShipment;
use App\Core\Domain\ShippingLabel\ShippingLabelStatus;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;

final readonly class EasyPostShippingLabelGateway implements ShippingLabelGateway
{
    public function __construct(
        private Factory $http,
        private string $apiKey,
        private string $baseUrl,
        private int $timeout,
    ) {
    }

    public function createShipment(ShippingLabelShipment $shipment): ShippingLabelQuote
    {
        $response = $this->post('shipments', [
            'shipment' => [
                'from_address' => $shipment->fromAddress(),
                'to_address' => $shipment->toAddress(),
                'parcel' => $shipment->parcel(),
            ],
        ]);

        return new ShippingLabelQuote(
            shipmentId: $this->requireString($response, 'id'),
            fromAddress: $this->requireArray($response, 'from_address'),
            toAddress: $this->requireArray($response, 'to_address'),
            parcel: $this->requireArray($response, 'parcel'),
            rates: $this->mapRates($this->requireArray($response, 'rates')),
            rawResponse: $response,
        );
    }

    public function buyShipment(ShippingLabelPurchase $purchase): PurchasedShippingLabel
    {
        $response = $this->post(sprintf('shipments/%s/buy', $purchase->shipmentId()), [
            'rate' => [
                'id' => $purchase->rateId(),
            ],
        ]);

        $selectedRate = $this->requireArray($response, 'selected_rate');
        $postageLabel = $this->requireArray($response, 'postage_label');

        return new PurchasedShippingLabel(
            shipmentId: $this->requireString($response, 'id'),
            rateId: $this->optionalString($selectedRate, 'id') ?? $purchase->rateId(),
            trackingCode: $this->optionalString($response, 'tracking_code'),
            labelUrl: $this->extractLabelUrl($postageLabel),
            carrier: $this->requireString($selectedRate, 'carrier'),
            service: $this->requireString($selectedRate, 'service'),
            rateAmount: $this->requireString($selectedRate, 'rate'),
            rateCurrency: $this->requireString($selectedRate, 'currency'),
            status: ShippingLabelStatus::Purchased,
            fromAddress: $this->requireArray($response, 'from_address'),
            toAddress: $this->requireArray($response, 'to_address'),
            parcel: $this->requireArray($response, 'parcel'),
            rawResponse: $response,
        );
    }

    private function post(string $uri, array $payload): array
    {
        if (trim($this->apiKey) === '') {
            throw new ShippingProviderAuthenticationException('EasyPost API key is not configured.');
        }

        try {
            $response = $this->http
                ->baseUrl(rtrim($this->baseUrl, '/'))
                ->acceptJson()
                ->asJson()
                ->timeout($this->timeout)
                ->withBasicAuth($this->apiKey, '')
                ->post($uri, $payload);
        } catch (ConnectionException $exception) {
            throw new ShippingProviderUnavailableException(previous: $exception);
        }

        return $this->handleResponse($response);
    }

    private function handleResponse(Response $response): array
    {
        if (in_array($response->status(), [401, 403], true)) {
            throw new ShippingProviderAuthenticationException();
        }

        if ($response->serverError()) {
            throw new ShippingProviderUnavailableException();
        }

        if ($response->clientError()) {
            throw new ShippingProviderRequestException(
                $this->extractErrorMessage($response) ?? 'Shipping provider rejected the request.',
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new ShippingProviderUnexpectedResponseException();
        }

        return $payload;
    }

    /**
     * @param array<int, array<string, mixed>> $rates
     * @return list<ShippingLabelRate>
     */
    private function mapRates(array $rates): array
    {
        $mappedRates = [];

        foreach ($rates as $rate) {
            if (! is_array($rate)) {
                throw new ShippingProviderUnexpectedResponseException();
            }

            $mappedRates[] = new ShippingLabelRate(
                id: $this->requireString($rate, 'id'),
                carrier: $this->requireString($rate, 'carrier'),
                service: $this->requireString($rate, 'service'),
                rateAmount: $this->requireString($rate, 'rate'),
                rateCurrency: $this->requireString($rate, 'currency'),
            );
        }

        return $mappedRates;
    }

    private function extractLabelUrl(array $postageLabel): string
    {
        return $this->optionalString($postageLabel, 'label_pdf_url')
            ?? $this->requireString($postageLabel, 'label_url');
    }

    private function extractErrorMessage(Response $response): ?string
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            return null;
        }

        $message = data_get($payload, 'error.message')
            ?? data_get($payload, 'message')
            ?? data_get($payload, 'errors.0.message');

        return is_string($message) && trim($message) !== ''
            ? $message
            : null;
    }

    private function requireArray(array $payload, string $key): array
    {
        $value = $payload[$key] ?? null;

        if (! is_array($value)) {
            throw new ShippingProviderUnexpectedResponseException();
        }

        return $value;
    }

    private function requireString(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw new ShippingProviderUnexpectedResponseException();
        }

        return $value;
    }

    private function optionalString(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (! is_string($value) || trim($value) === '') {
            throw new ShippingProviderUnexpectedResponseException();
        }

        return $value;
    }
}
