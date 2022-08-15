<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Log;
use fast\Http;
use think\Exception;
use think\Config;
use think\exception\PDOException;
use think\exception\ValidateException;

class Notify extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        Log::init(['type' => 'File', 'log_name' => 'Notify']);
        $this->requestLog();
    }
    
    public function orderpoint()
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
                $mOrderpoint = model('Orderpoint')->where("order_no = '".$order."' AND amount = ".$m." AND status = 0")->find();
                if($mOrderpoint){
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
                                $mOrderpoint->status = 1;
                                $mOrderpoint->save();
                                $memo = "儲值點數";
                                $this->changePoint($mOrderpoint->user_id, $mOrderpoint->point, $memo);
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
}
