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
        // $this->getRichMenu();
    }
    
    // public function index()
    // {
    //     $url = "https://sportgod.cc/api/baccarat/debt";
    //     $postData = [
    //         'code' => 'TESTCODE',
    //         'debt' => '15000',
    //     ];
    //     $r = curl_post($url, $postData);
    //     Log::notice($r);
    //     // $this->success('請求成功');
    // }
    
    public function notify()
    {
        Log::notice($this->request->request());
        $Ordernum = $this->request->request('Ordernum', '');
        $ACID = $this->request->request('ACID', '');
        $Total = $this->request->request('Total', '');
        $Bank1 = $this->request->request('Bank1', '');
        $QRCode = $this->request->request('QRCode', '');

        if($ACID != ''){
            $mBaccarat = model('Baccarat')->where("ordernum = '".$Ordernum."' AND debt = ".$Total)->find();
            if($mBaccarat){
                Log::notice('產生欠款');
                $mBaccarat->ACTCode = $ACID;
                $mBaccarat->Bank1 = $Bank1;
                $mBaccarat->QRCode = $QRCode;
                $mBaccarat->save();
                $this->success('已產生欠款',['debt' => $Total, 'ACID' => $ACID, 'Bank1' => $Bank1, 'QRCode' => $QRCode]);
            }else{
                Log::notice('查無訂單');
                $this->error('查無訂單');
            }
            Log::notice('產生欠款');
            $params = [
                'user_id' => $mUser->id,
                'pred_id' => $mUsertopred->pred_id
            ];
            model('Baccarat')::create($params);
            $this->success('請求成功');
        }

        
        if($this->request->ip() != '203.66.45.226'){
            Log::notice('非法ip');
            $this->error('系統異常');
        }
        
        $this->success('請求成功');
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
                $checkout_link = $this->site_url['furl']."/baccarat/checkout/order/".$mBaccarat->ordernum;
                $this->success('已更新欠款資訊',['checkout_link' => $checkout_link]);
            }else{
                $checkout_link = $this->site_url['furl']."/baccarat/checkout/order/".$mBaccarat->ordernum;
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
                $checkout_link = $this->site_url['furl']."/baccarat/checkout/order/".$mBaccarat->ordernum;
                $this->error('尚未結清',['checkout_link' => $checkout_link]);
            }
        }else{
            $this->error('代碼無效');
        }
    }
    

}
