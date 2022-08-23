<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Log;

use app\api\controller\Baccarat as ApiBaccarat;

class Baccarat extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = 'baccarat';
    protected $check_sysadminlogin = false;

    public function _initialize()
    {
        parent::_initialize();
        Log::init(['type' => 'File', 'log_name' => 'Baccarat']);
    }

    public function checkout($code = '')
    {
        $mBaccaratorder = model('Baccarat')->alias('b')
        ->join("baccarat_order bo","bo.id = b.baccarat_order_id","LEFT")
        ->field("bo.*, b.code, b.order_status")
        ->where("b.code = '".$code."'")->find();
        if($mBaccaratorder){
            if($mBaccaratorder->trade_type == 1){
                if($mBaccaratorder->end_time_strtotime <= time()){
                    $ApiBaccarat = new ApiBaccarat;
                    $r = $ApiBaccarat->reOrder($code);
                    
                    if($r){
                        $mBaccaratorder = model('Baccarat')->alias('b')
                        ->join("baccarat_order bo","bo.id = b.baccarat_order_id","LEFT")
                        ->field("bo.*, b.code, b.order_status")
                        ->where("b.code = '".$code."'")->find();
                    }else{
                        $orderid = 'BR'.date('YmdHis');
                        $amount = (int)$mBaccaratorder->amount;
                        $p = [
                            'baccarat_id' => $mBaccaratorder->baccarat_id,
                            'order_no' => $orderid,
                            'amount' => $amount,
                            'status' => 0,
                            'ip' => $mBaccaratorder->ip,
                            'trade_type' => 2
                        ];
                        $mBtr = model('Baccaratorder')::create($p);

                        $mBaccarat = model('Baccarat')->get($mBaccaratorder->baccarat_id);
                        if($mBaccarat){
                            $mBaccarat->order_status = 0;
                            $mBaccarat->baccarat_order_id = $mBtr->id;
                            $mBaccarat->save();
                        }
                        $mBaccaratorder = model('Baccarat')->alias('b')
                        ->join("baccarat_order bo","bo.id = b.baccarat_order_id","LEFT")
                        ->field("bo.*, b.code, b.order_status")
                        ->where("b.code = '".$code."'")->find();
                        return $this->orderpage($mBaccaratorder);
                    }
                }
            }elseif($mBaccaratorder->trade_type == 2){
                return $this->orderpage($mBaccaratorder);
            }
        }
        $this->view->assign('mBaccaratorder', $mBaccaratorder);
        return $this->view->fetch();
    }

    
    public function orderpage($mOrder)
    {
        $ItemDesc = "程式服務費用";

        $TradeInfo = [
            'MerchantID' => $this->newebpay_MerchantID,
            'RespondType' => 'JSON',
            'TimeStamp' => time(),
            'Version' => '1.5',
            'LangType' => 'zh-tw',
            'MerchantOrderNo' => $mOrder->order_no,
            'Amt' => $mOrder->amount,
            'ItemDesc' => $ItemDesc,
            'TradeLimit' => 0,
            'ExpireDate' => '',
            'ReturnURL' => '',
            'NotifyURL' => $this->site_url['api'].'/baccarat/notify2',
            'CustomerURL' => '',
            'ClientBackURL' => '',
            'Email' => '',
            'EmailModify' => 1,
            'LoginType' => 0,
            'OrderComment' => '',
        ];
        
        $TradeInfo = create_mpg_aes_encrypt($TradeInfo, $this->newebpay_HashKey, $this->newebpay_HashIV); 
        $TradeSha = "HashKey=".$this->newebpay_HashKey."&".$TradeInfo."&HashIV=".$this->newebpay_HashIV;
        $TradeSha = strtoupper(hash("sha256", $TradeSha));
        $szHtmlData = [
            'url' => $this->newebpay_url,
            'MerchantID' => $this->newebpay_MerchantID,
            'TradeInfo' => $TradeInfo,
            'TradeSha' => $TradeSha,
            'Version' => '1.5',
        ];

        $szHtml = '<!doctype html>';
        $szHtml .= '<html>';
        $szHtml .= '<head>';
        $szHtml .= '<meta charset="utf-8">';
        $szHtml .= '</head>';
        $szHtml .= '<body>';
        $szHtml .= '<form name="newebpay" id="newebpay" method="post" action="' . $szHtmlData['url'] . '" style="display:none;">';
        $szHtml .= '<input name="MerchantID" value="' . $szHtmlData['MerchantID'] . '" type="hidden">';
        $szHtml .= '<input name="TradeInfo" value="' . $szHtmlData['TradeInfo'] . '"   type="hidden">';
        $szHtml .= '<input name="TradeSha" value="' . $szHtmlData['TradeSha'] . '" type="hidden">';
        $szHtml .= '<input name="Version"  value="' . $szHtmlData['Version'] . '" type="hidden">';
        $szHtml .= '</form>';
        $szHtml .= '<script type="text/javascript">';
        $szHtml .= 'document.getElementById("newebpay").submit();';
        $szHtml .= '</script>';
        $szHtml .= '</body>';
        $szHtml .= '</html>';

        return $szHtml;
    }

}
