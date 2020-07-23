<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Shop;

class EasyStoreController extends Controller
{

    private $client_id;
    private $client_secret;

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

    public function __construct(Request $request){

        $this->client_id = env('EASYSTORE_CLIENT_ID');
        $this->client_secret = env('EASYSTORE_CLIENT_SECRET');

    }

    public function index(Request $request) {

        $timestamp = $request->timestamp;
        $shop_url = $request->shop;
        $hmac = $request->hmac;

        $hmac_correct = $this->verifyHmac($hmac, [ "shop" => $shop_url, "timestamp" => $timestamp ]);

        if (!$hmac_correct) {
            return response()->json(['errors' => 'Hmac validate fail'], 400);
        }

        $shop = Shop::where('url', $shop_url)
                    ->where('is_deleted', false)
                    ->first();

        if (!$shop) {
            return $this->redirectToInstall();
        }

        return redirect('/easystore/setting');

    }

    public function install(Request $request) {

        $code = $request->code;
        $timestamp = $request->timestamp;
        $shop_url = $request->shop;
        $hmac = $request->hmac;

        $hmac_correct = $this->verifyHmac($hmac, [ "code" => $code, "shop" => $shop_url, "timestamp" => $timestamp ]);

        if (!$hmac_correct) {
            return response()->json(['errors' => 'Hmac validate fail'], 400);
        }

        $data = [
            'shop_url' => $shop_url,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'code' => $code
        ];

        $access_token = $this->getAccessToken($data);

        if (!$access_token) {
            return $this->redirectToInstall();
        }

        $shop = Shop::where('url', $shop_url)->first();

        if(empty($shop)){
            $shop = new Shop;
            $shop->url = $shop_url;
        }

        $shop->access_token = $access_token;
        $shop->is_deleted = false;
        $shop->save();

        $this->subscribeUninstallWebhook($shop_url);

        return redirect('/easystore/setting');


    }

    public function setting(Request $request){

        return view('index');

    }

    public function uninstall(Request $request) {

        if ($request->header('Easystore-Topic') != 'app/uninstall') {
            return response()->json(['errors' => 'Topic invalid'], 400);
        }

        $data = file_get_contents('php://input');
        $hmac = hash_hmac('sha256', $data, $this->app_secret);
        $shop_url = $request->header('Easystore-Shop-Domain');

        if ($hmac != $request->header('Easystore-Hmac-Sha256')) {
            return response()->json(['errors' => 'Hmac validate fail'], 400);
        }

        $shop = Shop::where('url', $shop_url)->first();

        if (!$shop) {
            return response()->json(['errors' => 'Shop not exists'], 200);
        }

        $store->is_deleted = true;
        $store->display_data = null;
        $store->save();

        return response()->json(['success' => 'Shop deleted successfully.'], 200);


    }

    private function getAccessToken($data) {

        $shop_url = $data["shop_url"];

        $url = 'https://'.$shop_url.'/api/1.0/oauth/access_token';

        $data = [
            'client_id' => $data["client_id"],
            'client_secret' => $data["client_secret"],
            'code' => $data["code"]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($result, true);

        $access_token = $result["access_token"];

        return $access_token;

    }

    private function subscribeUninstallWebhook($shop_url) {

        $url = 'https://'.$shop_url.'/api/1.0/webhooks.json';

        $webhook_url = "https://" . $_SERVER['SERVER_NAME'] . '/easystore/uninstall';

        $data = [
            'webhook' => [
                'topic' => 'app/uninstall',
                'url' => $webhook_url,
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);

    }

    private function redirectToInstall() {

        $redirect_uri = "https://" . $_SERVER['SERVER_NAME'] . $this->redirect_path;

        $easystore_url = "https://admin.easystore.co";

        $url = "$easystore_url/oauth/authorize?app_id=". $this->client_id ."&scope=". implode(",", $this->app_scopes) ."&redirect_uri=" . $redirect_uri;

        return redirect()->away($url);

    }

    private function verifyHmac($hmac, $data) {

        ksort($data);

        $data = urldecode(http_build_query($data));

        $calculated = hash_hmac('sha256', $data, $this->client_secret);

        return $hmac === $calculated;
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
