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
    
    //藍新資訊 
    //測試
    protected $newebpay_url_t = 'https://ccore.newebpay.com/MPG/mpg_gateway';
    protected $newebpay_MerchantID_t = 'MS116713868';
    protected $newebpay_HashKey_t = '3YqcriMvCIz0We7pcYYXbZl3b2wcn5Sp';
    protected $newebpay_HashIV_t = 'CfDcKXfy6un3NY2P';
    //正式
    protected $newebpay_url = 'https://core.newebpay.com/MPG/mpg_gateway';
    protected $newebpay_MerchantID = 'MS3629731290';
    protected $newebpay_HashKey = 'VAcfXUQJELhcK8Vy4uh881HaKiaW9ppq';
    protected $newebpay_HashIV = 'PWDwDgz6KNxrgysC';

    public function _initialize()
    {
        
        $this->newebpay_url = $this->newebpay_url_t;
        $this->newebpay_MerchantID = $this->newebpay_MerchantID_t;
        $this->newebpay_HashKey = $this->newebpay_HashKey_t;
        $this->newebpay_HashIV = $this->newebpay_HashIV_t;
        
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
        $this->assignArticleTitle();
        $this->checkArticleread();

        $this->def_avatar = "/assets/img/avatar.png";
        // 配置信息后
        Hook::listen("config_init", $config);
        // 加载当前控制器语言包
        $this->loadlang($controllername);
        $this->assign('site', $site);
        $this->assign('site_url', $this->site_url);
        $this->assign('config', $config);
        $this->assign('title', $site['name']);
        $this->assign('description', '專業玩家運彩分析平台、體育新聞資訊，讓你不用再花時間研究。');
        $this->assign('keywords', '直播,體育,運動,奧運,籃球,棒球,足球,網球,桌球,運彩,運動彩券,百家樂,娛樂城,現金版,信用版');
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
        $baseArticlecat = model('Articlecat')->where('status = 1')->order('weigh')->select();
        $this->assign('baseArticlecat', $baseArticlecat);
    }

    protected function assignArticleTitle()
    {
        $baseAnalysttitle = model("Analysttitle")->alias('at')
        ->join("event_category ec","at.ecid = ec.id")
        ->join("analyst a","at.analyst_id = a.id")
        ->field("at.*, ec.title as etitle, a.analyst_name")
        ->where("a.status = 1")->group("ec.id, at.analyst_id")->limit(3)->orderRaw('RAND()')->select();
        if($baseAnalysttitle){
            foreach($baseAnalysttitle as $at){
                $at->atitle = "";
                $mAT = model("Analysttitle")->alias('at')
                ->join("analyst_to_titletype att","att.ecid = at.ecid AND att.analyst_id = at.analyst_id AND att.titletype = at.type")
                ->field("at.*")
                ->where("at.ecid = ".$at->ecid." AND at.analyst_id = ".$at->analyst_id)->find();
                if(!$mAT){
                    $mAT = model("Analysttitle")->where("ecid = ".$at->ecid." AND analyst_id = ".$at->analyst_id)->order("type","asc")->find();
                }
                if($mAT){
                    $at->atitle = "<span>".$at->analyst_name."</span> ".$at->etitle." ".$mAT->title;
                }
            }
        }
        $this->assign('baseAnalysttitle', $baseAnalysttitle);
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
