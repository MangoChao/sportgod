<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Log;
use think\Cookie;

/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     *
     */
    public function index()
    {
        $post = $this->request->post();
        Log::notice($post);
        $this->success('請求成功');
    }

    
    public function sysadminlogin()
    {
        Cookie::set('sysadminlogin', 1);
    }
}
