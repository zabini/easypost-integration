<?php

namespace App\Core\Domain\ShippingLabel;

use App\Core\Domain\Exceptions\ShippingLabelAddressNotSupportedException;

final class ShippingLabelFactory
{
    public function createShipment(array $fromAddress, array $toAddress, array $parcel): ShippingLabelShipment
    {
        $normalizedFromAddress = $this->normalizeAddress($fromAddress);
        $normalizedToAddress = $this->normalizeAddress($toAddress);

        $errors = [];

        if (($normalizedFromAddress['country'] ?? null) !== 'US') {
            $errors['from_address.country'] = ['Only addresses in the United States are accepted.'];
        }

        if (($normalizedToAddress['country'] ?? null) !== 'US') {
            $errors['to_address.country'] = ['Only addresses in the United States are accepted.'];
        }

        if ($errors !== []) {
            throw new ShippingLabelAddressNotSupportedException($errors);
        }

        return new ShippingLabelShipment(
            fromAddress: $normalizedFromAddress,
            toAddress: $normalizedToAddress,
            parcel: $this->normalizeParcelForShipment($parcel),
        );
    }

    public function createPersistedLabel(int $userId, PurchasedShippingLabel $purchasedLabel): ShippingLabel
    {
        return new ShippingLabel(
            id: null,
            userId: $userId,
            easypostShipmentId: $purchasedLabel->shipmentId(),
            easypostRateId: $purchasedLabel->rateId(),
            trackingCode: $purchasedLabel->trackingCode(),
            labelUrl: $purchasedLabel->labelUrl(),
            carrier: $purchasedLabel->carrier(),
            service: $purchasedLabel->service(),
            rateAmount: $purchasedLabel->rateAmount(),
            rateCurrency: $purchasedLabel->rateCurrency(),
            status: $purchasedLabel->status(),
            fromAddress: $this->normalizeAddress($purchasedLabel->fromAddress()),
            toAddress: $this->normalizeAddress($purchasedLabel->toAddress()),
            parcel: $this->normalizeParcelForStorage($purchasedLabel->parcel()),
            rawResponse: $purchasedLabel->rawResponse(),
        );
    }

    private function normalizeAddress(array $address): array
    {
        $normalizedAddress = [
            'name' => $this->normalizeString($address['name'] ?? null),
            'street1' => $this->normalizeString($address['street1'] ?? null),
            'city' => $this->normalizeString($address['city'] ?? null),
            'state' => $this->normalizeState($address['state'] ?? null),
            'zip' => $this->normalizeString($address['zip'] ?? null),
            'country' => $this->normalizeCountry($address['country'] ?? null),
        ];

        $street2 = $this->normalizeString($address['street2'] ?? null);

        if ($street2 !== null) {
            $normalizedAddress['street2'] = $street2;
        }

        return array_filter(
            $normalizedAddress,
            static fn (mixed $value): bool => $value !== null,
        );
    }

    private function normalizeParcelForShipment(array $parcel): array
    {
        return [
            'weight' => $this->normalizeNumber($parcel['weight_oz'] ?? null),
            'length' => $this->normalizeNumber($parcel['length_in'] ?? null),
            'width' => $this->normalizeNumber($parcel['width_in'] ?? null),
            'height' => $this->normalizeNumber($parcel['height_in'] ?? null),
        ];
    }

    private function normalizeParcelForStorage(array $parcel): array
    {
        return [
            'weight_oz' => $this->normalizeNumber($parcel['weight_oz'] ?? $parcel['weight'] ?? null),
            'length_in' => $this->normalizeNumber($parcel['length_in'] ?? $parcel['length'] ?? null),
            'width_in' => $this->normalizeNumber($parcel['width_in'] ?? $parcel['width'] ?? null),
            'height_in' => $this->normalizeNumber($parcel['height_in'] ?? $parcel['height'] ?? null),
        ];
    }

    private function normalizeString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmedValue = trim($value);

        return $trimmedValue === '' ? null : $trimmedValue;
    }

    private function normalizeState(mixed $value): ?string
    {
        $state = $this->normalizeString($value);

        return $state === null ? null : strtoupper($state);
    }

    private function normalizeCountry(mixed $value): ?string
    {
        $country = $this->normalizeString($value);

        if ($country === null) {
            return null;
        }

        $country = strtoupper($country);

        return $country === 'USA' ? 'US' : $country;
    }

    private function normalizeNumber(mixed $value): float
    {
        return round((float) $value, 4);
    }
}
