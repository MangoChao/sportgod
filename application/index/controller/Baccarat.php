<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Log;

class Baccarat extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = 'baccarat';

    public function _initialize()
    {
        parent::_initialize();
        Log::init(['type' => 'File', 'log_name' => 'Baccarat']);
    }

    public function checkout($order = '')
    {
        Log::notice($this->request->request());
        $Ordernum = $this->request->request('Ordernum', '');
        $ACID = $this->request->request('ACID', '');
        $Total = $this->request->request('Total', '');
        $Bank1 = $this->request->request('Bank1', '');
        $Bank2 = $this->request->request('Bank2', '');
        $Bank3 = $this->request->request('Bank3', '');
        $QRCode = $this->request->request('QRCode', '');

        if($Ordernum != '') $order = $Ordernum;
        $mBaccarat = model('Baccarat')->where("ordernum = '".$order."'")->find();
        if($mBaccarat){
            if($mBaccarat->take == 0){
                if($ACID != ''){
                    Log::notice('取號');
                    $mBaccarat->take = 1;
                    $mBaccarat->ACTCode = $ACID;
                    $mBaccarat->Bank1 = $Bank1;
                    $mBaccarat->Bank2 = $Bank2;
                    $mBaccarat->Bank3 = $Bank3;
                    $mBaccarat->QRCode = $QRCode;
                    $mBaccarat->save();
                }else{
                    Log::notice('前往取號');
                    $checkout_link = $this->site_url['furl']."/baccarat/checkout/order/".$mBaccarat->ordernum;
    
                    $Merchent = "WA";
                    // $Merchent = "AA";
                    $url = "http://pay.meixin.tw/api/getway02/VracRequest.ashx";
                    $url .= "?Merchent=".$Merchent;
                    $url .= "&OrderID=".$mBaccarat->ordernum;
                    $url .= "&Total=".$mBaccarat->debt;
                    $url .= "&Product=服務";
                    $url .= "&Name=葉加勒";
                    $url .= "&MSG=";
                    $url .= "&ReAUrl=".urlencode($checkout_link);
                    $url .= "&ReBUrl=".urlencode($this->site_url['api']."/baccarat/notify");
                    $this->redirect($url);
                }
            }
        }
        $this->view->assign('mBaccarat', $mBaccarat);
        return $this->view->fetch();
    }

}
