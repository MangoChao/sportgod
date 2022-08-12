<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Log;

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
            
        }
        $this->view->assign('mBaccaratorder', $mBaccaratorder);
        return $this->view->fetch();
    }

}
