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
     * 重新計算所有預測結果（僅限 event.status = 1 的賽事）
     */
    public function reEvent()
    {
        $modelPred = model('Pred');
        $predList = $modelPred
            ->alias('p')
            ->join('event e', 'e.id = p.event_id')
            ->where('e.status', '=', 1) // ✅ 只重算已結束賽事
            ->field([
                'p.*',
                'e.master_score AS event_master_score',
                'e.guests_score AS event_guests_score',
                'e.event_category_id AS event_category_id',
            ])
            ->select();

        if (!$predList) {
            \think\Log::notice('[reEvent] 無符合條件的預測資料');
            return json(['status' => 'ok', 'msg' => '無符合條件的資料']);
        }

        $recount = 0;
        foreach ($predList as $v) {
            $masterScore = $v['event_master_score'];
            $guestsScore = $v['event_guests_score'];
            $catId = $v['event_category_id'];

            // 跳過沒有比分的（理論上不會發生）
            if ($masterScore === null || $guestsScore === null) {
                continue;
            }

            // 重新計算輸贏結果
            calculateComply($v, $masterScore, $guestsScore, $catId, $modelPred);
            $recount++;
        }

        \think\Log::notice(sprintf('[reEvent] 已重新計算 %d 筆預測結果（僅 status=1）', $recount));

        return json([
            'status' => 'ok',
            'msg' => "已重新計算 {$recount} 筆預測結果（僅限結束賽事）",
        ]);
    }

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
    public function testnotify()
    {
        $post = $this->request->post();
        Log::notice($post);
        return '1|OK';
    }
    public function testback()
    {
        return '轉跳成功';
    }

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

    //4311-9522-2222-2222
    //https://bigwinners.cc/api/index/ecpay
    public function ecpay()
    {
        $url = "https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5";
        $HashKey = "5294y06JbISpM5x9";
        $HashIV = "v77hoKGq4kWxNNIS";

        $TradeDesc = "交易說明";
        $TotalAmount = 100;
        $ItemName = "商品名稱";
        $ReturnURL = $this->site_url['api'] . '/index/testnotify';
        $CheckMacValue = "";
        $ClientBackURL = $this->site_url['api'] . '/index/testback';
        $OrderResultURL = "";
        $postData = [
            'MerchantID' => '2000132',
            'MerchantTradeNo' => 'AB' . date("His"),
            'MerchantTradeDate' => date("Y/m/d H:i:s"),
            'PaymentType' => 'aio',
            'TotalAmount' => $TotalAmount,
            'TradeDesc' => $TradeDesc,
            'ItemName' => $ItemName,
            'ReturnURL' => $ReturnURL,
            'ChoosePayment' => 'ALL',
            'EncryptType' => 1,
            'ClientBackURL' => $ClientBackURL,
            'OrderResultURL' => $OrderResultURL,
        ];

        ksort($postData);
        $signStr = "";
        foreach ($postData as $k => $v) {
            $signStr .= $k . "=" . $v . "&";
        }
        $signStr = "HashKey=" . $HashKey . "&" . $signStr . "HashIV=" . $HashIV;
        $signStr = strtolower(urlencode($signStr));
        $signStr = toDotNetUrlEncode($signStr);
        $CheckMacValue = strtoupper(hash('sha256', $signStr));

        $szHtml = '<!doctype html>';
        $szHtml .= '<html>';
        $szHtml .= '<head>';
        $szHtml .= '<meta charset="utf-8">';
        $szHtml .= '</head>';
        $szHtml .= '<body>';
        $szHtml .= '<form name="ebpay" id="ebpay" method="post" action="' . $url . '" style="display:none;">';
        $szHtml .= '<input name="MerchantID" value="' . $postData['MerchantID'] . '" type="hidden">';
        $szHtml .= '<input name="MerchantTradeNo" value="' . $postData['MerchantTradeNo'] . '"   type="hidden">';
        $szHtml .= '<input name="MerchantTradeDate" value="' . $postData['MerchantTradeDate'] . '"   type="hidden">';
        $szHtml .= '<input name="PaymentType" value="' . $postData['PaymentType'] . '" type="hidden">';
        $szHtml .= '<input name="TotalAmount" value="' . $postData['TotalAmount'] . '" type="hidden">';
        $szHtml .= '<input name="TradeDesc" value="' . $postData['TradeDesc'] . '" type="hidden">';
        $szHtml .= '<input name="ItemName" value="' . $postData['ItemName'] . '" type="hidden">';
        $szHtml .= '<input name="ReturnURL" value="' . $postData['ReturnURL'] . '" type="hidden">';
        $szHtml .= '<input name="ChoosePayment" value="' . $postData['ChoosePayment'] . '" type="hidden">';
        $szHtml .= '<input name="EncryptType" value="' . $postData['EncryptType'] . '" type="hidden">';
        $szHtml .= '<input name="ClientBackURL" value="' . $postData['ClientBackURL'] . '" type="hidden">';
        $szHtml .= '<input name="OrderResultURL" value="' . $postData['OrderResultURL'] . '" type="hidden">';
        $szHtml .= '<input name="CheckMacValue"  value="' . $CheckMacValue . '" type="hidden">';
        $szHtml .= '</form>';
        $szHtml .= '<script type="text/javascript">';
        $szHtml .= 'document.getElementById("ebpay").submit();';
        $szHtml .= '</script>';
        $szHtml .= '</body>';
        $szHtml .= '</html>';

        return $szHtml;
    }
    
    public function b88()
    {
        loginSetCookie();
        $gameurl = "https://agiv-2.hau888.net";
        // $url = $gameurl . "/login.php";
        // $post = [
        //     'luserid' => '35817',
        //     'lpassword' => 'a123456',
        //     'paction' => 'login-processing',
        //     'remember' => 1
        // ];
        // // $cookie = './cookie.txt';
        // $cookie = __DIR__.'/cookie.txt';

        // Log::notice("模擬登錄 : ".$cookie);
        // //模擬登錄
        // $this->login_post($url, $cookie, $post);
        $cookie = "";

        
        $content = $this->get_content($gameurl.'/today_events_show_list.php?is_start=0&game_category=1', $cookie);
        Log::notice("----------content------------");
        Log::notice($content);
        Log::notice("----------content-end------------");
        echo $content;
    }
    
    private function get_content($url, $cookie) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);//是否显示头信息
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie); //讀取cookie
        
        $rs = curl_exec($ch); //執行cURL抓取頁面內容
        curl_close($ch);
        return $rs;
    }

}
