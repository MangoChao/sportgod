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
    public function index($cid = 0, $page = 1)
    {
        $catWhere = "";
        $ptitle = "所有文章";
        if($cid != 0){
            $catWhere = " AND cat_id = ".$cid;
            $mACat = model('Articlecat')->get($cid);
            $ptitle = $mACat->cat_name;
        }else{
            $mACat = false;
        }
        $mArticle = model('Article')->alias('a')
        ->join("user u","(u.id = a.user_id AND u.status = 1) OR a.user_id = 0 ")
        ->join("article_cat ac","ac.id = a.cat_id AND ac.status = 1")
        ->join("article_msg am","a.id = am.article_id AND am.status = 1","LEFT")
        ->field('a.*, ac.cat_name, u.nickname, count(am.id) as msg_count')
        ->where("a.status = 1 ".$catWhere)->group('a.id')->paginate(5);
        
        if($mArticle){
            foreach($mArticle as $v){
                if($v->user_id == 0){
                    $v->nickname = "管理員";
                }
            }
        }

        $count = $mArticle->total();
        $pagelist = $mArticle->render();
        
        $this->view->assign('count', $count);
        $this->view->assign('page', $page);
        $this->view->assign('pagelist', $pagelist);
        $this->view->assign('mArticle', $mArticle);
        $this->view->assign('mACat', $mACat);
        $this->view->assign('cid', $cid);
        $this->view->assign('ptitle', $ptitle);
        return $this->view->fetch();
    }
    
    //文章內容
    public function detail($id = null)
    {
        $mArticle = model('Article')->alias('a')
        ->join("user u","(u.id = a.user_id AND u.status = 1) OR a.user_id = 0 ")
        ->join("article_cat ac","ac.id = a.cat_id AND ac.status = 1")
        ->field('a.*, ac.cat_name, u.nickname')
        ->where("a.status = 1 AND a.id = ".$id)->find();

        if(!$mArticle){
            $this->redirect('/index/article');
        }
        if($mArticle->user_id == 0){
            $mArticle->nickname = "管理員";
        }
        
        $mArticlemsg = model('Articlemsg')->alias('am')
        ->join("user u","u.id = am.user_id AND u.status = 1")
        ->field('am.*, u.nickname')
        ->where("am.status = 1 AND am.article_id = ".$id)->select();

        $hasfav = false;
        if($this->auth->isLogin()){
            $afc = model('Articlefav')->where("user_id = ".$this->auth->id." AND article_id = ".$id)->count();
            if($afc > 0) $hasfav = true;
        }

        $this->view->assign('hasfav', $hasfav);
        $this->view->assign('mArticle', $mArticle);
        $this->view->assign('mArticlemsg', $mArticlemsg);
        return $this->view->fetch();
    }

}
