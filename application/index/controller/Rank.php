<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Log;

class Rank extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $noNeedMerchant = '*';
    protected $layout = 'base';

    public function index()
    {
        $mRank = model('Rank')->order("id","desc")->find();
        $mRankcontent = false;
        if($mRank){
            $mRankcontent = model('Rankcontent')->alias('rc')
            ->join("analyst a","a.id = rc.analyst_id")
            ->field("rc.*, a.analyst_name, a.avatar")
            ->where('rc.rank_id = '.$mRank->id)->order('rc.rank','asc')->select();
            if($mRankcontent){
                foreach($mRankcontent as $v){
                    if(!$v->avatar) $v->avatar = $this->def_avatar;
                }
            }
        }
        $this->view->assign('mRank', $mRank);
        $this->view->assign('mRankcontent', $mRankcontent);
        return $this->view->fetch();
    }
}
