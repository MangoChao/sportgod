<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Log;
use think\Cookie;

class V extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    public function index()
    {
        return $this->view->fetch();
    }
}
