<?php

namespace app\common\controller;

use app\common\library\Auth;
use think\Config;
use think\Controller;
use think\Hook;
use think\Lang;
use think\Log;
use think\Loader;
use think\Validate;
use think\Cookie;

/**
 * 前台控制器基类
 */
class Frontend extends Controller
{

    /**
     * 布局模板
     * @var string
     */
    protected $layout = '';

    /**
     * 无需登录的方法,同时也就不需要鉴权了
     * @var array
     */
    protected $noNeedLogin = [];

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = [];

    protected $hassidenav = false;

    /**
     * 权限Auth
     * @var Auth
     */
    protected $auth = null;

    protected $site_url = null;
    protected $def_avatar = "";
    protected $paginate_config = [];
    protected $check_sysadminlogin = true;

    public function _initialize()
    {
        
        // if($this->check_sysadminlogin AND !Cookie::has('sysadminlogin')){
        //     exit;
        // }

        //移除HTML标签
        $this->request->filter('trim,strip_tags,htmlspecialchars');
        $modulename = $this->request->module();
        $controllername = Loader::parseName($this->request->controller());
        $actionname = strtolower($this->request->action());

        // 如果有使用模板布局
        if ($this->layout) {
            $this->view->engine->layout('layout/' . $this->layout);
        }
        $this->auth = Auth::instance();

        // token
        $token = $this->request->server('HTTP_TOKEN', $this->request->request('token', \think\Cookie::get('token')));

        $path = str_replace('.', '/', $controllername) . '/' . $actionname;
        // 设置当前请求的URI
        $this->auth->setRequestUri($path);
        
        // 检测是否需要验证登录
        if (!$this->auth->match($this->noNeedLogin)) {
            //初始化
            $this->auth->init($token);
            //检测是否登录
            if (!$this->auth->isLogin()) {
                $this->error(__('Please login first'), 'index/user/login');
            }
            // 判断是否需要验证权限
            if (!$this->auth->match($this->noNeedRight)) {
                // 判断控制器和方法判断是否有对应权限
                if (!$this->auth->check($path)) {
                    $this->error(__('You have no permission'));
                }
            }
        } else {
            // 如果有传递token才验证是否登录状态
            if ($token) {
                $this->auth->init($token);
            }
        }

        $this->view->assign('user', $this->auth->getUser());

        // 语言检测
        $lang = strip_tags($this->request->langset());

        $site = Config::get("site");
        $this->site_url = $site['url'];
        $this->paginate_config = [
            'query' => $this->request->get()
        ];

        $upload = \app\common\model\Config::upload();

        // 上传信息配置后
        Hook::listen("upload_config_init", $upload);

        $suid = $this->request->get('suid','----');
        // 配置信息
        $config = [
            'site'           => array_intersect_key($site, array_flip(['name', 'cdnurl', 'version', 'timezone', 'languages', 'liffid'])),
            'upload'         => $upload,
            'modulename'     => $modulename,
            'controllername' => $controllername,
            'actionname'     => $actionname,
            'jsname'         => 'frontend/' . str_replace('.', '/', $controllername),
            'moduleurl'      => rtrim(url("/{$modulename}", '', false), '/'),
            'language'       => $lang,
            'url'            => $this->site_url,
            'suid'            => $suid
        ];
        $config = array_merge($config, Config::get("view_replace_str"));

        Config::set('upload', array_merge(Config::get('upload'), $upload));

        $channel_access_token = Config::get("site.line_channel_access_token");
        $this->LineBot = new LineBot($channel_access_token);

        $this->assignArticlecat();
        $this->checkArticleread();

        $this->def_avatar = "/assets/img/def_avatar.jpg";
        // 配置信息后
        Hook::listen("config_init", $config);
        // 加载当前控制器语言包
        $this->loadlang($controllername);
        $this->assign('site', $site);
        $this->assign('site_url', $this->site_url);
        $this->assign('config', $config);
        $this->assign('title', $site['name']);
        $this->assign('description', '賽事直播網帶給你全球各樣賽事的精采比賽，包含美國職棒大聯盟（MLB）、日本職棒大賽（NPB）、中華職棒（CPBL）、高中籃球聯賽（HBL）、美國職籃（NBA）、英超、西甲、德甲、法甲、意甲、五大聯賽、歐洲盃、世界盃');
        $this->assign('keywords', '直播,體育,運動,奧運,東澳,2020東京奧運,籃球,棒球,足球,網球,桌球');
    }

    /**
     * 加载语言文件
     * @param string $name
     */
    protected function loadlang($name)
    {
        $name =  Loader::parseName($name);
        Lang::load(APP_PATH . $this->request->module() . '/lang/' . $this->request->langset() . '/' . str_replace('.', '/', $name) . '.php');
    }

    /**
     * 渲染配置信息
     * @param mixed $name  键名或数组
     * @param mixed $value 值
     */
    protected function assignconfig($name, $value = '')
    {
        $this->view->config = array_merge($this->view->config ? $this->view->config : [], is_array($name) ? $name : [$name => $value]);
    }

    /**
     * 刷新Token
     */
    protected function token()
    {
        $token = $this->request->param('__token__');

        //验证Token
        if (!Validate::make()->check(['__token__' => $token], ['__token__' => 'require|token'])) {
            $this->error(__('Token verification error'), '', ['__token__' => $this->request->token()]);
        }

        //刷新Token
        $this->request->token();
    }

    
    protected function assignArticlecat()
    {
        $mArticlecat = model('Articlecat')->where('status = 1')->order('weigh')->select();
        $this->assign('mArticlecat', $mArticlecat);
    }

    protected function checkArticleread()
    {
        if ($this->auth->isLogin()) {
            $isnotify = model('Usernotify')
            ->where("user_id = ".$this->auth->id." AND `read` = 0")->count();
        }else{
            $isnotify = 0;
        }
        $this->assign('isnotify', $isnotify);
    }
    
    protected function changePoint($id, $point, $memo = '')
    {
        $mUser = model('User')->get($id);
        if($mUser){
            $amount = $point;
            $before = $mUser->point;
            $after = $mUser->point + $amount;

            $p_params = [
                'user_id' => $mUser->id,
                'amount' => $amount,
                'before' => $before,
                'after' => $after,
                'memo' => $memo,
                'admin_id' => $mUser->admin_id,
            ];
            model('Pointlog')::create($p_params);

            $mUser->point = $after;
            $mUser->save();
            Log::notice("[".__METHOD__."] 更動點數成功 pointlog參數:".json_encode($p_params));
        }else{
            Log::notice("[".__METHOD__."] 查無用戶 id:".$id);
        }
    }

}
