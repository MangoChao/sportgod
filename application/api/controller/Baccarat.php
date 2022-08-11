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

    // public function checkout($order = '')
    // {
    //     $mBaccarat = model('Baccarat')->where("ordernum = '".$order."'")->find();
    //     if($mBaccarat AND $order != ''){
    //         $mBaccarat->status = 1;
    //         $mBaccarat->save();
    //         return "已銷帳";
    //     }else{
    //         return "查無訂單";
    //     }
    // }

    // public function checkoutall()
    // {
    //     $mBaccarat = model('Baccarat')->where("locked = 1")->select();
    //     if($mBaccarat){
    //         foreach($mBaccarat as $v){
    //             $v->locked = 0;
    //             $v->act = 0;
    //             $v->save();
    //         }
    //         return "已解鎖";
    //     }else{
    //         return "查無鎖定";
    //     }
    // }

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
                                'order_no' => $result['order_no']??"",
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
                            ];
                            $mBaccaratorder = model('Baccaratorder')::create($p);

                            $mBaccarat->order_status = 0;
                            $mBaccarat->baccarat_order_id = $mBaccaratorder->id;
                            $mBaccarat->save();

                            $checkout_link = $this->site_url['furl']."/index/baccarat/checkout/code/".$code;
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
                $code = $getPayload['code']??"";
                $exp = $getPayload['exp']??0;
                $exps = $getPayload['exps']??60;
                $uid = $getPayload['uid']??"";
                $act = $getPayload['act']??0;
                $mBaccarat = model('Baccarat')->where("code = '".$code."'")->find();
                if($mBaccarat AND $code != "" AND $uid != ""){
                    if($mBaccarat->status == 0){
                        Log::notice("[".__METHOD__."] 代號已被停用".$code);
                        $msg = "代號已被鎖定";
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

}
