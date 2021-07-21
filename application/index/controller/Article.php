<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Log;

//文章
class Article extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $noNeedMerchant = '*';
    protected $layout = 'base';

    //文章列表
    public function index($cat = null)
    {
        
        return $this->view->fetch();
    }
    
    //文章內容
    public function detail($id = null)
    {
        return $this->view->fetch();
    }
    

}
