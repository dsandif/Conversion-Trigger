<?php

namespace App\Services;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use http\Env\Response;
use Illuminate\Http\Request;
use http\Header;
use Illuminate\Support\Facades\Config;
use PhpParser\Node\Scalar\String_;

class ProductService{
    protected $http;

    protected $affiliate_service;


    public function __construct(AffiliateService $affiliate_service){
        $this->http = new Client();
        $this->affiliate_service = $affiliate_service;
    }


    /**
     * Verifies a product webhook response header.
     *
     * @param $hmac_header
     * @return bool
     */
    public function verify_webhook($hmac_header){
        $data            = file_get_contents('php://input');
        $shopify_key     = Config::get("services.shopify.key");
        $final_hmac      = base64_encode( hash_hmac('sha256', $data, $shopify_key, true));

        return ($hmac_header === $final_hmac);
    }


    /**
     * Creates a webhook for a topic.
     *
     * @param string $webhook_topic
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createWebhook(string $webhook_topic = "products/create")
    {
        $shopify_key      = Config::get("services.shopify.key");
        $shopify_password = Config::get("services.shopify.password");
        $shopify_hostname = Config::get("services.shopify.hostname");
        $shopify_version  = Config::get("services.shopify.version");
        $ngrok_address    = Config::get('services.ngrok.address');

        $url = 'https://'. $shopify_key .':'. $shopify_password . '@' . $shopify_hostname .'/admin/api/'. $shopify_version .'/webhooks.json';

        $headers = array(
            'Content-Type'  => 'application/json'
        );

        $body = array(
            'webhook' => array(
                'topic'     => "products/create",
                'address'   => $ngrok_address,
                'format'    => 'json'
            ));

        try {
            $response = $this->http->post($url,[
                'headers' => $headers,
                'json' => $body
            ]);

            return Response("Webhook created for " . $webhook_topic,201);

        }catch (RequestException $exception){
            return Response($exception->getMessage() . $webhook_topic,409);
        }
    }

    /**
     * Processes a shopify webhook response.
     *
     * @param Request $request
     * @param $webhook_data
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function processWebhookResponse(Request $request, $webhook_data): bool
    {
        return $this->affiliate_service->newAffilliateTrigger($webhook_data);
    }

    /**
     * @param Request $request
     * @return array|string|null
     */
    public function getHmacHeader(Request $request)
    {
        $hmac_header= "";

        if ($request->hasHeader("X-Shopify-Hmac-Sha256")) {
            //get header
            $hmac_header = $request->header("X-Shopify-Hmac-Sha256");

        }
        return $hmac_header;
    }

}
