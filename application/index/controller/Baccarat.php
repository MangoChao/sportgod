<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Log;

class Baccarat extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = 'base';

    public function debt()
    {
        $code = $this->request->request('code', '');
        $debt = $this->request->request('debt', '');
        if($code == '' || $debt == ''){
            return '{"code":0,"msg":"缺少參數","time":"'.time().'","data":null}';
        }
        if(!is_numeric($debt)){
            return '{"code":0,"msg":"debt必須是數字","time":"'.time().'","data":null}';
        }
        if(!$debt > 0){
            return '{"code":0,"msg":"debt必須大於0","time":"'.time().'","data":null}';
        }
        $mBaccarat = model('Baccarat')->where("code = '".$code."'")->find();
        if($mBaccarat){
            if($mBaccarat->status == 1){
                Log::notice('產生新欠款');
                $ordernum = 'BR'.date('YmdHis');
                $mBaccarat->ordernum = $ordernum;
                $mBaccarat->debt = $debt;
                $mBaccarat->status = 0;
                $mBaccarat->save();

                $url = "http://pay.meixin.tw/api/getway02/VracRequest.ashx";
                $url .= "?Merchent=AA";
                $url .= "&OrderID=".$ordernum;
                $url .= "&Total=".$debt;
                $url .= "&Product=服務";
                $url .= "&Name=葉加勒";
                $url .= "&MSG=";
                $url .= "&ReAUrl=".urlencode($this->site_url['api']."/baccarat/creatdebt");
                $url .= "&ReBUrl=".urlencode($this->site_url['api']."/baccarat/notify");
                $this->redirect($url);
            }else{
                return '{"code":0,"msg":"尚未結清","time":"'.time().'","data":{"debt":"'.$mBaccarat->debt.'","ACID":"'.$mBaccarat->ACTCode.'","Bank1":"'.$mBaccarat->Bank1.'","QRCode":"'.$mBaccarat->QRCode.'"}}';
            }
        }else{
            return '{"code":0,"msg":"代碼無效","time":"'.time().'","data":null}';
        }
    }


}
