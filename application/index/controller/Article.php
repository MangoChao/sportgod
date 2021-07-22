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
    public function index($cat = null, $page = 1)
    {
        $catWhere = "";
        $ptitle = "所有文章";
        if($cat){
            $catWhere = " AND cat_id = ".$cat;
            $mArticlecat = model('Articlecat')->get($cat);
            $ptitle = $mArticlecat->cat_name;
        }
        $mArticle = model('Article')->alias('a')
        ->join("article_cat ac","ac.id = a.cat_id AND ac.status = 1")
        ->field('a.*, ac.cat_name')
        ->where("a.status = 1 ".$catWhere)->paginate(50);
        
        $count = $mArticle->total();
        $pagelist = $mArticle->render();

        $this->view->assign('count', $count);
        $this->view->assign('page', $page);
        $this->view->assign('pagelist', $pagelist);
        $this->view->assign('mArticle', $mArticle);
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
        $this->view->assign('mArticle', $mArticle);
        return $this->view->fetch();
    }
    

}
