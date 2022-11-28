<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Log;

class Godarticle extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = 'base';

    public function _initialize()
    {
        parent::_initialize();
        exit;
    }

    //文章列表
    public function index()
    {
        $page = $this->request->request('page', 1);
        $mGodarticle = model('Godarticle')->alias('a')
        ->join("user u","(u.id = a.user_id AND u.status = 1) OR a.user_id = 0 ")
        ->field('a.*, u.nickname, u.avatar')
        ->where("a.status = 1 ")->group('a.id')->order('a.updatetime','desc')->paginate(20, false, $this->paginate_config);
        
        // if($mGodarticle){
        //     foreach($mGodarticle as $v){
        //         // if(!$v->avatar) $v->avatar = $this->def_avatar;
        //     }
        // }

        $count = $mGodarticle->total();
        $pagelist = $mGodarticle->render();
        
        $this->view->assign('count', $count);
        $this->view->assign('page', $page);
        $this->view->assign('pagelist', $pagelist);
        $this->view->assign('mGodarticle', $mGodarticle);
        return $this->view->fetch();
    }
    
    //文章內容
    public function detail($id = null)
    {
        $mGodarticle = model('Godarticle')->alias('a')
        ->join("user u","(u.id = a.user_id AND u.status = 1) OR a.user_id = 0 ")
        ->field('a.*, u.nickname, u.avatar')
        ->where("a.status = 1 AND a.id = ".$id)->find();

        if(!$mGodarticle){
            $this->redirect('/index/godarticle');
        }
        if(!$mGodarticle->avatar) $mGodarticle->avatar = $this->def_avatar;

        $mGodarticle->user_id;

        $mGodarticleLast = model('Godarticle')->alias('a')
        ->field('a.*')
        ->where("a.status = 1 AND a.id <> ".$id." AND a.user_id = ".$mGodarticle->user_id)->order('a.updatetime','desc')->limit(3)->select();

        $hasfav = false;
        if($this->auth->isLogin()){
            $afc = model('Articlefav')->where("user_id = ".$this->auth->id." AND type = 2 AND article_id = ".$id)->count();
            if($afc > 0) $hasfav = true;
        }

        $this->view->assign('hasfav', $hasfav);
        $this->view->assign('mGodarticleLast', $mGodarticleLast);
        $this->view->assign('mGodarticle', $mGodarticle);
        return $this->view->fetch();
    }

}
