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
    
    //發布文章
    public function addarticle()
    {
        $id = $this->request->request('id', 0);
        $cid = $this->request->request('cid', 0);
        $cat_id = $this->request->request('cat_id', 0);
        $title = $this->request->request('title', '', 'trim');
        $content = $this->request->request('content', '', 'trim');
        
        $mUser = model('User')->get($this->auth->id);
        if(!$mUser){
            $this->error('查無用戶, 請重新登入');
        }
        if($mUser->status == 0){
            $this->error('你已被停用');
        }

        if($cat_id == 0){
            $this->error('必須選擇分類');
        }
        if($title == ''){
            $this->error('標題不能為空');
        }
        if($content == ''){
            $this->error('內容不能為空');
        }

        $params = [
            'cat_id' => $cat_id,
            'title' => $title,
            'content' => $content,
            'user_id' => $this->auth->id,
        ];

        model('Article')::create($params);

        $this->success('發佈成功',['cid' => $cid]);
    }

    //留言
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
        $this->success('留言成功');
    }
    
    //收藏
    public function setfav()
    {
        $id = $this->request->request('id', 0);
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

        
        $mArticlefav = model('Articlefav')->where("user_id = ".$this->auth->id." AND article_id = ".$id)->find();
        if($Articlefav){
            $Articlefav->delete();
            $successText = '已取消收藏';
            $text = '收藏文章';
        }else{
            $params = [
                'article_id' => $id,
                'user_id' => $this->auth->id,
            ];
            model('Articlefav')::create($params);
            $successText = '已成功收藏';
            $text = '取消收藏';
        }
        
        $count = model('Articlefav')->where("article_id = ".$id)->count();
        $mArticle->fav = $count;
        $mArticle->save();

        $rData = [
            'count' => $count,
            'text' => $text,
        ];

        $this->success($successText, $rData);
    }
    
}
