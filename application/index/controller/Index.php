<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Log;
use think\Cookie;

class Index extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = 'base';

    public function _initialize()
    {
        parent::_initialize();
        Log::init(['type' => 'File', 'log_name' => 'index']);
    }

    public function index()
    {
        $mNewArticle = model('Godarticle')->alias('a')
        ->join("article_cat ac","ac.id = a.cat_id AND ac.status = 1")
        ->field('a.*')
        ->where("a.status = 1 AND a.god_type = 0 AND a.cover_img <> '' ")->order("a.createtime","desc")->limit(8)->select();
        
        //足球
        $mArticle1 = model('Godarticle')->alias('a')
        ->join("article_cat ac","ac.id = a.cat_id AND ac.status = 1")
        ->field('a.*')
        ->where("a.status = 1 AND a.cover_img <> '' AND a.cat_id = 4")->order("a.createtime","desc")->limit(7)->select();

        //籃球
        $mArticle2 = model('Godarticle')->alias('a')
        ->join("article_cat ac","ac.id = a.cat_id AND ac.status = 1")
        ->field('a.*')
        ->where("a.status = 1 AND a.cover_img <> '' AND a.cat_id = 1")->order("a.createtime","desc")->limit(7)->select();
        
        //棒球
        $mArticle3 = model('Godarticle')->alias('a')
        ->join("article_cat ac","ac.id = a.cat_id AND ac.status = 1")
        ->field('a.*')
        ->where("a.status = 1 AND a.cover_img <> '' AND a.cat_id = 3")->order("a.createtime","desc")->limit(7)->select();

        
        // $H = date('H');
        // //排行
        // $c = 0;
        // $ckshowhome = true; //第一次 有查時間
        // $mRankcontent = false;
        // do{
        //     $rcid = 0;
        //     $mECtitle = "";
        //     if($ckshowhome){
        //         $whereStr = "showhome1 <= ".$H." AND showhome2 > ".$H." ";
        //         $whereStr .= " OR (showhome1 > showhome2 AND showhome2 > ".$H.")";
        //         $whereStr .= " OR (showhome1 > showhome2 AND showhome1 <= ".$H.")";
        //         $whereStr = " AND (".$whereStr.")";
        //     }else{
        //         $whereStr = "";
        //     }
        //     $mEventcategory = model('Eventcategory')->where("status = 1 ".$whereStr)->orderRaw('RAND()')->find();
        //     if($mEventcategory){
        //         $rcid = $mEventcategory->id;
        //         $mECtitle = $mEventcategory->title;

        //         $mRank = model('Rank')->where("event_category_id = ".$rcid)->order("id","desc")->find();
        //         $mRankcontent = false;
        //         if($mRank){
        //             $mRankcontent = model('Rankcontent')->alias('rc')
        //             ->join("analyst a","a.id = rc.analyst_id")
        //             ->field("rc.*, a.analyst_name, a.avatar")
        //             ->where('rc.rank_id = '.$mRank->id)->order('rc.rank','asc')->limit(8)->select();
        //             if($mRankcontent){
        //                 foreach($mRankcontent as $v){
        //                     if(!$v->avatar) $v->avatar = $this->def_avatar;
        //                 }
        //             }
        //         }
        //     }else{
        //         $ckshowhome = false;
        //     }
        //     $c++;
        // }while($c <= 12 AND $mRankcontent === false);
        // $this->view->assign('rcid', $rcid);
        // $this->view->assign('mECtitle', $mECtitle);
        // $this->view->assign('mRank', $mRank);
        // $this->view->assign('mRankcontent', $mRankcontent);

        $this->view->assign('mNewArticle', $mNewArticle);
        $this->view->assign('mArticle1', $mArticle1);
        $this->view->assign('mArticle2', $mArticle2);
        $this->view->assign('mArticle3', $mArticle3);
        return $this->view->fetch();
    }
    
    public function contact()
    {
        return $this->view->fetch();
    }

    public function policy()
    {
        // Cookie::set('sysadminlogin', 1);
        $policy = "歡迎你蒞臨「bigwinners.cc大贏家」（以下簡稱本網站），為了讓你能夠安心使用本網站的各項服務與資訊，特此向你說明本網站的隱私權保護政策，以保障你的權益，請你詳閱下列內容：

        一、隱私權保護政策的適用範圍
        
        隱私權保護政策內容，包括本網站如何處理在你使用網站服務時收集到的個人識別資料。隱私權保護政策不適用於本網站以外的相關連結網站，也不適用於非本網站所委託或參與管理的人員。
        
        二、個人資料的蒐集、處理及利用方式
        
        當你造訪本網站或使用本網站所提供之功能服務時，我們將視該服務功能性質，請你提供必要的個人資料，並在該特定目的範圍內處理及利用你的個人資料；非經你書面同意，本網站不會將個人資料用於其他用途。
        本網站在你使用服務信箱、問卷調查等互動性功能時，會保留你所提供的姓名、電子郵件地址、聯絡方式及使用時間等。
        於一般瀏覽時，伺服器會自行記錄相關行徑，包括你使用連線設備的IP位址、使用時間、使用的瀏覽器、瀏覽及點選資料記錄等，做為我們增進網站服務的參考依據，此記錄為內部應用，決不對外公佈。
        為提供精確的服務，我們會將收集的問卷調查內容進行統計與分析，分析結果之統計數據或說明文字呈現，除供內部研究外，我們會視需要公佈統計數據及說明文字，但不涉及特定個人之資料。
        
        三、資料之保護
        
        本網站主機均設有防火牆、防毒系統等相關的各項資訊安全設備及必要的安全防護措施，加以保護網站及你的個人資料採用嚴格的保護措施，只由經過授權的人員才能接觸你的個人資料，相關處理人員皆簽有保密合約，如有違反保密義務者，將會受到相關的法律處分。
        如因業務需要有必要委託其他單位提供服務時，本網站亦會嚴格要求其遵守保密義務，並且採取必要檢查程序以確定其將確實遵守。
        
        四、網站對外的相關連結
        
        本網站的網頁提供其他網站的網路連結，你也可經由本網站所提供的連結，點選進入其他網站。但該連結網站不適用本網站的隱私權保護政策，你必須參考該連結網站中的隱私權保護政策。
        
        五、與第三人共用個人資料之政策
        
        本網站絕不會提供、交換、出租或出售任何你的個人資料給其他個人、團體、私人企業或公務機關，但有法律依據或合約義務者，不在此限。
        
        前項但書之情形包括不限於：
        
        經由你書面同意。
        法律明文規定。
        為免除你生命、身體、自由或財產上之危險。
        與公務機關或學術研究機構合作，基於公共利益為統計或學術研究而有必要，且資料經過提供者處理或蒐集著依其揭露方式無從識別特定之當事人。
        當你在網站的行為，違反服務條款或可能損害或妨礙網站與其他使用者權益或導致任何人遭受損害時，經網站管理單位研析揭露你的個人資料是為了辨識、聯絡或採取法律行動所必要者。
        有利於你的權益。
        本網站委託廠商協助蒐集、處理或利用你的個人資料時，將對委外廠商或個人善盡監督管理之責。
        六、COOKIE之使用
        
        為了提供你最佳的服務，本網站會在你的電腦中放置並取用我們的COOKIE，若你不願接受COOKIE的寫入，你可在你使用的瀏覽器功能項中設定隱私權等級為高，即可拒絕COOKIE的寫入，但可能會導至網站某些功能無法正常執行。
        
        七、隱私權保護政策之修正
        
        本網站隱私權保護政策將因應需求隨時進行修正，修正後的條款將刊登於網站上。";
        $this->view->assign('policy', $policy);
        return $this->view->fetch();
    }
    
    public function service()
    {
        // Cookie::set('sysadminlogin', 1);
        return $this->view->fetch();
    }
    
    public function notifylistlayer()
    {
        if ($this->auth->isLogin()) {
            $mUsernotify = model('Usernotify')
            ->where("user_id = ".$this->auth->id." AND `read` = 0")->order('id','desc')->select();
            if($mUsernotify){
                foreach($mUsernotify as $v){
                    $v->read = 1;
                    $v->save();
                }
            }
        }else{
            $mUsernotify = false;
        }
        $this->view->assign('mUsernotify', $mUsernotify);
        return $this->view->fetch('common/notifylistlayer');
    }

    public function teach()
    {
        
        return $this->view->fetch();
    }
    
    public function teach_detail($id = null)
    {
        $mGodarticle = null;
        // $mGodarticle = model('Godarticle')->alias('a')
        // ->join("user u","(u.id = a.user_id AND u.status = 1) OR a.user_id = 0 ")
        // ->field('a.*, u.nickname, u.avatar')
        // ->where("a.status = 1 AND a.id = ".$id)->find();

        if(!$mGodarticle){
            $this->redirect('/index/teach');
        }
        return $this->view->fetch();
    }

    public function betList()
    {
        return $this->view->fetch();
    }
    
    public function review()
    {
        return $this->view->fetch();
    }
    
    public function cityInfo()
    {
        return $this->view->fetch();
    }
}
