<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Log;

class Index extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $noNeedMerchant = '*';
    protected $layout = '';

    public function index()
    {
        return $this->view->fetch();
    }
    
    // public function line($key = null)
    // {
    //     if($key){
    //         $mNotify = model('Notify')->where(" MD5(CONCAT(id,notifytime)) = '".$key."' ")->find();
    //         if($mNotify){
    //             $mNotify->isread = 1;
    //             $mNotify->save();
    //             $mMerchant = model('Merchant')->get($mNotify->merchant_id);
    //             if($mMerchant) $this->redirect($mMerchant->merchant_line); 
    //         }
    //     }
    //     $this->redirect(url('/')); 
    // }

}
