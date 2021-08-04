<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Validate;

class Article extends Api
{
    // protected $noNeedLogin = [''];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }
    
    public function addmsg()
    {
        $id = $this->request->request('id', 0);
        $msg = $this->request->request('msg', '', 'trim');
        $mUser = model('User')->get($this->auth->id);
        if(!$mUser){
            $this->error('查無用戶, 請重新登入');
        }
        if($mUser->status == 0){
            $this->error('你已被停用');
        }

        $mArticle = model('Article')->get($id);
        if(!$mArticle) $this->error('文章不存在');
        if($mArticle->status == 0) $this->error('文章已關閉');

        $params = [
            'article_id' => $id,
            'msg' => $msg,
            'user_id' => $this->auth->id,
        ];

        model('Articlemsg')::create($params);
    }

}
