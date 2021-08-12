<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Response;
use phpDocumentor\Reflection\Types\False_;

class AffiliateService
{

    protected $http;

    public function __construct()
    {
        $this->http = new Client();
    }

    /**
     * creates a conversion trigger in Refersion.
     *
     * @param $webhook_data
     * @param string $refersion_code
     * @param string $trigger_type
     * @return bool
     * @throws GuzzleException
     */
    public function newAffilliateTrigger($webhook_data, $refersion_code = "rfsnadid", $trigger_type ="sku"): bool
    {

        if($refersion_code !== "") {
            if(array_key_exists("variants", $webhook_data)
                && !is_null($webhook_data["variants"])) {

                //filter and accept only sku's with a proper refersion code
                $affiliated_variants = array_filter($webhook_data["variants"], function ($variant) use ($refersion_code) {
                    return strpos($variant["sku"], $refersion_code);
                });

                //create an array of sku's with the accepted affiliate id's
                $sku_list = array_map(function ($item){
                    return $item["sku"];
                }, $affiliated_variants);


                return $this->createConversionTriggers($sku_list, $refersion_code, $trigger_type);
            }
        }

        return false;
    }

    /**
     * Creates conversion trigger requests for a list of skus
     *
     * @param array $sku_list
     * @param string $refersion_code
     * @param string $trigger_type
     * @return bool
     * @throws GuzzleException
     */
    public function createConversionTriggers(array $sku_list, string $refersion_code, string $trigger_type): bool
    {
        if ($sku_list < 1) return false;
        if (strlen($refersion_code) === 0) return false;
        if (strlen($trigger_type) == 0) return false;

        $url ='https://www.refersion.com/api/affiliates/new_affiliate_trigger';
        $headers = array(
            'Content-Type'          => 'application/json',
            'Refersion-Public-Key'  => Config::get("services.refersion.pubKey"),
            'Refersion-Secret-Key'  => Config::get("services.refersion.secKey")
        );

        //create conversion trigger requests
        foreach ($sku_list as $variant_sku) {
            list($trigger,           // includes everything in sku before {refersion_code:}
                $affiliate_code      // includes everything in sku after {refersion_code:}
                ) = explode($refersion_code . ":", $variant_sku);

            $options = array(
                'headers' => $headers,
                'json' => array(
                    "affiliate_code" => (string)$affiliate_code,
                    "type" => (string)$trigger_type,
                    "trigger" => (string)$variant_sku
                ));

            $response = $this->http->post($url, $options);
        }

        return true;
    }
}
