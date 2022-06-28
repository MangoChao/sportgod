<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Log;
use fast\Http;
use think\Exception;
use think\Config;

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
    
    public function notify()
    {

        $request = $this->request->request();
        $Ordernum = $this->request->request('Ordernum', '');
        $ACTCode = $this->request->request('ACTCode', '');
        $bkid = $this->request->request('bkid', '');
        $Total = $this->request->request('Total', '');
        $Status = $this->request->request('Status', '');
        $PoliceReport = $this->request->request('PoliceReport', '');
        $baccarat_id = 0;
        $msg = '';
        if($this->request->ip() == '203.66.45.226'){
            if($Status == '0000'){
                $mBaccarat = model('Baccarat')->where("ordernum = '".$Ordernum."' AND ACTCode = ".$ACTCode." AND debt = ".$Total." AND status = 0 AND take = 1")->find();
                if($mBaccarat){
                    $baccarat_id = $mBaccarat->id;
                    $mBaccarat->status = 1;
                    $mBaccarat->repay += $Total;
                    $mBaccarat->save();
                    Log::notice('銷帳成功');
                    $msg = "銷帳成功";
                }else{
                    Log::notice('查無訂單');
                    $msg = "查無訂單";
                }
            }else{
                Log::notice('狀態不正確,銷帳失敗');
                $msg = "狀態不正確,銷帳失敗";
            }
        }else{
            Log::notice('非法ip');
            $msg = "非法ip";
        }
        
        $p = [
            'request' => json_encode($request),
            'baccarat_id' => $baccarat_id,
            'Ordernum' => $Ordernum,
            'ACTCode' => $ACTCode,
            'bkid' => $bkid,
            'Total' => $Total,
            'Status' => $Status,
            'PoliceReport' => $PoliceReport,
            'msg' => $msg,
            'ip' => $this->request->ip(),
        ];
        model('Baccaratlog')::create($p);
    }

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
        if(!$debt > 0){
            $this->error('debt必須大於0');
        }
        $mBaccarat = model('Baccarat')->where("code = '".$code."'")->find();
        if($mBaccarat){
            if($mBaccarat->status == 1){
                Log::notice('更新欠款資訊');
                $ordernum = 'BR'.date('YmdHis');
                $mBaccarat->ordernum = $ordernum;
                $mBaccarat->debt = $debt;
                $mBaccarat->status = 0;
                $mBaccarat->take = 0;
                $mBaccarat->save();
                $checkout_link = $this->site_url['furl']."/index/baccarat/checkout/order/".$mBaccarat->ordernum;
                $this->success('已更新欠款資訊',['checkout_link' => $checkout_link]);
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
