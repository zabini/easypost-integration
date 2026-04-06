<?php

namespace App\Core\Domain\ShippingLabel;

final readonly class ShippingLabelQuote
{
    /**
     * @param  list<ShippingLabelRate>  $rates
     */
    public function __construct(
        private string $shipmentId,
        private array $fromAddress,
        private array $toAddress,
        private array $parcel,
        private array $rates,
        private array $rawResponse,
    ) {}

    public function shipmentId(): string
    {
        return $this->shipmentId;
    }

    public function fromAddress(): array
    {
        return $this->fromAddress;
    }

    public function toAddress(): array
    {
        return $this->toAddress;
    }

    public function parcel(): array
    {
        return $this->parcel;
    }

    /**
     * @return list<ShippingLabelRate>
     */
    public function rates(): array
    {
        return $this->rates;
    }

    public function rawResponse(): array
    {
        return $this->rawResponse;
    }

    public function findLowestRateByCarrier(string $carrier): ?ShippingLabelRate
    {
        $selectedRate = null;
        $selectedAmount = null;
        $normalizedCarrier = strtoupper($carrier);

        foreach ($this->rates as $rate) {
            if (strtoupper($rate->carrier()) !== $normalizedCarrier) {
                continue;
            }

            $rateAmount = is_numeric($rate->rateAmount())
                ? (float) $rate->rateAmount()
                : INF;

            if ($selectedRate === null || $rateAmount < $selectedAmount) {
                $selectedRate = $rate;
                $selectedAmount = $rateAmount;
            }
        }

        return $selectedRate;
    }
}
