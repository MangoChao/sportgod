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
    
    public function index()
    {
        $this->success('請求成功');
    }
    
    public function notify()
    {
        Log::notice($this->request->request());
        if($this->request->ip() != '203.66.45.226'){
            Log::notice('非法ip');
            $this->error('系統異常');
        }
        $Ordernum = $this->request->request('Ordernum', '');
        $ACID = $this->request->request('ACID', '');
        $Total = $this->request->request('Total', '');
        $Bank1 = $this->request->request('Bank1', '');
        $QRCode = $this->request->request('QRCode', '');

        if($ACID != ''){
            Log::notice('產生欠款');
            $params = [
                'user_id' => $mUser->id,
                'pred_id' => $mUsertopred->pred_id
            ];
            model('Usertopred')::create($params);
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
        $mBaccarat = model('Baccarat')->where("code = ".$code)->find();
        if($mBaccarat){
            if($mBaccarat->status == 1){
                $ordernum = 'BR'.date('YmdHis');
                $url = "http://pay.meixin.tw/api/getway02/VracRequest.ashx";
                $postData = [
                    'Merchent' => 'AA',
                    'OrderID' => $ordernum,
                    'Total' => $debt,
                    'Product' => '服務',
                    'Name' => '葉加勒',
                    'MSG' => '',
                    'ReAUrl' => $this->site_url['api']."/baccarat/notify",
                    'ReBUrl' => $this->site_url['api']."/baccarat/notify",
                ];
                $r = curl_post($url, $postData);
                Log::notice($r);
                $this->success('已產生欠款',['ordernum' => $ordernum, 'debt' => $debt, 'ACTCode' => '']);
            }else{
                $this->error('尚未結清',['ordernum' => $mBaccarat->ordernum, 'debt' => $mBaccarat->debt, 'ACTCode' => $mBaccarat->ACTCode]);
            }
        }else{
            $this->error('代碼無效');
        }
    }

    public function check()
    {
        $code = $this->request->request('code', '');
        $mBaccarat = model('Baccarat')->where("code = ".$code)->find();
        if($mBaccarat){
            if($mBaccarat->status == 1){
                $this->success('已結清帳號');
            }else{
                $this->error('尚未結清',['ordernum' => $mBaccarat->ordernum, 'debt' => $mBaccarat->debt, 'ACTCode' => $mBaccarat->ACTCode]);
            }
        }else{
            $this->error('代碼無效');
        }
    }
    

}
