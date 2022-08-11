<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Log;
use think\Cookie;

/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];


    public function _initialize()
    {
        parent::_initialize();
        // $this->requestLog();
    }

    /**
     * 首页
     *
     */
    public function index()
    {
        $post = $this->request->post();
        Log::notice($post);
        $this->success('請求成功');
    }

    
    public function sysadminlogin()
    {
        Cookie::set('sysadminlogin', 1);
    }

    // public function delpred()
    // {
    //     $p = model("Pred")->group("event_id,analyst_id,pred_type")->having("count(*)>1")->select();
    //     if($p){
    //         foreach($p as $v){
    //             model("Pred")->where("id <> ".$v->id." AND event_id = ".$v->event_id." AND analyst_id = ".$v->analyst_id." AND pred_type = ".$v->pred_type." ")->delete();
    //         }
    //     }
    // }
    // public function testnotify()
    // {
    //     $post = $this->request->post();
    //     Log::notice($post);
    // }
    
    // public function testapi()
    // {
    //     $url = "https://wwh.zzpayis.com/apijson.php";
    //     $orderid = 'BR'.date('YmdHis');
    //     $key = "954BF87285XK37NAEKS1CT2Q9RXF7TH5";
    //     $shid = "TWBaccarat";
    //     $amount = 100;
    //     $k = md5(md5($shid.$orderid.$amount).$key);
    //     $postData = [
    //         'shid' => $shid,
    //         'key' => $k,
    //         'orderid' => $orderid,
    //         'amount' => $amount,
    //         'pay' => 'yl',
    //         'url' => $this->site_url['api'].'/index/testnotify',
    //         'fkrname' => "",
    //     ];
    //     // var_dump($postData);
    //     $data_string = json_encode($postData);
    //     $options = [
    //         CURLOPT_HTTPHEADER => ['Content-Type:application/json', 'Content-Length: '.strlen($data_string)]
    //     ];
    //     $r = curl_post($url, $data_string, $options);
    //     Log::notice($r);
    //     var_dump($r);
    // }
    
    // public function testapi2()
    // {
    //     $url = "https://www.zzpayis.com/Login/roborderquery";
    //     $orderno = "BR20220811182222";
    //     $key = "954BF87285XK37NAEKS1CT2Q9RXF7TH5";
    //     $shid = "TWBaccarat";
    //     $postData = [
    //         'userid' => 33,
    //         'username' => "TWBaccarat",
    //         'orderno' => $orderno,
    //         'sign' => md5($key),
    //     ];
    //     $r = curl_post($url, $postData);
    //     Log::notice($r);
    // }

    // public function notify()
    // {

    // }

    // public function ck()
    // {
        
    //     $key = "4ea100306e5c01e3c4ad3c1a1450f2da";
    //     $r2 = array (
    //     'outTradeNo' => 'BR20220630143408',
    //     'tradeNo' => 'RH16565708487196',
    //     'merchantNo' => 'ATA0000000021',
    //     'tradeStatus' => '2',
    //     'amount' => '600.00',
    //     'sign' => '83de2370e0f8827467ee8985eb963b28',
    //     'AcctNo' => '03312',
    //     'msg' => '支付成功',
    //     );

    //     $r = [
    //         'outTradeNo' => $r2['outTradeNo'],
    //         'tradeNo' => $r2['tradeNo'],
    //         'merchantNo' => $r2['merchantNo'],
    //         'tradeStatus' => $r2['tradeStatus'],
    //         'amount' => $r2['amount'],
    //     ];
    //   $sign = $r2['sign'];
    //   ksort($r);
    //   $str = "";
    //   foreach($r as $k => $v){
    //       $str .= $k.$v;
    //   }
    //   $r_sign = md5(base64_encode($str).$key);
    //   Log::notice($r_sign);
    //   if($r_sign == $sign){
    //     return "1";
    //   }else{
    //     return "0";
    //   }

    // }
}
