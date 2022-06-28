<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Log;

class Index extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = 'base';

    public function index()
    {
        $mNewArticle = model('Article')->alias('a')
        ->join("article_cat ac","ac.id = a.cat_id AND ac.status = 1")
        ->field('a.*')
        ->where("a.status = 1 AND a.img <> '' ")->order("a.createtime","desc")->limit(8)->select();

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

    
    public function debt()
    {
        $code = $this->request->request('code', '');
        $debt = $this->request->request('debt', '');
        if($code == '' || $debt == ''){
            $this->error('缺少參數');
        }
        if(!is_numeric($debt)){
            $this->error('debt必須是數字');
        }
        if(!$debt > 0){
            $this->error('debt必須大於0');
        }
        $mBaccarat = model('Baccarat')->where("code = '".$code."'")->find();
        if($mBaccarat){
            if($mBaccarat->status == 1){
                $ordernum = 'BR'.date('YmdHis');
                $url = "http://pay.meixin.tw/api/getway02/VracRequest.ashx";
                $url .= "?Merchent=AA";
                $url .= "&OrderID=".$ordernum;
                $url .= "&Total=".$debt;
                $url .= "&Product=服務";
                $url .= "&Name=葉加勒";
                $url .= "&MSG=";
                $url .= "&ReAUrl=".urlencode($this->site_url['api']."/baccarat/notify");
                $url .= "&ReBUrl=".urlencode($this->site_url['api']."/baccarat/notify");
                $this->redirect($url);
                // $postData = [
                //     'Merchent' => 'AA',
                //     'OrderID' => $ordernum,
                //     'Total' => $debt,
                //     'Product' => '服務',
                //     'Name' => '葉加勒',
                //     'MSG' => '',
                //     'ReAUrl' => $this->site_url['api']."/baccarat/notify",
                //     'ReBUrl' => $this->site_url['api']."/baccarat/notify",
                // ];
                // $r = curl_post($url, $postData);
                // echo $r;

                // $this->success('已產生欠款',['ordernum' => $ordernum, 'debt' => $debt, 'ACTCode' => '']);
            }else{
                $this->error('尚未結清',['ordernum' => $mBaccarat->ordernum, 'debt' => $mBaccarat->debt, 'ACTCode' => $mBaccarat->ACTCode]);
            }
        }else{
            $this->error('代碼無效');
        }
    }


}
