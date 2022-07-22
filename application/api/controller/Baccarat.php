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
        $mBaccarat = model('Baccarat')->where("locked = 1")->select();
        if($mBaccarat){
            foreach($mBaccarat as $v){
                $v->locked = 0;
                $v->act = 0;
                $v->save();
            }
            return "已解鎖";
        }else{
            return "查無鎖定";
        }
    }

    public function checkoutall2()
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
                        $postData = $this->sign($postData, $this->key);
    
                        $r = curl_post($url, $postData);
                        $result = json_decode($r, true);
                        if($result){
                            if($result['code'] == 1){
                                $signData = [
                                    'outTradeNo' => $result['outTradeNo']??'',
                                    'tradeNo' => $result['tradeNo']??'',
                                    'tradeStatus' => $result['tradeStatus']??'',
                                    'amount' => $result['amount']??'',
                                    'payTime' => $result['payTime']??'',
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
                                    Log::notice('對方:'.$result['sign']);
                                    Log::notice('我方:'.$this->signStr($signData, $this->key));
                                    Log::notice($signData);
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

    public function debt()
    {
        Log::notice("[".__METHOD__."] 產生欠款");
        $code = $this->request->request('code', '');
        $debt = $this->request->request('debt', '');
        if($code == '' || $debt == ''){
            Log::notice("[".__METHOD__."] 缺少參數");
            $this->error('缺少參數');
        }
        if(!is_numeric($debt)){
            Log::notice("[".__METHOD__."] debt必須是數字");
            $this->error('debt必須是數字');
        }
        if(!$debt > 500){
            Log::notice("[".__METHOD__."] debt必須大於500");
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

                try{
                    
                    //--
                    $mBaccarat->status = 0;
                    $mBaccarat->take = 1;
                    $mBaccarat->tradeNo = 'tradeNo'.time();
                    $mBaccarat->virtualBankNo = 'virtualBankNo'.time();
                    $mBaccarat->virtualAccount = 'virtualAccount'.time();
                    $mBaccarat->save();

                    $checkout_link = $this->site_url['furl']."/index/baccarat/checkout/order/".$mBaccarat->ordernum;
                    Log::notice("[".__METHOD__."] 已更新欠款資訊,回傳連結");
                    $this->success('已更新欠款資訊',['checkout_link' => $checkout_link]);
                    //--

                    $url = "http://full-speed.ddns.net/Pay/V1";
                    $merchantNo = $this->merchantNo;
                    $postData = [
                        'orderNo' => $ordernum,
                        'merchantNo' => $merchantNo,
                        'tradeType' => 1,
                        'amount' => $debt,
                    ];
                    $postData = $this->sign($postData, $this->key);

                    $r = curl_post($url, $postData);
                    $result = json_decode($r, true);
                    if($result){
                        if($result['code'] == 1){
                            $mBaccarat->status = 0;
                            $mBaccarat->take = 1;
                            $mBaccarat->tradeNo = $result['tradeNo']??'建單異常';
                            $mBaccarat->virtualBankNo = $result['virtualBankNo']??'建單異常';
                            $mBaccarat->virtualAccount = $result['virtualAccount']??'建單異常';
                            $mBaccarat->save();

                            $checkout_link = $this->site_url['furl']."/index/baccarat/checkout/order/".$mBaccarat->ordernum;
                            $this->success('已更新欠款資訊',['checkout_link' => $checkout_link]);
                        }
                    }else{
                        Log::notice("[".__METHOD__."] 回傳異常");
                        Log::notice($r);
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
                Log::notice("[".__METHOD__."] 建單失敗:".$result['msg']);
                $this->error("建單失敗");
            }else{
                Log::notice("[".__METHOD__."] 尚未結清,回傳連結");
                $checkout_link = $this->site_url['furl']."/index/baccarat/checkout/order/".$mBaccarat->ordernum;
                $this->error('尚未結清',['checkout_link' => $checkout_link]);
            }
        }else{
            Log::notice("[".__METHOD__."] 代碼無效");
            $this->error('代碼無效');
        }
    }

    public function check()
    {
        Log::notice("[".__METHOD__."] 檢查");
        $code = $this->request->request('code', '');
        if($code == ''){
            Log::notice("[".__METHOD__."] 缺少參數");
            $this->error('缺少參數');
        }
        $mBaccarat = model('Baccarat')->where("code = '".$code."'")->find();
        if($mBaccarat){
            if($mBaccarat->status == 1){
                if($mBaccarat->locked == 1){
                    Log::notice("[".__METHOD__."] 代號已被鎖定");
                    $this->error('代號已被鎖定');
                }else{
                    Log::notice("[".__METHOD__."] 已結清帳號");
                    $this->success('已結清帳號');
                }
            }else{
                Log::notice("[".__METHOD__."] 尚未結清,回傳帳單連結");
                $checkout_link = $this->site_url['furl']."/index/baccarat/checkout/order/".$mBaccarat->ordernum;
                $this->error('尚未結清',['checkout_link' => $checkout_link]);
            }
        }else{
            Log::notice("[".__METHOD__."] 代碼無效");
            $this->error('代碼無效');
        }
    }
    

    public function check2()
    {
        Log::notice("[".__METHOD__."] 檢查2");
        try{
            $token = $this->request->request('d', '');
            $jwt = new \app\common\library\Jwt;
            $getPayload = $jwt->verifyToken($token);
            $msg = "";
            $code = "";
            $exps = 60;
            $iat = time();
            if($getPayload){
                $code = $getPayload['code']??"";
                $exp = $getPayload['exp']??0;
                $exps = $getPayload['exps']??60;
                $uid = $getPayload['uid']??"";
                $act = $getPayload['act']??0;
                $mBaccarat = model('Baccarat')->where("code = '".$code."'")->find();
                if($mBaccarat AND $code != "" AND $uid != ""){
                    if($mBaccarat->locked == 1){
                        Log::notice("[".__METHOD__."] 代號已被鎖定".$code);
                        $msg = "代號已被鎖定";
                        $response_code = 0;
                    }else{
                        if(!$mBaccarat->uid){
                            $mBaccarat->uid = $uid;
                        }
                        if($mBaccarat->uid != $uid){
                            Log::notice("[".__METHOD__."] 識別碼異常,鎖定代號".$code);
                            $mBaccarat->locked = 1; //鎖定
                            $msg = "識別碼異常,鎖定代號";
                            $response_code = 0;
                        }else{
                            if($mBaccarat->act == 1 AND ($mBaccarat->last_act_date + 600) < $iat){
                                //超時
                                Log::notice("[".__METHOD__."] 檢查逾時,鎖定代號".$code);
                                $mBaccarat->locked = 1; //鎖定
                                $msg = "檢查逾時,鎖定代號";
                                $response_code = 0;
                            }else{
                                Log::notice("[".__METHOD__."] 檢查通過,已更新檢查時間".$code);
                                $mBaccarat->act = $act;
                                $mBaccarat->last_act_date = $iat;
                                $msg = "檢查通過,已更新檢查時間";
                                $response_code = 1;
                            }
                        }
                        $mBaccarat->save();
                    }
                }else{
                    Log::notice("[".__METHOD__."] 代號無效".$code);
                    $msg = "代號無效";
                    $response_code = 0;
                }
            }else{
                Log::notice("[".__METHOD__."] 解碼異常");
                $msg = "解碼異常";
                $response_code = 0;
            }

            $exp = $iat + $exps;
            $rPayload = [
                'iat' => $iat,
                'exp' => $exp,
                'exps' => $exps,
                'code' => $code,
                'response_code' => $response_code,
                'msg' => $msg,
            ];
            $rToken = $jwt->getToken($rPayload);
            Log::notice("[".__METHOD__."] 正常回傳 d=".$rToken);
            $this->success('正常回傳', ['d' => $rToken]);
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

}
