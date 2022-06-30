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

    
    public function testapi()
    {
        $url = "http://full-speed.ddns.net/Pay/V1";
        $ordernum = 'BR'.date('YmdHis');
        $key = "4ea100306e5c01e3c4ad3c1a1450f2da";
        $merchantNo = "ATA0000000021";
        $postData = [
            'orderNo' => $ordernum,
            'merchantNo' => $merchantNo,
            'tradeType' => 1,
            'amount' => 600,
        ];
        ksort($postData);
        $str = "";
        foreach($postData as $k => $v){
            $str .= $k.$v;
        }
        $postData['sign'] = md5(base64_encode($str).$key);

        $r = curl_post($url, $postData);
        Log::notice($r);
    }
    
    public function testapi2()
    {
        $url = "http://full-speed.ddns.net/Query/V1";
        $ordernum = "BR20220630143408";
        $key = "4ea100306e5c01e3c4ad3c1a1450f2da";
        $merchantNo = "ATA0000000021";
        $postData = [
            'outTradeNo' => $ordernum,
            'merchantNo' => $merchantNo,
        ];
        ksort($postData);
        $str = "";
        foreach($postData as $k => $v){
            $str .= $k.$v;
        }
        $postData['sign'] = md5(base64_encode($str).$key);

        $r = curl_post($url, $postData);
        Log::notice($r);
    }

    public function notify()
    {

    }

    public function ck()
    {
        
        $key = "4ea100306e5c01e3c4ad3c1a1450f2da";
        $r2 = array (
        'outTradeNo' => 'BR20220630143408',
        'tradeNo' => 'RH16565708487196',
        'merchantNo' => 'ATA0000000021',
        'tradeStatus' => '2',
        'amount' => '600.00',
        'sign' => '83de2370e0f8827467ee8985eb963b28',
        'AcctNo' => '03312',
        'msg' => '支付成功',
        );

        $r = [
            'outTradeNo' => $r2['outTradeNo'],
            'tradeNo' => $r2['tradeNo'],
            'merchantNo' => $r2['merchantNo'],
            'tradeStatus' => $r2['tradeStatus'],
            'amount' => $r2['amount'],
        ];
      $sign = $r2['sign'];
      ksort($r);
      $str = "";
      foreach($r as $k => $v){
          $str .= $k.$v;
      }
      $r_sign = md5(base64_encode($str).$key);
      Log::notice($r_sign);
      if($r_sign == $sign){
        return "1";
      }else{
        return "0";
      }

    }
}
