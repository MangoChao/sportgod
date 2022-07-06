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
    
    //發布專欄
    public function addgodarticle()
    {
        $id = $this->request->request('id', 0);
        $cat_id = $this->request->request('cat_id', 0);
        $title = $this->request->request('title', '', 'trim');
        $cover_img = $this->request->request('cover_img', '');
        $content = $this->request->request('content', '', 'trim');
        
        $mUser = model('User')->get($this->auth->id);
        if(!$mUser){
            $this->error('查無用戶, 請重新登入');
        }
        if($mUser->status == 0){
            $this->error('你已被停用');
        }

        if($mUser->isgod == 0){
            $this->error('你不是神人');
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
            'cover_img' => $cover_img,
            'content' => $content,
            'user_id' => $this->auth->id,
        ];

        $mGodarticle = model('Godarticle')::create($params);

        $this->success('發佈成功');
    }
    
    //發布文章
    public function addarticle()
    {
        $id = $this->request->request('id', 0);
        $cid = $this->request->request('cid', 0);
        $cat_id = $this->request->request('cat_id', 0);
        $title = $this->request->request('title', '', 'trim');
        $img = $this->request->request('img', '');
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
            'img' => $img,
            'content' => $content,
            'user_id' => $this->auth->id,
        ];

        $mArticle = model('Article')::create($params);

        $params = [
            'article_id' => $mArticle->id,
            'user_id' => $this->auth->id,
        ];
        model('Articleread')::create($params);
        $this->success('發佈成功',['cid' => $cid]);
    }

    //編輯文章
    public function editarticle()
    {
        $id = $this->request->request('id', 0);
        $cid = $this->request->request('cid', 0);
        $cat_id = $this->request->request('cat_id', 0);
        $title = $this->request->request('title', '', 'trim');
        $img = $this->request->request('img', '');
        $content = $this->request->request('content', '', 'trim');
        
        $mUser = model('User')->get($this->auth->id);
        if(!$mUser){
            $this->error('查無用戶, 請重新登入');
        }
        if($mUser->status == 0){
            $this->error('你已被停用');
        }
        
        $mArticle = model('Article')->where("id = ".$id." AND user_id = ".$this->auth->id)->find();
        if(!$mArticle){
            $this->error('查無文章, 或是沒有編輯權限');
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
    

        $mArticle->cat_id = $cat_id;
        $mArticle->title = $title;
        $mArticle->img = $img;
        $mArticle->content = $content;

        $mArticle->save();

        model('Articleread')->where("article_id = ".$id." AND user_id <> ".$this->auth->id)->delete();
        $this->success('編輯成功',['id' => $id]);
    }
    
    public function editgodarticle()
    {
        $id = $this->request->request('id', 0);
        $cat_id = $this->request->request('cat_id', 0);
        $title = $this->request->request('title', '', 'trim');
        $cover_img = $this->request->request('cover_img', '');
        $content = $this->request->request('content', '', 'trim');
        
        $mUser = model('User')->get($this->auth->id);
        if(!$mUser){
            $this->error('查無用戶, 請重新登入');
        }
        if($mUser->status == 0){
            $this->error('你已被停用');
        }

        if($mUser->isgod == 0){
            $this->error('你不是神人');
        }
        
        $mArticle = model('Godarticle')->where("id = ".$id." AND user_id = ".$this->auth->id)->find();
        if(!$mArticle){
            $this->error('查無專欄, 或是沒有編輯權限');
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

        if($mArticle->cat_id != $cat_id){
            $mArticle->cat_id = $cat_id;
            $mArticle->status = 0;
        }
        if($mArticle->title != $title){
            $mArticle->title = $title;
            $mArticle->status = 0;
        }
        if($mArticle->cover_img != $cover_img){
            $mArticle->cover_img = $cover_img;
            $mArticle->status = 0;
        }
        if($mArticle->content != $content){
            $mArticle->content = $content;
            $mArticle->status = 0;
        }

        $mArticle->save();

        $this->success('編輯成功',['id' => $id]);
    }

    public function delarticle($id = 0)
    {
        
        $mUser = model('User')->get($this->auth->id);
        if(!$mUser){
            $this->error('查無用戶, 請重新登入');
        }
        if($mUser->status == 0){
            $this->error('你已被停用');
        }

        $mArticle = model('Article')->where("id = ".$id." AND user_id = ".$this->auth->id)->find();
        if(!$mArticle){
            $this->error('查無文章, 或是沒有編輯權限');
        }

        $mArticle->status = 2;
        $mArticle->save();
        $this->success('刪除成功');
    }

    public function delgodarticle($id = 0)
    {
        
        $mUser = model('User')->get($this->auth->id);
        if(!$mUser){
            $this->error('查無用戶, 請重新登入');
        }
        if($mUser->status == 0){
            $this->error('你已被停用');
        }

        $mArticle = model('Godarticle')->where("id = ".$id." AND user_id = ".$this->auth->id)->find();
        if(!$mArticle){
            $this->error('查無專欄, 或是沒有編輯權限');
        }

        $mArticle->status = 3;
        $mArticle->save();
        $this->success('刪除成功');
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
        
        model('Articleread')->where("article_id = ".$id." AND user_id <> ".$this->auth->id)->delete();
        $this->success('留言成功');
    }
    
    //收藏
    public function setfav()
    {
        $id = $this->request->request('id', 0);
        $type = $this->request->request('type', 1);
        $mUser = model('User')->get($this->auth->id);
        if(!$mUser){
            $this->error('查無用戶, 請重新登入');
        }
        if($mUser->status == 0){
            $this->error('你已被停用');
        }

        if($type == 1){
            $mArticle = model('Article')->get($id);
            if(!$mArticle) $this->error('文章不存在');
            if($mArticle->status == 0) $this->error('文章已關閉');
        }else{
            $mArticle = model('Godarticle')->get($id);
            if(!$mArticle) $this->error('專欄不存在');
            if($mArticle->status == 0) $this->error('專欄已關閉');
        }

        
        $mArticlefav = model('Articlefav')->where("user_id = ".$this->auth->id." AND article_id = ".$id." AND type = ".$type)->find();
        if($mArticlefav){
            $mArticlefav->delete();
            $successText = '已取消收藏';
            $text = '收藏文章';
            $active = 0;
        }else{
            $params = [
                'article_id' => $id,
                'type' => $type,
                'user_id' => $this->auth->id,
            ];
            model('Articlefav')::create($params);
            $successText = '已成功收藏';
            $text = '取消收藏';
            $active = 1;
        }
        
        $count = model('Articlefav')->where("article_id = ".$id." AND type = ".$type)->count();
        $mArticle->fav = $count;
        $mArticle->save();

        $rData = [
            'count' => $count,
            'text' => $text,
            'active' => $active,
        ];

        $this->success($successText, $rData);
    }
    
    //贊助
    public function dotnet()
    {
        $id = $this->request->request('id', 0);
        $mUser = model('User')->get($this->auth->id);
        if(!$mUser){
            $this->error('查無用戶, 請重新登入');
        }
        if($mUser->status == 0){
            $this->error('你已被停用');
        }

        $mGodarticle = model('Godarticle')->get($id);
        if(!$mGodarticle) $this->error('專欄不存在');
        if($mGodarticle->status == 0) $this->error('專欄已關閉');

        $point = 100;
        
        $memo = "贊助專欄 Godarticle id:".$id;
        $this->changePoint($this->auth->id, -$point, $memo);
        
        $real_point = round($point/2);
        $memo = "專欄被贊助 Godarticle id:".$id." 實收".$real_point;
        $this->changePoint($mGodarticle->user_id, $real_point, $memo);

        $params = [
            'godarticle_id' => $id,
            'user_id' => $this->auth->id,
            'godarticle_user_id' => $mGodarticle->user_id,
            'point' => $point,
            'real_point' => $real_point,
        ];
        model('Dotnet')::create($params);

        $this->success('贊助成功!');
    }
    
}
