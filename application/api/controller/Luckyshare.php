<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Validate;

class Luckyshare extends Api
{
    // protected $noNeedLogin = [''];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }
    
    //發布文章
    public function add()
    {
        if(!$this->auth->id){
            $this->error('請先登入');
        }
        $mUser = model('User')->get(['id'=> $this->auth->id, 'status'=> 1]);
        if(!$mUser){
            $this->error('無權操作');
        }

        $hide = $this->request->request('hide', 0);
        $img = $this->request->request('img', '');
        $content = $this->request->request('content', '', 'trim');
        
        if($img == ''){
            $this->error('照片必須上傳');
        }
        if($content == ''){
            $this->error('內容不能為空');
        }

        $params = [
            'hide' => $hide,
            'img' => $img,
            'content' => $content,
            'status' => 0,
            'user_id' => $this->auth->id,
        ];

        $mLuckyshare = model('Luckyshare')::create($params);

        $this->success('分享成功');
    }

}
