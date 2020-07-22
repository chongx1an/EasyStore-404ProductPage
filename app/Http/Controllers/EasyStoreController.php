<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Shop;

class EasyStoreController extends Controller
{

    private $client_id = "appf13f00a9c3894b84";
    private $client_secret = "72b566f95be98c546e6ac623941f080b";

    private $client_id_blue = "appe7066f3464092d19";
    private $client_secret_blue = "8b4036c2d4b870089f556b67ae72ed84";

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

        $this->slack_say("#cx", json_encode("Entering index"));

        $timestamp = $request->timestamp;
        $shop_url = $request->shop;
        $hmac = $request->hmac;

        $shop = Shop::where('url', $shop_url)
                    ->where('is_deleted', false)
                    ->first();

        $this->slack_say("#cx", json_encode([
            "message" => "Store found",
            "shop" => $shop
        ]));

        if ($shop) {
            $request->session()->put('easystore-shop', $shop_url);
            return redirect('/easystore/setting');
        }

        return $this->redirect_to_install();

    }

    public function install(Request $request) {

        $this->slack_say("#cx", json_encode("Entering install"));

        $code = $request->code;
        $timestamp = $request->timestamp;
        $shop_url = $request->shop;
        $hmac = $request->hmac;

        $url = 'https://'.$shop_url.'/api/1.0/oauth/access_token';

        //open connection
        $ch = curl_init();

        $data = json_encode([
            'client_id' => $this->client_id_blue,
            'client_secret' => $this->client_secret_blue,
            'code' => $code
        ]);

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //execute post
        $result = curl_exec($ch);
        curl_close($ch);

        $this->slack_say("#cx", json_encode([
            "message" => "Calling Access token endpoint",
            "url" => $url,
            "data" => $data,
            "result" => $result
        ]));

        $access_token = $result["access_token"];

        if ($access_token) {

            $store = Shop::where('url', $shop_url)->first();

            if(empty($store)){
                $store = new Store;
                $store->url = $shop_url;
            }

            $store->access_token = $access_token;
            $store->is_deleted = false;
            $store->save();

            return redirect('/easystore/setting');

        }

        $this->slack_say("#cx", json_encode([
            "error" => "Get access token error",
            "data" => $result
        ]));

        return $this->redirect_to_install();

    }

    public function setting(Request $request){

        return view('/index');

    }

    private function redirect_to_install() {

        $redirect_uri = "https://" . $_SERVER['SERVER_NAME'] . $this->redirect_path;

        $easystore_url = "https://admin.easystore.co";
        $easystore_url_blue = "https://admin.easystore.blue";

        $url = "$easystore_url_blue/oauth/authorize?app_id=". $this->client_id_blue ."&scope=". implode(",", $this->app_scopes) ."&redirect_uri=" . $redirect_uri;

        $this->slack_say("#cx", json_encode("Store not found, redirect to $url"));

        return redirect()->away($url);

    }

    private function slack_say($channel, $text){
        $msg = "payload=".json_encode([
            'text' => $text,
            'channel' => $channel,
        ]);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://hooks.slack.com/services/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");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $msg);
        $reply = curl_exec($ch);
        curl_close($ch);
    }


}
