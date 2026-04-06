<?php

namespace App\Infra\Http\Controller\ShippingLabel;

use App\Core\Application\ShippingLabel\GetShippingLabel;
use App\Core\Application\ShippingLabel\GetShippingLabelHandler;
use Illuminate\Http\JsonResponse;

final class ShowController
{
    public function __invoke(int $id, GetShippingLabelHandler $handler): JsonResponse
    {
        $shippingLabel = $handler->handle(new GetShippingLabel($id));

        return response()->json([
            'data' => ShippingLabelResponseData::fromDomain($shippingLabel),
        ]);
    }
}
