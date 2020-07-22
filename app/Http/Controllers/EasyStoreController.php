<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EasyStoreController extends Controller
{

    private $client_id = "appf13f00a9c3894b84";
    private $client_secret = "72b566f95be98c546e6ac623941f080b";

    private $app_scopes = [
        'read_script_tags',
        'write_script_tags',
        'read_snippets',
        'write_snippets',
        'read_customers',
        'write_customers',
        'read_orders',
        'write_orders',
        'read_products',
        'write_products',
        'read_content',
        'write_content',
        'read_fulfillments',
        'write_fulfillments',
        'read_shipping',
        'write_shipping',
        'read_currencies'
    ];

    private $redirect_path = "/easystore/install";

    public function index(Request $request) {

        $timestamp = $request->timestamp;
        $shop = $request->shop;
        $hmac = $request->hmac;

        $redirect_uri = $_SERVER['SERVER_NAME'] . $this->redirect_path;

        $url = " https://admin.easystore.co/oauth/authorize?app_id=". $this->client_id ."&scope=". implode(",", $this->app_scopes) ."&redirect_uri=" . $redirect_uri;

        dd($url);
        return redirect()->away($url);

    }

    public function install(Request $request) {

        $code = $request->code;
        $timestamp = $request->timestamp;
        $shop = $request->shop;
        $hmac = $request->hmac;

        return [
            "shop" => $shop
        ];

        $url = $shop.'/api/1.0/oauth/access_token';

        //open connection
        $ch = curl_init();

        $data = json_encode([
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'code' => $code
        ]);

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        //execute post
        $result = curl_exec($ch);
        echo $result;

        return $result;

    }


}
