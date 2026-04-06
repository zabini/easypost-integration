<?php

namespace App\Infra\Http\Controller\ShippingLabel;

use App\Core\Application\ShippingLabel\CreateHandler;
use App\Core\Domain\ShippingLabel\ShippingLabel;
use App\Infra\Http\Request\ShippingLabel\CreateRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class CreateController
{
    public function __invoke(CreateRequest $request, CreateHandler $handler): JsonResponse
    {
        $shippingLabel = $handler->handle($request->toCommand());

        return response()->json([
            'data' => $this->toResponse($shippingLabel),
        ], Response::HTTP_CREATED);
    }

    private function toResponse(ShippingLabel $shippingLabel): array
    {
        return [
            'id' => $shippingLabel->id(),
            'easypost_shipment_id' => $shippingLabel->easypostShipmentId(),
            'easypost_rate_id' => $shippingLabel->easypostRateId(),
            'tracking_code' => $shippingLabel->trackingCode(),
            'label_url' => $shippingLabel->labelUrl(),
            'carrier' => $shippingLabel->carrier(),
            'service' => $shippingLabel->service(),
            'rate_amount' => $shippingLabel->rateAmount(),
            'rate_currency' => $shippingLabel->rateCurrency(),
            'status' => $shippingLabel->status()->value,
            'from_address' => $shippingLabel->fromAddress(),
            'to_address' => $shippingLabel->toAddress(),
            'parcel' => $shippingLabel->parcel(),
            'created_at' => $shippingLabel->createdAt()?->format(DATE_ATOM),
            'updated_at' => $shippingLabel->updatedAt()?->format(DATE_ATOM),
        ];
    }
}
