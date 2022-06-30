<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Log;
use fast\Http;
use think\Exception;
use think\Config;
use think\exception\PDOException;
use think\exception\ValidateException;

class Baccarat extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $key = "4ea100306e5c01e3c4ad3c1a1450f2da";
    protected $merchantNo = "ATA0000000021";


    public function _initialize()
    {
        parent::_initialize();
        Log::init(['type' => 'File', 'log_name' => 'Baccarat']);
        $this->requestLog();
    }

    public function checkout($order = '')
    {
        $mBaccarat = model('Baccarat')->where("ordernum = '".$order."'")->find();
        if($mBaccarat AND $order != ''){
            $mBaccarat->status = 1;
            $mBaccarat->save();
            return "已銷帳";
        }else{
            return "查無訂單";
        }
    }

    public function checkoutall()
    {
        $mBaccarat = model('Baccarat')->where("status = 0")->select();
        if($mBaccarat){
            foreach($mBaccarat as $v){
                $v->status = 1;
                $v->save();
            }
            return "已銷帳";
        }else{
            return "查無訂單";
        }
    }
    
    public function notify()
    {
        $request = $this->request->request();
        $outTradeNo = $this->request->request('outTradeNo', '');
        $tradeNo = $this->request->request('tradeNo', '');
        $merchantNo = $this->request->request('merchantNo', '');
        $tradeStatus = $this->request->request('tradeStatus', '');
        $amount = $this->request->request('amount', '');
        $AcctNo = $this->request->request('AcctNo', '');
        $notifymsg = $this->request->request('msg', '');
        $sign = $this->request->request('sign', '');
        $baccarat_id = 0;
        $msg = '';

        if($merchantNo == $this->merchantNo){
            $signData = [
                'outTradeNo' => $outTradeNo,
                'tradeNo' => $tradeNo,
                'merchantNo' => $merchantNo,
                'tradeStatus' => $tradeStatus,
                'amount' => $amount,
            ];
            if($sign == $this->signStr($signData, $this->key)){
                $mBaccarat = model('Baccarat')->where("ordernum = '".$outTradeNo."' AND tradeNo = '".$tradeNo."' AND debt = ".$amount." AND status = 0 AND take = 1")->find();
                if($mBaccarat){
                    $baccarat_id = $mBaccarat->id;
                    try{
                        $url = "http://full-speed.ddns.net/Query/V1";
                        $postData = [
                            'outTradeNo' => $outTradeNo,
                            'merchantNo' => $merchantNo,
                        ];
                        $this->sign($postData, $key);
    
                        $r = curl_post($url, $postData);
                        $result = json_decode($r, true);
                        if($result){
                            if($result['code'] == 1){
                                $signData = [
                                    'outTradeNo' => $outTradeNo,
                                    'tradeNo' => $tradeNo,
                                    'merchantNo' => $merchantNo,
                                    'tradeStatus' => $tradeStatus,
                                    'amount' => $amount,
                                ];
                                if($result['sign'] == $this->signStr($signData, $this->key)){
                                    if($result['tradeStatus'] == 2){
                                        $mBaccarat->status = 1;
                                        $mBaccarat->repay += $amount;
                                        $mBaccarat->save();
                                        Log::notice('銷帳成功');
                                        $msg = "success";
                                    }else{
                                        Log::notice('查單狀態錯誤');
                                        $msg = "查單狀態錯誤";
                                    }
                                }else{
                                    Log::notice('查單驗簽失敗');
                                    $msg = "查單驗簽失敗";
                                }
                            }else{
                                Log::notice('查單code錯誤');
                                $msg = "查單code錯誤";
                            }
                        }else{
                            Log::notice('查單失敗');
                            $msg = "查單失敗";
                        }
                    }catch (ValidateException $e) {
                        Log::notice("[".__METHOD__."] ValidateException :".$e->getMessage());
                        $msg = $e->getMessage();
                    } catch (PDOException $e) {
                        Log::notice("[".__METHOD__."] PDOException :".$e->getMessage());
                        $msg = $e->getMessage();
                    } catch (Exception $e) {
                        Log::notice("[".__METHOD__."] Exception :".$e->getMessage());
                        $msg = $e->getMessage();
                    }
                }else{
                    Log::notice('查無訂單');
                    $msg = "查無訂單";
                }
            }else{
                Log::notice('驗簽失敗');
                $msg = "銷帳成功";
            }
        }else{
            Log::notice('merchantNo不符');
            $msg = "merchantNo不符";
        }

        $p = [
            'request' => json_encode($request),
            'baccarat_id' => $baccarat_id,
            'outTradeNo' => $outTradeNo,
            'tradeNo' => $tradeNo,
            'merchantNo' => $merchantNo,
            'tradeStatus' => $tradeStatus,
            'amount' => $amount,
            'AcctNo' => $AcctNo,
            'notifymsg' => $notifymsg,
            'msg' => $msg,
            'ip' => $this->request->ip(),
        ];
        model('Baccaratlog')::create($p);
        return $msg;
    }

    // public function notify()
    // {

    //     $request = $this->request->request();
    //     $Ordernum = $this->request->request('Ordernum', '');
    //     $ACTCode = $this->request->request('ACTCode', '');
    //     $bkid = $this->request->request('bkid', '');
    //     $Total = $this->request->request('Total', '');
    //     $Status = $this->request->request('Status', '');
    //     $PoliceReport = $this->request->request('PoliceReport', '');
    //     $baccarat_id = 0;
    //     $msg = '';
    //     if($this->request->ip() == '203.66.45.226'){
    //         if($Status == '0000'){
    //             $mBaccarat = model('Baccarat')->where("ordernum = '".$Ordernum."' AND ACTCode = ".$ACTCode." AND debt = ".$Total." AND status = 0 AND take = 1")->find();
    //             if($mBaccarat){
    //                 $baccarat_id = $mBaccarat->id;
    //                 $mBaccarat->status = 1;
    //                 $mBaccarat->repay += $Total;
    //                 $mBaccarat->save();
    //                 Log::notice('銷帳成功');
    //                 $msg = "銷帳成功";
    //             }else{
    //                 Log::notice('查無訂單');
    //                 $msg = "查無訂單";
    //             }
    //         }else{
    //             Log::notice('狀態不正確,銷帳失敗');
    //             $msg = "狀態不正確,銷帳失敗";
    //         }
    //     }else{
    //         Log::notice('非法ip');
    //         $msg = "非法ip";
    //     }
        
    //     $p = [
    //         'request' => json_encode($request),
    //         'baccarat_id' => $baccarat_id,
    //         'Ordernum' => $Ordernum,
    //         'ACTCode' => $ACTCode,
    //         'bkid' => $bkid,
    //         'Total' => $Total,
    //         'Status' => $Status,
    //         'PoliceReport' => $PoliceReport,
    //         'msg' => $msg,
    //         'ip' => $this->request->ip(),
    //     ];
    //     model('Baccaratlog')::create($p);
    // }

    public function debt()
    {
        $code = $this->request->request('code', '');
        $debt = $this->request->request('debt', '');
        if($code == '' || $debt == ''){
            $this->error('缺少參數');
        }
        if(!is_numeric($debt)){
            $this->error('debt必須是數字');
        }
        if(!$debt > 500){
            $this->error('debt必須大於500');
        }
        $mBaccarat = model('Baccarat')->where("code = '".$code."'")->find();
        if($mBaccarat){
            if($mBaccarat->status == 1){
                Log::notice('更新欠款資訊');
                $ordernum = 'BR'.date('YmdHis');
                $mBaccarat->ordernum = $ordernum;
                $mBaccarat->debt = $debt;
                $mBaccarat->tradeNo = "-";
                $mBaccarat->virtualBankNo = "-";
                $mBaccarat->virtualAccount = "-";
                $mBaccarat->status = 0;
                $mBaccarat->take = 0;

                try{
                    $url = "http://full-speed.ddns.net/Pay/V1";
                    $key = $this->key;
                    $merchantNo = $this->merchantNo;
                    $postData = [
                        'orderNo' => $ordernum,
                        'merchantNo' => $merchantNo,
                        'tradeType' => 1,
                        'amount' => $debt,
                    ];
                    $this->sign($postData, $key);

                    $r = curl_post($url, $postData);
                    $result = json_decode($r, true);
                    if($result){
                        if($result['code'] == 1){
                            $mBaccarat->take = 1;
                            $mBaccarat->tradeNo = $result['tradeNo']??'建單異常';
                            $mBaccarat->virtualBankNo = $result['virtualBankNo']??'建單異常';
                            $mBaccarat->virtualAccount = $result['virtualAccount']??'建單異常';
                            $mBaccarat->save();

                            $checkout_link = $this->site_url['furl']."/index/baccarat/checkout/order/".$mBaccarat->ordernum;
                            $this->success('已更新欠款資訊',['checkout_link' => $checkout_link]);
                        }
                    }
                }catch (ValidateException $e) {
                    Log::notice("[".__METHOD__."] ValidateException :".$e->getMessage());
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Log::notice("[".__METHOD__."] PDOException :".$e->getMessage());
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Log::notice("[".__METHOD__."] Exception :".$e->getMessage());
                    $this->error($e->getMessage());
                }
                
                $mBaccarat->take = 0;
                $mBaccarat->save();
                Log::notice("[".__METHOD__."] 建單失敗:".$result['msg']);
                $this->error("建單失敗");
            }else{
                $checkout_link = $this->site_url['furl']."/index/baccarat/checkout/order/".$mBaccarat->ordernum;
                $this->error('尚未結清',['checkout_link' => $checkout_link]);
            }
        }else{
            $this->error('代碼無效');
        }
    }

    public function check()
    {
        $code = $this->request->request('code', '');
        if($code == ''){
            $this->error('缺少參數');
        }
        $mBaccarat = model('Baccarat')->where("code = '".$code."'")->find();
        if($mBaccarat){
            if($mBaccarat->status == 1){
                $this->success('已結清帳號');
            }else{
                $checkout_link = $this->site_url['furl']."/index/baccarat/checkout/order/".$mBaccarat->ordernum;
                $this->error('尚未結清',['checkout_link' => $checkout_link]);
            }
        }else{
            $this->error('代碼無效');
        }
    }
    

}
