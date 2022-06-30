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
        $this->requestLog();
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

    public function notify()
    {

    }
}
