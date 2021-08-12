<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class ProductController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    private $product_service;

    public function __construct(ProductService $product_service)
    {
        $this->product_service = $product_service;
    }

    /**
     * Creates a product webhook.
     *
     * @return Response
     */
    public function postWebhook(): String
    {
        $response = $this->product_service->createWebhook("products/create");
        return $response;
    }


    /**
     * Accepts a product webhook response for processing.
     *
     * @param Request $request
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function webhookResponse(Request $request): void
    {
        $webhook_data = json_decode($request->getContent(),true);
//        //get hmac header
//        $hmac_header = $this->product_service->getHmacHeader($request);
//        //verify webhook
//        $verified = $this->product_service->verify_webhook($hmac_header);
//
//        if($verified){}

        $this->product_service->processWebhookResponse($request, $webhook_data);
    }
}
