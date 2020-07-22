<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EasyStoreController extends Controller
{

    private $client_id = "appkjhgfgkl";

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
        'read_currencies',
        'delete_order'
    ];

    private $redirect_path = "/easystore/install";

    public function index(Request $request) {

        $timestamp = $request->timestamp;
        $shop = $request->shop;
        $hmac = $request->hmac;

        $redirect_uri = $_SERVER['SERVER_NAME'] . $this->redirect_path;

        $url = " https://admin.easystore.co/oauth/authorize?app_id=". $this->client_id ."&scope=". implode(",", $this->app_scopes) ."&redirect_uri=" . $redirect_uri;

        return ["url" => $url];
        return redirect()->away($url);

    }

    public function install(Request $request) {

        $code = $request->code;
        $timestamp = $request->timestamp;
        $shop = $request->shop;
        $hmac = $request->hmac;

    }

    private function getAccessToken($code = ''){
		$dta = array('client_id' => $this->_API['API_KEY'], 'client_secret' => $this->_API['API_SECRET'], 'code' => $code);
		$data = $this->call(['METHOD' => 'POST', 'URL' => 'oauth/access_token', 'DATA' => $dta], FALSE);
		return isset($data->access_token) ? $data->access_token : false;
    }

}
