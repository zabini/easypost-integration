<?php

namespace App\Infra\Http\Controller\ShippingLabel;

use App\Core\Application\ShippingLabel\CreateHandler;
use App\Infra\Http\Request\ShippingLabel\CreateRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class CreateController
{
    public function __invoke(CreateRequest $request, CreateHandler $handler): JsonResponse
    {
        $shippingLabel = $handler->handle($request->toCommand());

        return response()->json([
            'data' => ShippingLabelResponseData::fromDomain($shippingLabel),
        ], Response::HTTP_CREATED);
    }
}
