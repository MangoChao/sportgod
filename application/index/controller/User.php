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
    
    public function notify()
    {
        $this->view->assign('title', '通知');
        return $this->view->fetch();
    }
    
    public function favorites()
    {
        $this->view->assign('title', '收藏');
        return $this->view->fetch();
    }
    
    public function article()
    {
        $this->view->assign('title', '文章');
        return $this->view->fetch();
    }

    
    //發表文章
    public function addarticle($cat = 0)
    {

        $catlist = [
            '0' => '請選擇分類'
        ];
        $mArticlecat = model('Articlecat')->where("type = 1 AND status = 1")->order("weigh")->select();
        if($Articlecat){
            foreach($Articlecat as $v){
                $catlist[$v->id] = $v->cat_name;
            }
        }

        $this->view->assign('catlist', $catlist);
        $this->view->assign('cat', $cat);
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
            $username = $this->request->post('username','');
            $nickname = $this->request->post('nickname','');
            $password = $this->request->post('password','');
            $email = $this->request->post('email', '');
            $mobile = $this->request->post('mobile', '');
            $captcha = $this->request->post('captcha');
            $token = $this->request->post('__token__');
            $rule = [
                'username'  => 'require|length:2,10',
                'nickname'  => 'require|length:2,10',
                'password'  => 'require|length:6,16',
                'mobile'    => 'require|regex:/^09\d{2}-?\d{3}-?\d{3}$/',
                'email'  => 'require|email',
                '__token__' => 'require|token',
            ];

            $msg = [
                'username.require' => '帳號為必填選項',
                'username.length'  => '帳號必須是2~10個字元',
                'nickname.require' => '暱稱為必填選項',
                'nickname.length'  => '暱稱必須是2~10個字元',
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
