<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Log;

class Index extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $noNeedMerchant = '*';
    protected $layout = 'base';

    public function index()
    {
        $mNewArticle = model('Article')->where("status = 1 AND img <> '' ")->order("createtime","desc")->limit(8)->select();

        $mCatArticle = [];
        $mArticlecat = model('Articlecat')->where('status = 1')->order('weigh')->select();
        if($mArticlecat){
            foreach($mArticlecat as $v){
                $mCatArticle[] = [
                    'mArticle' => model('Article')->where("status = 1 AND img <> '' AND cat_id = ".$v->id)->order("createtime","desc")->limit(5)->select(),
                    'cat_name' => $v->cat_name,
                ];
            }
        }

        $this->view->assign('mNewArticle', $mNewArticle);
        $this->view->assign('mCatArticle', $mCatArticle);
        return $this->view->fetch();
    }
    
    public function contact()
    {
        return $this->view->fetch();
    }

}
