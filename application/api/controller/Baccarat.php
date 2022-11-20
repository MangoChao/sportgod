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

    public function _initialize()
    {
        parent::_initialize();
        Log::init(['type' => 'File', 'log_name' => 'Baccarat']);
        $this->requestLog();
    }

    public function checkout()
    {
        $mBaccarat = model('Baccarat')->where("code = 'TESTAPP'")->find();
        if($mBaccarat){
            $mBaccarat->order_status = 1;
            $mBaccarat->save();
            return "已銷帳";
        }else{
            return "查無訂單";
        }
    }

    public function unlocked()
    {
        $mBaccarat = model('Baccarat')->where("code = 'TESTAPP'")->find();
        if($mBaccarat){
            $mBaccarat->locked = 0;
            $mBaccarat->act = 0;
            $mBaccarat->save();
            return "已解鎖 act = 0";
        }else{
            return "查無";
        }
    }

    public function unsetuid()
    {
        $mBaccarat = model('Baccarat')->where("code = 'TESTAPP'")->find();
        if($mBaccarat){
            $mBaccarat->uid = null;
            $mBaccarat->act = 0;
            $mBaccarat->save();
            return "已重置 uid = null ,act = 0";
        }else{
            return "查無";
        }
    }

    // public function checkoutall2()
    // {
    //     $mBaccarat = model('Baccarat')->where("status = 0")->select();
    //     if($mBaccarat){
    //         foreach($mBaccarat as $v){
    //             $v->status = 1;
    //             $v->save();
    //         }
    //         return "已銷帳";
    //     }else{
    //         return "查無訂單";
    //     }
    // }

    public function get_default_url()
    {
        $mC = model('Config')->where("`name` = 'baccarat_url'")->find();
        if($mC){
            return $mC->value;
        }else{
            return "";
        }
    }

    public function confirm()
    {
        $id = $this->request->request('id', '');
        if($id == ''){
            $this->error('查無帳號');
        }
        $mBaccarat = model('Baccarat')->where("id = ".$id)->find();
        if(!$mBaccarat){
            $this->error('查無帳號');
        }
        $phone = $this->request->request('phone', '');
        $img = $this->request->request('img', '');
        if($phone == ''){
            $this->error('[連絡電話]不能為空');
        }
        if($img == ''){
            $this->error('[匯款帳號]不能為空');
        }

        $mBaccarat->phone = $phone;
        $mBaccarat->img = $img;
        $mBaccarat->confirm = 1;
        $mBaccarat->save();
        $this->success('實名認證已送出');

    }
    
    public function notify()
    {
        $request = $this->request->request();
        $m = $this->request->request('m', '');
        $class = $this->request->request('class', '');
        $order = $this->request->request('order', '');
        $shid = $this->request->request('shid', '');
        $md5key = $this->request->request('md5key', '');
        $status = $this->request->request('status', '');
        $msg = '';

        if($shid == $this->payapi_shid){
            $md5keyCheck = md5(md5($order.$m.$shid).$this->payapi_key);
            if($md5keyCheck == $md5key){
                $mBaccaratorder = model('Baccaratorder')->where("order_no = '".$order."' AND amount = ".$m." AND status = 0")->find();
                if($mBaccaratorder){
                    try{
                        $mBaccaratorder->request = json_encode($request, JSON_UNESCAPED_UNICODE);
                        $mBaccaratorder->save();
                        $url = $this->payapi_queryurl;
                        $postData = [
                            'userid' => $this->payapi_mid,
                            'username' => $this->payapi_shid,
                            'orderno' => $order,
                            'sign' => md5($this->payapi_key),
                        ];
                        $r = curl_post($url, $postData);
                        $result = json_decode($r, true);
                        if($result){
                            if($result['status'] == 3){
                                $mBaccaratorder->status = 1;
                                $mBaccaratorder->save();
                                $mBaccarat = model('Baccarat')->where("id = '".$mBaccaratorder->baccarat_id."'")->find();
                                if($mBaccarat){
                                    $mBaccarat->order_status = 1;
                                    $mBaccarat->repay += $m;
                                    $mBaccarat->save();
                                    Log::notice('銷帳成功');
                                }else{
                                    Log::notice('查無對應帳單的代碼');
                                }
                                $msg = "success";
                            }else{
                                Log::notice('查單狀態錯誤');
                                $msg = "查單狀態錯誤";
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
                Log::notice('md5key不符');
                $msg = "md5key不符";
            }
        }else{
            Log::notice('shid不符');
            $msg = "shid不符";
        }
        return $msg;
    }

    public function notify2()
    {
        $post = $this->request->post();
        if (isset($post['Status']) && $post['MerchantID'] == $this->newebpay_MerchantID) {
            $TradeInfo = $post['TradeInfo'];
            $TradeSha = $post['TradeSha'];
            $CheckTradeSha = "HashKey=".$this->newebpay_HashKey."&".$TradeInfo."&HashIV=".$this->newebpay_HashIV;
            $CheckTradeSha = strtoupper(hash("sha256", $CheckTradeSha));
            if($CheckTradeSha == $TradeSha){

                $TradeInfo = create_aes_decrypt($TradeInfo, $this->newebpay_HashKey, $this->newebpay_HashIV);
                Log::notice("[".__METHOD__."] TradeInfo :".$TradeInfo);
                $TradeInfo = json_decode($TradeInfo,true);
                $Result = $TradeInfo['Result'];

                $order = model('Baccaratorder')->get(['order_no' => $Result['MerchantOrderNo'],'status' => 0]);
                Log::notice("[".__METHOD__."] order :".json_encode($order));
                if($order){
                    $r = false;
                    try {

                        if($Result['MerchantID'] != $this->newebpay_MerchantID){
                            Log::notice("[".__METHOD__."] MerchantID 不符 :".$Result['MerchantID']);
                            $this->error('MerchantID 不符');
                        }
                        if($Result['Amt'] != $order['amount']){
                            Log::notice("[".__METHOD__."] Amt 不符 :".$Result['Amt']);
                            $this->error('Amt 不符');
                        }

                        $params = [
                            'request' => json_encode($post, JSON_UNESCAPED_UNICODE)??"",
                            'notify_msg' => $TradeInfo['Message']??"",
                            'trade_no' => $Result['TradeNo']??"",
                            'payment_type' => $Result['PaymentType']??"",
                            'pay_time' => strtotime($Result['PayTime'])??"",

                            //WEBATM、ATM 繳費回傳參數
                            'pay_bank_code' => $Result['PayBankCode']??"",
                            'payer_account_5_code' => $Result['PayerAccount5Code']??"",

                            //超商代碼繳費回傳參數
                            'code_no' => $Result['CodeNo']??"",
                            'store_type' => $Result['StoreType']??"",
                            'store_ID' => $Result['StoreID']??"",

                            //超商條碼繳費回傳參數
                            'barcode_1' => $Result['Barcode_1']??"",
                            'barcode_2' => $Result['Barcode_2']??"",
                            'barcode_3' => $Result['Barcode_3']??"",
                            'pay_store' => $Result['PayStore']??""
                        ];
                        if($TradeInfo['Status'] == 'SUCCESS'){
                            $params['status'] = 1;
                        }else{
                            $params['status'] = 3;
                        }
                        Log::notice("[".__METHOD__."] params:". json_encode($params));

                        $r = $order->allowField(true)->save($params);
                    } catch (ValidateException $e) {
                        Log::notice("[".__METHOD__."] ValidateException :".$e->getMessage());
                        $this->error($e->getMessage());
                    } catch (PDOException $e) {
                        Log::notice("[".__METHOD__."] PDOException :".$e->getMessage());
                        $this->error($e->getMessage());
                    } catch (Exception $e) {
                        Log::notice("[".__METHOD__."] Exception :".$e->getMessage());
                        $this->error($e->getMessage());
                    }
                    if ($r !== false) {
                        if($order->status == 1){
                            $mBaccarat = model('Baccarat')->where("id = '".$order->baccarat_id."'")->find();
                            if($mBaccarat){
                                $mBaccarat->order_status = 1;
                                $mBaccarat->repay += $order->amount;
                                $mBaccarat->save();
                                Log::notice("[".__METHOD__."]銷帳成功");
                            }else{
                                Log::notice("查無對應帳單的代碼");
                            }
                        }
                        Log::notice("[".__METHOD__."][".$Result["MerchantOrderNo"]."] 回調成功,已改變訂單狀態");
                    } else {
                        Log::notice("[".__METHOD__."][".$Result["MerchantOrderNo"]."] 回調失敗,未改變訂單狀態");
                    }
                }else{
                    Log::notice("[".__METHOD__."] 訂單不存在或是狀態不符 : order_number:".$Result['MerchantOrderNo']);
                }
            }else{
                Log::notice("[".__METHOD__."] 驗證錯誤 : CheckTradeSha:".$CheckTradeSha." | TradeSha:".$TradeSha);
            }
        }else{
            Log::notice("[".__METHOD__."] 參數錯誤");
        }
    }
    
    public function reOrder($code = '')
    {
        if($code == ''){
            Log::notice("[".__METHOD__."] 缺少參數");
            return false;
        }
        $mBaccaratorder = model('Baccarat')->alias('b')
        ->join("baccarat_order bo","bo.id = b.baccarat_order_id","LEFT")
        ->field("bo.*")
        ->where("b.code = '".$code."'")->find();
        if($mBaccaratorder){
            try{
                $url = $this->payapi_payurl;
                $shid = $this->payapi_shid;
                $key = $this->payapi_key;
                $amount = $mBaccaratorder->amount;

                $orderid = 'BR'.date('YmdHis');
                $md5key = md5(md5($shid.$orderid.$amount).$key);
                $postData = [
                    'shid' => $shid,
                    'key' => $md5key,
                    'orderid' => $orderid,
                    'amount' => $amount,
                    'pay' => 'yl',
                    'url' => $this->site_url['api'].'/baccarat/notify',
                    'fkrname' => "",
                ];
                $data_string = json_encode($postData);
                $options = [
                    CURLOPT_HTTPHEADER => ['Content-Type:application/json', 'Content-Length: '.strlen($data_string)]
                ];
                $r = curl_post($url, $data_string, $options);
                $result = json_decode($r, true);
                if($result){
                    if($result['status'] == "success"){
                        
                        $p = [
                            'baccarat_id' => $mBaccaratorder->baccarat_id,
                            'result' => json_encode($result),
                            'msg' => $result['msg']??"",
                            'order_no' => $orderid,
                            'trans_order_no' => $result['trans_order_no']??"",
                            'amount' => $result['amount']??null,
                            'create_time' => $result['create_time']??"",
                            'end_time' => $result['end_time']??"",
                            'create_time_strtotime' => $result['create_time']?strtotime($result['create_time']):null,
                            'end_time_strtotime' => $result['end_time']?strtotime($result['end_time']):null,
                            'name' => $result['name']??"",
                            'bank_card_number' => $result['bank_card_number']??"",
                            'bank_name' => $result['bank_name']??"",
                            'bank_zhihang' => $result['bank_zhihang']??"",
                            'checkout_url' => $result['url']??"",
                            'ip' => $mBaccaratorder->ip,
                        ];
                        $mBaccaratorder = model('Baccaratorder')::create($p);
                        
                        $mBaccarat = model('Baccarat')->get($mBaccaratorder->baccarat_id);
                        if($mBaccarat){
                            $mBaccarat->order_status = 0;
                            $mBaccarat->baccarat_order_id = $mBaccaratorder->id;
                            $mBaccarat->save();
                            return true;
                        }else{
                            return false;
                        }
                    }else{
                        Log::notice("[".__METHOD__."] 建單失敗");
                        Log::notice($result);
                        return false;
                    }
                }else{
                    Log::notice("[".__METHOD__."] 回傳異常");
                    Log::notice($r);
                    return false;
                }
            }catch (ValidateException $e) {
                Log::notice("[".__METHOD__."] ValidateException :".$e->getMessage());
                return false;
            } catch (PDOException $e) {
                Log::notice("[".__METHOD__."] PDOException :".$e->getMessage());
                return false;
            } catch (Exception $e) {
                Log::notice("[".__METHOD__."] Exception :".$e->getMessage());
                return false;
            }
        }else{
            Log::notice("[".__METHOD__."] 查無用戶");
            return false;
        }
    }

    public function debt()
    {
        Log::notice("[".__METHOD__."] 產生欠款");
        $code = $this->request->request('code', '');
        $debt = $this->request->request('debt', 0);
        if($code == '' || $debt == 0){
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
            if($mBaccarat->order_status == 1){
                Log::notice("更新欠款資訊");
                $mBaccarat->debt = $debt;

                try{
                    $url = $this->payapi_payurl;
                    $shid = $this->payapi_shid;
                    $key = $this->payapi_key;
                    $amount = $debt;

                    $orderid = 'BR'.date('YmdHis');
                    $md5key = md5(md5($shid.$orderid.$amount).$key);
                    $postData = [
                        'shid' => $shid,
                        'key' => $md5key,
                        'orderid' => $orderid,
                        'amount' => $amount,
                        'pay' => 'yl',
                        'url' => $this->site_url['api'].'/baccarat/notify',
                        'fkrname' => "",
                    ];
                    $data_string = json_encode($postData);
                    $options = [
                        CURLOPT_HTTPHEADER => ['Content-Type:application/json', 'Content-Length: '.strlen($data_string)]
                    ];
                    $r = curl_post($url, $data_string, $options);
                    $result = json_decode($r, true);
                    if($result){
                        if($result['status'] == "success"){
                            
                            $p = [
                                'baccarat_id' => $mBaccarat->id,
                                'result' => json_encode($result),
                                'msg' => $result['msg']??"",
                                'order_no' => $orderid,
                                'trans_order_no' => $result['trans_order_no']??"",
                                'amount' => $result['amount']??null,
                                'create_time' => $result['create_time']??"",
                                'end_time' => $result['end_time']??"",
                                'create_time_strtotime' => $result['create_time']?strtotime($result['create_time']):null,
                                'end_time_strtotime' => $result['end_time']?strtotime($result['end_time']):null,
                                'name' => $result['name']??"",
                                'bank_card_number' => $result['bank_card_number']??"",
                                'bank_name' => $result['bank_name']??"",
                                'bank_zhihang' => $result['bank_zhihang']??"",
                                'checkout_url' => $result['url']??"",
                                'ip' => $this->request->ip(),
                                'trade_type' => 1
                            ];
                            $mBaccaratorder = model('Baccaratorder')::create($p);

                            $mBaccarat->order_status = 0;
                            $mBaccarat->baccarat_order_id = $mBaccaratorder->id;
                            $mBaccarat->save();

                            $checkout_link = $this->site_url['furl']."/index/baccarat/checkout/code/".$code;
                            $this->success('已更新欠款資訊',['checkout_link' => $checkout_link]);
                        }else{
                            Log::notice("[".__METHOD__."] 建單失敗");
                            Log::notice($result);
                        }
                    }else{
                        Log::notice("[".__METHOD__."] 回傳異常");
                        Log::notice($r);
                    }
                    
                    $p = [
                        'baccarat_id' => $mBaccarat->id,
                        'order_no' => $orderid,
                        'amount' => $amount,
                        'status' => 0,
                        'ip' => $this->request->ip(),
                        'trade_type' => 2
                    ];
                    $mBaccaratorder = model('Baccaratorder')::create($p);

                    $mBaccarat->order_status = 0;
                    $mBaccarat->baccarat_order_id = $mBaccaratorder->id;
                    $mBaccarat->save();

                    $checkout_link = $this->site_url['furl']."/index/baccarat/checkout/code/".$code;
                    $this->success('已更新欠款資訊',['checkout_link' => $checkout_link]);

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
                $checkout_link = $this->site_url['furl']."/index/baccarat/checkout/code/".$code;
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
            if($mBaccarat->order_status == 1){
                if($mBaccarat->status == 0){
                    Log::notice("[".__METHOD__."] 代號已被停用");
                    $this->error('代號已被鎖定');
                }elseif($mBaccarat->locked == 1){
                    Log::notice("[".__METHOD__."] 代號已被鎖定");
                    $this->error('代號已被鎖定');
                }else{
                    Log::notice("[".__METHOD__."] 已結清帳號");
                    $this->success('已結清帳號');
                }
            }else{
                Log::notice("[".__METHOD__."] 尚未結清,回傳帳單連結");
                $checkout_link = $this->site_url['furl']."/index/baccarat/checkout/code/".$code;
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
                Log::notice("[".__METHOD__."] getPayload:".json_encode($getPayload, JSON_UNESCAPED_UNICODE));
                $code = $getPayload['code']??"";
                $exp = $getPayload['exp']??0;
                $exps = $getPayload['exps']??60;
                $uid = $getPayload['uid']??"";
                $act = $getPayload['act']??0;
                $mBaccarat = model('Baccarat')->where("code = '".$code."'")->find();
                if($mBaccarat AND $code != "" AND $uid != ""){
                    if($mBaccarat->status == 0){
                        Log::notice("[".__METHOD__."] 代號已被停用".$code);
                        $msg = "代號已被停用";
                        $response_code = 0;
                    }elseif($mBaccarat->locked == 1){
                        Log::notice("[".__METHOD__."] 代號已被鎖定".$code);
                        $msg = "代號已被鎖定";
                        $response_code = 0;
                    }else{
                        if(!$mBaccarat->uid){
                            $mBaccarat->uid = $uid;
                        }
                        if($mBaccarat->uid != $uid){
                            // if($mBaccarat->uid != $uid AND $mBaccarat->act == 1){
                            Log::notice("[".__METHOD__."] 識別碼異常,鎖定代號".$code);
                            $mBaccarat->locked = 1; //鎖定
                            $msg = "識別碼異常,鎖定代號";
                            $response_code = 0;
                        }else{
                            // if($mBaccarat->act == 0){
                            //     $mBaccarat->uid = $uid;
                            // }
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

}
