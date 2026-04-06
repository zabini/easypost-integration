<?php

namespace App\Infra\Http\Controller\ShippingLabel;

use App\Core\Application\ShippingLabel\ListShippingLabels;
use App\Core\Application\ShippingLabel\ListShippingLabelsHandler;
use Illuminate\Http\JsonResponse;

final class ListController
{
    public function __invoke(ListShippingLabelsHandler $handler): JsonResponse
    {
        $shippingLabels = $handler->handle(new ListShippingLabels());

        return response()->json([
            'data' => array_map(
                static fn ($shippingLabel) => ShippingLabelResponseData::fromDomain($shippingLabel),
                $shippingLabels,
            ),
        ]);
    }
}
