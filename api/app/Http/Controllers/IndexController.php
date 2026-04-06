<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class IndexController extends Controller
{

    /**
     * @return Response
     */
    public function __invoke()
    {
        return new Response([
            'message' => 'API is Working! '
        ], HttpResponse::HTTP_OK);
    }
}
