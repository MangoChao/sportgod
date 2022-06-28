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

    
    public function creatdebt()
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
                Log::notice('確認產生欠款');
                $mBaccarat->ACTCode = $ACID;
                $mBaccarat->Bank1 = $Bank1;
                $mBaccarat->QRCode = $QRCode;
                $mBaccarat->status = 0;
                $mBaccarat->save();
                $this->success('已產生欠款',['debt' => $Total, 'ACID' => $ACID, 'Bank1' => $Bank1,  'Bank2' => '',  'Bank3' => '', 'QRCode' => $QRCode]);
            }else{
                Log::notice('查無訂單');
                $this->error('查無訂單');
            }
        }else{
            $this->error('系統異常');
        }
        // $this->error('尚未結清',['ordernum' => $mBaccarat->ordernum, 'debt' => $mBaccarat->debt, 'ACTCode' => $mBaccarat->ACTCode]);
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
                $this->error('尚未結清',['ordernum' => $mBaccarat->ordernum, 'debt' => $mBaccarat->debt, 'ACTCode' => $mBaccarat->ACTCode]);
            }
        }else{
            $this->error('代碼無效');
        }
    }
    

}
