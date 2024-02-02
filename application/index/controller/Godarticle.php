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
    }

    //文章列表
    public function index($id=null)
    {
        $whereStr = "";
        if($id){
            $whereStr = " AND gt.id = '".$id."'";
        }
        $page = $this->request->request('page', 1);
        $mGodarticle = model('Godarticle')->alias('a')
        ->join("user u","(u.id = a.user_id AND u.status = 1) OR a.user_id = 0 ")
        ->join("god_type gt","gt.id = a.god_type and gt.status = 1")
        ->field('a.*, u.nickname, u.avatar')
        ->where("a.status = 1 ".$whereStr)->group('a.id')->order('a.updatetime','desc')->paginate(20, false, $this->paginate_config);
        // Log::notice( model('Godarticle')->getLastSql());
        
        // if($mGodarticle){
        //     foreach($mGodarticle as $v){
        //         // if(!$v->avatar) $v->avatar = $this->def_avatar;
        //     }
        // }

        $count = $mGodarticle->total();
        $pagelist = $mGodarticle->render();
        
        
        //1神人 2教學 3新聞 4視界
        $this->view->assign('count', $count);
        $this->view->assign('page', $page);
        $this->view->assign('pagelist', $pagelist);
        $this->view->assign('mGodarticle', $mGodarticle);
        
        //1神人 2教學 3新聞 4視界
        if($id == 2){
            return $this->view->fetch('godarticle/teach');
        }elseif($id == 3){
            return $this->view->fetch('godarticle/news');
        }elseif($id == 4){
            return $this->view->fetch('godarticle/video');
        }
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

        $this->view->assign('mGodarticle', $mGodarticle);

        //1神人 2教學 3新聞 4視界
        if($mGodarticle->god_type == 2){
            return $this->view->fetch('godarticle/detail/teach');
        }elseif($mGodarticle->god_type == 3){
            return $this->view->fetch('godarticle/detail/news');
        }elseif($mGodarticle->god_type == 4){
            return $this->view->fetch('godarticle/detail/video');
        }
        return $this->view->fetch('godarticle/detail/base');
    }

}
