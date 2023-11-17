<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Log;

class Luckyshare extends Frontend
{

    protected $noNeedLogin = ['index'];
    protected $noNeedRight = '*';
    protected $layout = 'base';

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        $page = $this->request->request('page', 1);
        $mLuckyshare = model('Luckyshare')->alias('ls')
        ->join("user u","u.id = ls.user_id")
        ->field("ls.*, u.nickname, u.avatar")
        ->where('ls.status = 1')->order('ls.id','desc')->paginate(20, false, $this->paginate_config);
        $count = $mLuckyshare->total();
        $pagelist = $mLuckyshare->render();

        $this->view->assign('count', $count);
        $this->view->assign('page', $page);
        $this->view->assign('pagelist', $pagelist);
        
        if($mLuckyshare){
            foreach($mLuckyshare as $v){
                if(!$v->avatar) $v->avatar = $this->def_avatar;
                
                if($v->hide == 1){
                    $v->avatar = $this->def_avatar;
                    $v->nickname = '匿名';
                }
            }
        }
        $this->view->assign('mLuckyshare', $mLuckyshare);
        return $this->view->fetch();
    }

    
    public function add()
    {
        return $this->view->fetch();
    }
}
