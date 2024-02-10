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
    public function index($id=null, $cat = null)
    {
        $whereStr = "";
        if($id){
            $whereStr .= " AND gt.id = '".$id."'";
            if($id != 2){
                $whereStr .= " AND ac.status = 1";
            }
        }
        if($cat){
            $whereStr .= " AND ac.id = '".$cat."'";
        }
        $page = $this->request->request('page', 1);
        $paginate = 9999;
        $mGodarticle = model('Godarticle')->alias('a')
        ->join("user u","(u.id = a.user_id AND u.status = 1) OR a.user_id = 0 ")
        ->join("god_type gt","gt.id = a.god_type and gt.status = 1")
        ->join("article_cat ac","ac.id = a.cat_id")
        ->field('a.*, u.nickname, u.avatar, ac.cat_name')
        ->where("a.status = 1 AND (a.cover_img <> '' OR a.video_url <> '' ) ".$whereStr)->group('a.id')->order(['ac.weigh' => 'asc', 'a.updatetime' => 'desc'])->paginate($paginate, false, $this->paginate_config);
        // Log::notice( model('Godarticle')->getLastSql());
        
        $list = [];
        if($mGodarticle){
            $teachCatList = getTeachCatList();
            foreach($mGodarticle as $v){
                if (!isset($list[$v->cat_id])){
                    if($id == 2){
                        $title = $teachCatList[$v->cat_id];
                    }else{
                        $title = $v->cat_name;
                    }
                    $list[$v->cat_id] = [
                        'title' => $title,
                        'list' => []
                    ];
                }

                $list[$v->cat_id]['list'][] = $v;
                // if(!$v->avatar) $v->avatar = $this->def_avatar;
            }
        }

        $count = $mGodarticle->total();
        $pagelist = $mGodarticle->render();
        
        
        $this->view->assign('count', $count);
        $this->view->assign('page', $page);
        $this->view->assign('pagelist', $pagelist);
        $this->view->assign('mGodarticle', $mGodarticle);
        $this->view->assign('list', $list);
        
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
        ->join("article_cat ac","ac.id = a.cat_id")
        ->field('a.*, u.nickname, u.avatar, ac.cat_name')
        ->where("a.status = 1 AND a.id = ".$id)->find();

        if(!$mGodarticle){
            $this->redirect('/index/godarticle');
        }
        $mGodarticle->views += 1; 
        $mGodarticle->save();
        if(!$mGodarticle->avatar) $mGodarticle->avatar = $this->def_avatar;

        $this->view->assign('mGodarticle', $mGodarticle);

        $teachCatList = getTeachCatList();
        if($mGodarticle->god_type == 2){
            $title = $teachCatList[$mGodarticle->cat_id];
        }else{
            $title = $mGodarticle->cat_name;
        }
        $list = [
            'title' => $title,
            'list' => []
        ];
        $mGodarticleList = model('Godarticle')->alias('a')
        ->join("user u","(u.id = a.user_id AND u.status = 1) OR a.user_id = 0 ")
        ->join("god_type gt","gt.id = a.god_type and gt.status = 1")
        ->join("article_cat ac","ac.id = a.cat_id")
        ->field('a.*, u.nickname, u.avatar, ac.cat_name')
        ->where("a.status = 1 AND (a.cover_img <> '' OR a.video_url <> '' ) AND a.cat_id = ".$mGodarticle->cat_id." AND a.god_type = ".$mGodarticle->god_type." ")->group('a.id')->order(['ac.weigh' => 'asc', 'a.updatetime' => 'desc'])->select();
        if($mGodarticleList){
            foreach($mGodarticleList as $v){
                $list['list'][] = $v;
            }
        }
        $this->view->assign('list', $list);

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
