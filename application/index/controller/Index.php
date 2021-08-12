<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Log;

class Index extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $noNeedMerchant = '*';
    protected $layout = 'base';

    public function index()
    {
        return $this->view->fetch();
    }
    
    public function contact()
    {
        return $this->view->fetch();
    }

}
