<?php

namespace app\index\controller;

use addons\wechat\model\WechatCaptcha;
use app\common\controller\Frontend;
use app\common\library\Ems;
use app\common\library\Sms;
use app\common\model\Attachment;
use think\Config;
use think\Cookie;
use think\Hook;
use think\Session;
use think\Validate;
use think\Log;

/**
 * 會員 需要登入的頁面
 */
class User extends Frontend
{
    protected $layout = 'base';
    protected $noNeedLogin = ['login', 'register', 'third'];
    protected $noNeedRight = ['*'];
    protected $hassidenav = true;

    public function _initialize()
    {
        parent::_initialize();
        $auth = $this->auth;

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }

        //监听注册登录退出的事件
        Hook::add('user_login_successed', function ($user) use ($auth) {
            $expire = input('post.keeplogin') ? 30 * 86400 : 0;
            Cookie::set('uid', $user->id, $expire);
            Cookie::set('token', $auth->getToken(), $expire);
        });
        Hook::add('user_register_successed', function ($user) use ($auth) {
            Cookie::set('uid', $user->id);
            Cookie::set('token', $auth->getToken());
        });
        Hook::add('user_delete_successed', function ($user) use ($auth) {
            Cookie::delete('uid');
            Cookie::delete('token');
        });
        Hook::add('user_logout_successed', function ($user) use ($auth) {
            Cookie::delete('uid');
            Cookie::delete('token');
        });
    }

    public function index()
    {
        $this->redirect(url('/index/user/profile')); 
    }
    
    public function myhome()
    {
        $this->view->assign('title', '個人首頁');
        return $this->view->fetch();
    }

    public function analysttitle()
    {
        $mEventcategory = false;
        $checkAT = false;
        $mAnalyst = model('Analyst')->where('user_id = '.$this->auth->id)->find();
        if($mAnalyst){
            $mEventcategory = model("Eventcategory")->where("status = 1")->order("id")->select();
            if($mEventcategory){
                foreach($mEventcategory as $v){
                    $v->mAnalysttitle = model("Analysttitle")->alias('at')
                    ->join("analyst_to_titletype att","att.ecid = ".$v->id." AND att.analyst_id = ".$mAnalyst->id." AND att.titletype = at.type","LEFT")
                    ->field('at.*, att.id as attid')
                    ->where("at.ecid = ".$v->id." AND at.analyst_id = ".$mAnalyst->id)->order("at.type","asc")->select();
                    if($v->mAnalysttitle){
                        $checkAT = true;
                    }
                }
            }
        }
        $this->view->assign('mEventcategory', $mEventcategory);
        $this->view->assign('mAnalyst', $mAnalyst);
        $this->view->assign('checkAT', $checkAT);
        return $this->view->fetch();
    }
    
    public function notify($page = 1)
    {
        $mUsernotify = model('Usernotify')
        ->where("user_id = ".$this->auth->id." ")->order('id','desc')->paginate(20, false, $this->paginate_config);
        $count = $mUsernotify->total();
        $pagelist = $mUsernotify->render();
        
        $this->view->assign('count', $count);
        $this->view->assign('page', $page);
        $this->view->assign('pagelist', $pagelist);
        $this->view->assign('mUsernotify', $mUsernotify);

        $this->view->assign('title', '通知');
        return $this->view->fetch();
    }
    
    public function favorites($page = 1)
    {
        $mArticle = model('Article')->alias('a')
        ->join("article_fav af","af.article_id = a.id AND af.type = 1 AND af.user_id = ".$this->auth->id)
        ->join("user u","(u.id = a.user_id AND u.status = 1) OR a.user_id = 0 ")
        ->join("article_cat ac","ac.id = a.cat_id AND ac.status = 1")
        ->join("article_msg am","a.id = am.article_id AND am.status = 1","LEFT")
        ->field('a.*, ac.cat_name, u.nickname, u.avatar, count(am.id) as msg_count')
        ->where("a.status = 1 ")->order('a.updatetime','desc')->group('a.id')->paginate(15);
        
        if($mArticle){
            foreach($mArticle as $v){
                if(!$v->avatar) $v->avatar = $this->def_avatar;
                if($v->user_id == 0){
                    $v->nickname = "管理員";
                    $v->avatar = model('User')->getAvatarAttr('');
                }else{
                    $v->avatar = model('User')->getAvatarAttr($v->avatar);
                }
            }
        }

        $count = $mArticle->total();
        $pagelist = $mArticle->render();
        
        $this->view->assign('count', $count);
        $this->view->assign('page', $page);
        $this->view->assign('pagelist', $pagelist);
        $this->view->assign('mArticle', $mArticle);

        $this->view->assign('title', '收藏的文章');
        return $this->view->fetch();
    }
    
    public function favoritesg($page = 1)
    {
        $mArticle = model('Godarticle')->alias('a')
        ->join("article_fav af","af.article_id = a.id AND af.type = 2 AND af.user_id = ".$this->auth->id)
        ->join("user u","(u.id = a.user_id AND u.status = 1) OR a.user_id = 0 ")
        ->join("article_cat ac","ac.id = a.cat_id AND ac.status = 1")
        ->field('a.*, ac.cat_name, u.nickname, u.avatar')
        ->where("a.status = 1 ")->order('a.updatetime','desc')->group('a.id')->paginate(15);
        
        if($mArticle){
            foreach($mArticle as $v){
                if(!$v->avatar) $v->avatar = $this->def_avatar;
            }
        }

        $count = $mArticle->total();
        $pagelist = $mArticle->render();
        
        $this->view->assign('count', $count);
        $this->view->assign('page', $page);
        $this->view->assign('pagelist', $pagelist);
        $this->view->assign('mArticle', $mArticle);

        $this->view->assign('title', '收藏的專欄');
        return $this->view->fetch();
    }
    
    public function article($page = 1)
    {
        $mArticle = model('Article')->alias('a')
        ->join("user u","u.id = a.user_id ")
        ->join("article_cat ac","ac.id = a.cat_id AND ac.status = 1")
        ->join("article_msg am","a.id = am.article_id AND am.status = 1","LEFT")
        ->field('a.*, ac.cat_name, u.nickname, u.avatar, count(am.id) as msg_count')
        ->where("a.status <> 0 AND a.user_id = ".$this->auth->id)->order('a.updatetime','desc')->group('a.id')->paginate(15);
        
        if($mArticle){
            foreach($mArticle as $v){
                if($v->user_id == 0){
                    $v->nickname = "管理員";
                    $v->avatar = model('User')->getAvatarAttr('');
                }else{
                    $v->avatar = model('User')->getAvatarAttr($v->avatar);
                }
                if($v->status == 0){
                    $v->status_str = "<span class='text-gray'>隱藏</span>";
                }elseif($v->status == 2){
                    $v->status_str = "<span class='text-gray'>已刪除</span>";
                }else{
                    $v->status_str = "";
                }
            }
        }

        $count = $mArticle->total();
        $pagelist = $mArticle->render();
        
        $this->view->assign('count', $count);
        $this->view->assign('page', $page);
        $this->view->assign('pagelist', $pagelist);
        $this->view->assign('mArticle', $mArticle);

        $this->view->assign('title', '發表的文章');
        return $this->view->fetch();
    }
    
    public function godarticle($page = 1)
    {
        $mArticle = model('Godarticle')->alias('a')
        ->join("user u","u.id = a.user_id ")
        ->join("article_cat ac","ac.id = a.cat_id AND ac.status = 1")
        ->field('a.*, ac.cat_name, u.nickname, u.avatar')
        ->where("a.user_id = ".$this->auth->id)->order('a.updatetime','desc')->group('a.id')->paginate(15);
        
        if($mArticle){
            foreach($mArticle as $v){
                if(!$v->avatar) $v->avatar = $this->def_avatar;
                
                if($v->status == 0){
                    $v->status_str = "<span class='text-orange'>審核中</span>";
                }elseif($v->status == 2){
                    if($v->reason){
                        $reason = "<br><span class='text-info'>".$v->reason."</span>";
                    }else{
                        $reason = "";
                    }
                    $v->status_str = "<span class='text-danger'>拒絕刊登".$reason."</span>";
                }elseif($v->status == 3){
                    $v->status_str = "<span class='text-gray'>已刪除</span>";
                }else{
                    $v->status_str = "";
                }
            }
        }

        $count = $mArticle->total();
        $pagelist = $mArticle->render();
        
        $this->view->assign('count', $count);
        $this->view->assign('page', $page);
        $this->view->assign('pagelist', $pagelist);
        $this->view->assign('mArticle', $mArticle);

        $this->view->assign('title', '發表的專欄');
        return $this->view->fetch();
    }

    //刊登神人專欄
    public function addgodarticle()
    {
        $catlist = [
            '0' => '請選擇分類'
        ];
        $mArticlecat = model('Articlecat')->where("type = 1 AND status = 1")->order("weigh")->select();
        if($mArticlecat){
            foreach($mArticlecat as $v){
                $catlist[$v->id] = $v->cat_name;
            }
        }
        $this->view->assign('catlist', $catlist);
        return $this->view->fetch();
    }

    //發表文章
    public function addarticle($cid = 0)
    {
        $catlist = [
            '0' => '請選擇分類'
        ];
        $mArticlecat = model('Articlecat')->where("type = 1 AND status = 1")->order("weigh")->select();
        if($mArticlecat){
            foreach($mArticlecat as $v){
                $catlist[$v->id] = $v->cat_name;
            }
        }

        $this->view->assign('catlist', $catlist);
        $this->view->assign('cid', $cid);
        return $this->view->fetch();
    }

    //編輯文章
    public function editarticle($id = 0)
    {
        $mArticle = model('Article')->where("id = ".$id." AND status = 1 AND user_id = ".$this->auth->id)->find();
        if(!$mArticle){
            $this->redirect('/index/article');
        }
        
        $catlist = [
            '0' => '請選擇分類'
        ];
        $mArticlecat = model('Articlecat')->where("type = 1 AND status = 1")->order("weigh")->select();
        if($mArticlecat){
            foreach($mArticlecat as $v){
                $catlist[$v->id] = $v->cat_name;
            }
        }

        $this->view->assign('mArticle', $mArticle);
        $this->view->assign('catlist', $catlist);
        return $this->view->fetch();
    }

    public function editgodarticle($id = 0)
    {
        $mArticle = model('Godarticle')->where("id = ".$id." AND status = 1 AND user_id = ".$this->auth->id)->find();
        if(!$mArticle){
            $this->redirect('/index/godarticle');
        }
        
        $catlist = [
            '0' => '請選擇分類'
        ];
        $mArticlecat = model('Articlecat')->where("type = 1 AND status = 1")->order("weigh")->select();
        if($mArticlecat){
            foreach($mArticlecat as $v){
                $catlist[$v->id] = $v->cat_name;
            }
        }

        $this->view->assign('mArticle', $mArticle);
        $this->view->assign('catlist', $catlist);
        return $this->view->fetch();
    }

    
    
    public function buypoint()
    {
        $mPointitem = model('Pointitem')->order('point','asc')->select();
        $this->view->assign('mPointitem', $mPointitem);
        $this->view->assign('title', '儲值點數');
        return $this->view->fetch();
    }
    
    public function pred()
    {
        $sdate = $this->request->request('sdate', strtotime(date('Y-m-d')));
        $edate = strtotime(date('Y-m-d', $sdate).' +1 day');
        $this->view->assign('sdate', $sdate);

        
        // $mEventcategory = model('Eventcategory')->where('status = 1')->find();
        $mEventcategory = model('Eventcategory')->alias('ec')
        ->join("event e","e.event_category_id = ec.id AND e.starttime > ".$sdate." AND e.starttime < ".$edate)
        ->distinct(true)
        ->field("ec.*, e.id as e_id")
        ->where("ec.status = 1")->order('ec.id')->group('ec.id')->find();
        $eid = 0;
        if($mEventcategory) $eid = $mEventcategory->id;
        $cat_id = $this->request->request('cat', $eid);
        $this->view->assign('cat_id', $cat_id);
        
        // $mEventcategory = model('Eventcategory')->where('status = 1')->select();
        $mEventcategory = model('Eventcategory')->alias('ec')
        ->join("event e","e.event_category_id = ec.id AND e.starttime > ".$sdate." AND e.starttime < ".$edate, "LEFT")
        ->distinct(true)
        ->field("ec.*, e.id as e_id")
        ->where("ec.status = 1")->order('ec.id')->group('ec.id')->select();
        $this->view->assign('mEventcategory', $mEventcategory);
        
        $datelist = [];
        $weekStr =  ['日', '一', '二', '三', '四', '五', '六'];
        $time = strtotime(date('Y-m-d'));
        $datelist[$time] = date('m/d', $time).'&nbsp;('.$weekStr[date('w', $time)].')';
        $time = strtotime(date('Y-m-d').' +1 day');
        $datelist[$time] = date('m/d', $time).'&nbsp;('.$weekStr[date('w', $time)].')';
        $this->view->assign('datelist', $datelist);

        $mAnalyst = model('Analyst')->where('user_id = '.$this->auth->id)->find();
        if(!$mAnalyst){
            $params = [
                'user_id' => $this->auth->id,
                'analyst_name' => $this->auth->nickname,
                'avatar' => $this->auth->avatar,
                'status' => 1,
                'admin_id' => 0,
                'autopred' => 0,
                'free' => 1,
            ];
            $mAnalyst = model('Analyst')::create($params);
        }
        $this->view->assign('analyst_id', $mAnalyst->id);

        $predBtn = false;
        $checktime = false;
        $mEvent = model('Event')->where("starttime > ".$sdate." AND starttime < ".$edate." AND event_category_id = ".$cat_id)->select();
        if($mEvent){
            foreach($mEvent as $v){
                $v->score_str = "<span class='text-info'>".$v->guests_score."&nbsp;</span><br><span class='text-info'>".$v->master_score."&nbsp;</span>";
                $v->event_str = "<span class=''>".$v->guests."</span><br><span class='text-info'>".$v->master."</span><span class='text-danger'>(主)</span>";
                
                if($v->starttime > time()){
                    $checktime = true;
                    $v->eventstatus = "<span class='text-gray'>未開賽</span>";
                    if($v->guests_refund != ''){
                        $v->refund = "<label data-id='refund_".$v->id."' for='refund_0_".$v->id."'>客場 <span class='text-info'>".$v->guests_refund."</span></label><br><label data-id='refund_".$v->id."' for='refund_1_".$v->id."'>主場 </label>";
                    }else{
                        $v->refund = "<label data-id='refund_".$v->id."' for='refund_0_".$v->id."'>客場 </label><br><label data-id='refund_".$v->id."' for='refund_1_".$v->id."'>主場 <span class='text-info'>".$v->master_refund."</span></label>";
                    }
                    $v->refund .= "<input type='radio' id='refund_0_".$v->id."' name='refund[".$v->id."]' value='0'><input type='radio' id='refund_1_".$v->id."' name='refund[".$v->id."]' value='1'>";
                    $v->refund = "<div class='pred_radio'>".$v->refund."</div>";
                    
                    $v->bigs = "<label data-id='bigs_".$v->id."' for='bigs_1_".$v->id."'>大分 <span class='text-info'>".$v->bigscore."</span></label><br><label data-id='bigs_".$v->id."' for='bigs_0_".$v->id."'>小分 </label>";
                    $v->bigs .= "<input type='radio' id='bigs_1_".$v->id."' name='bigs[".$v->id."]' value='1'><input type='radio' id='bigs_0_".$v->id."' name='bigs[".$v->id."]' value='0'>";
                    $v->bigs = "<div class='pred_radio'>".$v->bigs."</div>";    
                }else{
                    $v->eventstatus = "<span class='text-orange'>已開賽</span>";
                    if($v->guests_refund != ''){
                        $v->refund = "客場 <span class='text-info'>".$v->guests_refund."</span><br>主場";
                    }else{
                        $v->refund = "客場 <br>主場 <span class='text-info'>".$v->master_refund."</span>";
                    }
                    $v->bigs = "大分 <span class='text-info'>".$v->bigscore."</span><br>小分";
                }

                $v->pred_str = "";
                $v->comply_str = "";
            }
        }
        if($checktime){
            $predBtn = true;
        }
        $this->view->assign('mEvent', $mEvent);
        $this->view->assign('predBtn', $predBtn);

        $this->view->assign('title', '我要預測');
        return $this->view->fetch();
    }

    /**
     * 注册会员
     */
    public function register()
    {
        $url = $this->request->request('url', '');
        if ($this->auth->id) {
            $this->success('你已經登入', $url ? $url : url('user/profile'));
        }
        if ($this->request->isPost()) {
            $agree = $this->request->post('agree',0);
            if($agree != 1){
                $this->error('請同意 服務條款與隱私權政策');
            }
            $username = $this->request->post('username','');
            $nickname = $this->request->post('nickname','');
            $password = $this->request->post('password','');
            $email = $this->request->post('email', '');
            $mobile = $this->request->post('mobile', '');
            $captcha = $this->request->post('captcha');
            $token = $this->request->post('__token__');
            $rule = [
                'username'  => 'require|length:2,10',
                'nickname'  => 'require|length:2,8',
                'password'  => 'require|length:6,16',
                'mobile'    => 'require|regex:/^09\d{2}-?\d{3}-?\d{3}$/',
                'email'  => 'require|email',
                '__token__' => 'require|token',
            ];

            $msg = [
                'username.require' => '帳號為必填選項',
                'username.length'  => '帳號必須是2~10個字元',
                'nickname.require' => '暱稱為必填選項',
                'nickname.length'  => '暱稱必須是2~8個字元',
                'password.require' => '密碼為必填選項',
                'password.length'  => '密碼必須是6~16個字元',
                'mobile.require' => '手機為必填選項',
                'mobile.regex'  => '手機格式無效',
                'email.require' => '信箱為必填選項',
                'email.email'  => '信箱格式無效',
            ];
            $data = [
                'username'  => $username,
                'nickname'  => $nickname,
                'password'  => $password,
                'mobile'    => $mobile,
                'email'    => $email,
                '__token__' => $token,
            ];
            //验证码
            $captchaResult = true;
            $captchaType = config("fastadmin.user_register_captcha");
            if ($captchaType) {
                if ($captchaType == 'mobile') {
                    $captchaResult = Sms::check($mobile, $captcha, 'register');
                } elseif ($captchaType == 'email') {
                    $captchaResult = Ems::check($email, $captcha, 'register');
                } elseif ($captchaType == 'wechat') {
                    $captchaResult = WechatCaptcha::check($captcha, 'register');
                } elseif ($captchaType == 'text') {
                    $captchaResult = \think\Validate::is($captcha, 'captcha');
                }
            }
            if (!$captchaResult) {
                $this->error(__('Captcha is incorrect'));
            }
            $validate = new Validate($rule, $msg);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
            }
            if ($this->auth->register($username, $nickname, $password, $email, $mobile)) {
                $this->success(__('Sign up successful'), $url ? $url : url('/index/user/profile'));
            } else {
                $this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
            }
        }
        //判断来源
        $referer = $this->request->server('HTTP_REFERER');
        if (!$url && (strtolower(parse_url($referer, PHP_URL_HOST)) == strtolower($this->request->host()))
            && !preg_match("/(user\/login|user\/register|user\/logout)/i", $referer)) {
            $url = $referer;
        }
        $this->view->assign('captchaType', config('fastadmin.user_register_captcha'));
        $this->view->assign('url', $url);
        $this->view->assign('title', __('Register'));
        return $this->view->fetch();
    }

    /**
     * 会员登录
     */
    public function login()
    {
        $url = $this->request->request('url', '');
        if ($this->auth->id) {
            $this->success('你已經登入', $url ? $url : url('user/profile'));
        }
        if ($this->request->isPost()) {
            $account = $this->request->post('account', '');
            $password = $this->request->post('password', '');
            $keeplogin = (int)$this->request->post('keeplogin');
            $token = $this->request->post('__token__');
            $rule = [
                'account'   => 'require',
                'password'  => 'require',
                '__token__' => 'require|token',
            ];

            $msg = [
                'account.require'  => '帳號不能為空',
                'password.require' => '密碼不能為空',
            ];
            $data = [
                'account'   => $account,
                'password'  => $password,
                '__token__' => $token,
            ];
            $validate = new Validate($rule, $msg);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
                return false;
            }
            if ($this->auth->login($account, $password)) {
                $this->success(__('Logged in successful'), $url ? $url : url('/index/user/profile'));
            } else {
                $this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
            }
        }
        
        //判断来源
        $referer = $this->request->server('HTTP_REFERER');
        if (!$url && (strtolower(parse_url($referer, PHP_URL_HOST)) == strtolower($this->request->host()))
            && !preg_match("/(user\/login|user\/register|user\/logout)/i", $referer)) {
            $url = $referer;
        }
        $this->view->assign('url', $url);
        $this->view->assign('title', __('Login'));
        return $this->view->fetch();
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        //退出本站
        $this->auth->logout();
        $this->success(__('Logout successful'), url('user/index'));
    }

    /**
     * 个人信息
     */
    public function profile()
    {
        $this->view->assign('title', __('Profile'));
        return $this->view->fetch();
    }

    /**
     * 修改密码
     */
    public function changepwd()
    {
        if ($this->request->isPost()) {
            $oldpassword = $this->request->post("oldpassword");
            $newpassword = $this->request->post("newpassword");
            $renewpassword = $this->request->post("renewpassword");
            $token = $this->request->post('__token__');
            $rule = [
                'oldpassword'   => 'require|length:6,30',
                'newpassword'   => 'require|length:6,30',
                'renewpassword' => 'require|length:6,30|confirm:newpassword',
                '__token__'     => 'token',
            ];

            $msg = [
                'renewpassword.confirm' => __('Password and confirm password don\'t match')
            ];
            $data = [
                'oldpassword'   => $oldpassword,
                'newpassword'   => $newpassword,
                'renewpassword' => $renewpassword,
                '__token__'     => $token,
            ];
            $field = [
                'oldpassword'   => __('Old password'),
                'newpassword'   => __('New password'),
                'renewpassword' => __('Renew password')
            ];
            $validate = new Validate($rule, $msg, $field);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
                return false;
            }

            $ret = $this->auth->changepwd($newpassword, $oldpassword);
            if ($ret) {
                $this->success(__('Reset password successful'), url('user/login'));
            } else {
                $this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
            }
        }
        $this->view->assign('title', __('Change password'));
        return $this->view->fetch();
    }

    public function attachment()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $mimetypeQuery = [];
            $filter = $this->request->request('filter');
            $filterArr = (array)json_decode($filter, true);
            if (isset($filterArr['mimetype']) && preg_match("/[]\,|\*]/", $filterArr['mimetype'])) {
                $this->request->get(['filter' => json_encode(array_diff_key($filterArr, ['mimetype' => '']))]);
                $mimetypeQuery = function ($query) use ($filterArr) {
                    $mimetypeArr = explode(',', $filterArr['mimetype']);
                    foreach ($mimetypeArr as $index => $item) {
                        if (stripos($item, "/*") !== false) {
                            $query->whereOr('mimetype', 'like', str_replace("/*", "/", $item) . '%');
                        } else {
                            $query->whereOr('mimetype', 'like', '%' . $item . '%');
                        }
                    }
                };
            }
            $model = new Attachment();
            $offset = $this->request->get("offset", 0);
            $limit = $this->request->get("limit", 0);
            $total = $model
                ->where($mimetypeQuery)
                ->where('user_id', $this->auth->id)
                ->order("id", "DESC")
                ->count();

            $list = $model
                ->where($mimetypeQuery)
                ->where('user_id', $this->auth->id)
                ->order("id", "DESC")
                ->limit($offset, $limit)
                ->select();
            $cdnurl = preg_replace("/\/(\w+)\.php$/i", '', $this->request->root());
            foreach ($list as $k => &$v) {
                $v['fullurl'] = ($v['storage'] == 'local' ? $cdnurl : $this->view->config['upload']['cdnurl']) . $v['url'];
            }
            unset($v);
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->view->assign("mimetypeList", \app\common\model\Attachment::getMimetypeList());
        return $this->view->fetch();
    }
}
