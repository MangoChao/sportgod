<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Log;
use think\Config;
use think\Validate;
use fast\Http;

class Liff extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = 'liff';

    public function _initialize()
    {
        parent::_initialize();
        $this->view->assign('title', '9453預測家');
    }

    public function index()
    {
        return $this->view->fetch();
    }
    
    public function pred()
    {

        return $this->view->fetch();
    }
    
    public function getpred()
    {
        $line_user_id = $this->request->request('line_user_id', null);

        $eventcatlist = [];
        $mEventcategory = model('Eventcategory')->where('status = 1')->select();
        if($mEventcategory){
            foreach($mEventcategory as $v){
                $eventcatlist[$v->id] = $v->title;
            }
        }
        $this->view->assign('eventcatlist', $eventcatlist);
        
        $mLotteryimg = model('Lotteryimg')->order("id","desc")->find();
        $this->view->assign('mLotteryimg', $mLotteryimg);
        return $this->view->fetch();
    }

    public function eventlist($cid = 0)
    {
        $table_data_list = [];
        $startdate = date('Y-m-d');
        $starttime = time();
        $starttime_next = strtotime($startdate." +1 day");
        $day = 1;
        do{
            $starttime_fiter = "starttime > ".$starttime." AND starttime < ".$starttime_next;
            if($cid != 0){
                $mEvent = model('Event')->where("event_category_id = ".$cid." AND ".$starttime_fiter)->select();
            }else{
                $mEvent = model('Event')->where(" ".$starttime_fiter)->select();
            }
            if($mEvent){
                foreach($mEvent as $v){
                    $guests_refund_box = '';
                    $master_refund_box = '';
                    if($v->guests_refund != ''){
                        if($v->guests_refund == '0'){
                            $guests_refund_box = '<span class="refund_box">盤口未開</span>';
                        }else{
                            $guests_refund_box = '<span class="refund_box">'.$v->guests_refund.'</span>';
                        }
                    }else{
                        if($v->master_refund == '0'){
                            $master_refund_box = '<span class="refund_box">盤口未開</span>';
                        }else{
                            $master_refund_box = '<span class="refund_box">'.$v->master_refund.'</span>';
                        }
                    }
                    $v->team_str = '<span class="text-black">'.$v->guests.'</span>&nbsp;'.$guests_refund_box.'<br><span class="text-info">'.$v->master.'</span><span class="text-danger">(主)</span>&nbsp;'.$master_refund_box;
                    $v->refund_str = '<span class="text-info">'.$v->guests_refund.'&nbsp;</span><br><span class="text-info">'.$v->master_refund.'&nbsp;</span>';
                    $v->bigscore_str = '<span class="text-info">'.$v->bigscore.'&nbsp;</span><br><span class="text-info">&nbsp;</span>';
                }
                $table_data_list[$startdate] = $mEvent;
            }
            $starttime = $starttime_next;
            $startdate = date("Y-m-d",$starttime);
            $starttime_next = strtotime($startdate." +1 day");
            $day++;
        }while($day <= 5);
        
        $this->view->assign('table_data_list', $table_data_list);
        return $this->view->fetch();
    }

    public function eventinfo()
    {
        $line_user_id = $this->request->request('line_user_id', '----');
        $id = $this->request->request('id', 0);

        $predstr1 = '';
        $predstr2 = '';
        $today = strtotime(date('Y-m-d'));
        $mUser = model('User')->where("status = 1 AND line_user_id = '".$line_user_id."' ")->find();
        if(!$mUser){
            $mUser = model('Userfree')->where("line_user_id = '".$line_user_id."' ")->find();
            if($mUser){
                $mUser->haspred1 = 0;
                $mUser->haspred2 = 0;
                $mUser->isfree = 1; 

                if(!$mUser->get_pred_time OR $mUser->get_pred_time < $today){
                    $mUser->freepred = 1;
                    $mUser->lastpred = 0;
                }else{
                    $mUser->freepred = 0;
                    $mUser->lastpred = 0;
                }
                
                $mUsertopred1 = model('Usertopred')->alias('utp')
                ->join("pred p","p.id = utp.pred_id")
                ->join("analyst a","a.id = p.analyst_id")
                ->join("event e","e.id = p.event_id")
                ->field('p.*, a.analyst_name, e.guests, e.master')
                ->where("utp.userfree_id = ".$mUser->id." AND p.event_id = ".$id." AND p.pred_type = 1 ")->find();
                
                $mUsertopred2 = model('Usertopred')->alias('utp')
                ->join("pred p","p.id = utp.pred_id")
                ->join("analyst a","a.id = p.analyst_id")
                ->join("event e","e.id = p.event_id")
                ->field('p.*, a.analyst_name, e.guests, e.master')
                ->where("utp.userfree_id = ".$mUser->id." AND p.event_id = ".$id." AND p.pred_type = 2 ")->find();

            }else{
                return 0; //錯誤 關閉視窗
            }
        }else{
            $mUser->haspred1 = 0;
            $mUser->haspred2 = 0;
            $mUser->isfree = 0; 

            if(!$mUser->get_pred_time OR $mUser->get_pred_time < $today){
                $mUser->freepred = 1;
            }else{
                $mUser->freepred = 0;
            }
            
            $lastpred = 0;
            if($mUser->ptime1 AND $mUser->ptime2){
                if($mUser->ptime1 <= time() AND time() <= $mUser->ptime2){
                    $lastpred = $mUser->pred2;
                }
            }else{
                $lastpred = $mUser->pred2;
            }
            // $mUser->lastpred = $mUser->pred + $isfree - model('Usertopred')->where("user_id = ".$mUser->id." AND createtime > ".$today)->count();
            $mUser->lastpred = $lastpred;
            
            $mUsertopred1 = model('Usertopred')->alias('utp')
            ->join("pred p","p.id = utp.pred_id")
            ->join("analyst a","a.id = p.analyst_id")
            ->join("event e","e.id = p.event_id")
            ->field('p.*, a.analyst_name, e.guests, e.master')
            ->where("utp.user_id = ".$mUser->id." AND p.event_id = ".$id." AND p.pred_type = 1 ")->find();
            
            
            $mUsertopred2 = model('Usertopred')->alias('utp')
            ->join("pred p","p.id = utp.pred_id")
            ->join("analyst a","a.id = p.analyst_id")
            ->join("event e","e.id = p.event_id")
            ->field('p.*, a.analyst_name, e.guests, e.master')
            ->where("utp.user_id = ".$mUser->id." AND p.event_id = ".$id." AND p.pred_type = 2 ")->find();

        }

        if($mUsertopred1){
            $mUser->haspred1 = 1;
            $predstr1 = "預測盤口時間:&nbsp;".date("Y-m-d H:i", $mUsertopred1->predtime)."<br>";
            $predstr1 .= "分析師:&nbsp;".$mUsertopred1->analyst_name."<br>";
            if($mUsertopred1->guests_refund != ''){
                $refund = $mUsertopred1->guests_refund;
                if(strpos($refund, '-') !== false){
                    $refund = str_replace('-','+',trim($refund));
                }else{
                    $refund = str_replace('+','-',trim($refund));
                }
                $predstr = $mUsertopred1->winteam ? "主 ".$mUsertopred1->master." 受讓 ".$refund:"客 ".$mUsertopred1->guests." 讓分 ".$mUsertopred1->guests_refund;
            }else{
                $refund = $mUsertopred1->master_refund;
                if(strpos($refund, '-') !== false){
                    $refund = str_replace('-','+',trim($refund));
                }else{
                    $refund = str_replace('+','-',trim($refund));
                }
                $predstr = $mUsertopred1->winteam ? "主 ".$mUsertopred1->master." 讓分 ".$mUsertopred1->master_refund:"客 ".$mUsertopred1->guests." 受讓 ".$refund;
            }
            $predstr1 .= "<span class='text-danger'>".$predstr."</span>";
        }

        if($mUsertopred2){
            $mUser->haspred2 = 1;
            $bigscore = $mUsertopred2->bigscore;
            if(strpos($bigscore, '-') !== false){
                $bigscore = str_replace('-','+',trim($bigscore));
            }else{
                $bigscore = str_replace('+','-',trim($bigscore));
            }
            $predstr2 = "預測盤口時間:&nbsp;".date("Y-m-d H:i", $mUsertopred2->predtime)."<br>";
            $predstr2 .= "分析師:&nbsp;".$mUsertopred2->analyst_name."<br>";
            $predstr = $mUsertopred2->bigsmall?"大分 ".$mUsertopred2->bigscore:"小分 ".$bigscore;
            $predstr2 .= "<span class='text-danger'>".$predstr."</span>";
        }

        $mEvent = model('Event')->alias('e')
        ->join("event_category ec","ec.id = e.event_category_id")
        ->field('e.*, ec.title')
        ->where("e.id = ".$id)->find();
        if($mEvent){
            $mEvent->guests_refund_box = '';
            $mEvent->master_refund_box = '';
            if($mEvent->guests_refund != ''){
                if($mEvent->guests_refund == '0'){
                    $mEvent->guests_refund_box = '<span class="refund_box">盤口未開</span>';
                }else{
                    $mEvent->guests_refund_box = '<span class="refund_box">'.$mEvent->guests_refund.'</span>';
                }
            }else{
                if($mEvent->master_refund == '0'){
                    $mEvent->master_refund_box = '<span class="refund_box">盤口未開</span>';
                }else{
                    $mEvent->master_refund_box = '<span class="refund_box">'.$mEvent->master_refund.'</span>';
                }
            }
            if($mEvent->bigscore == '0'){
                $mEvent->bigscore_box = '盤口未開';
            }else{
                $mEvent->bigscore_box = $mEvent->bigscore;
            }
        }

        $this->view->assign('predstr1', $predstr1);
        $this->view->assign('predstr2', $predstr2);
        $this->view->assign('line_user_id', $line_user_id);
        $this->view->assign('mUser', $mUser);
        $this->view->assign('mEvent', $mEvent);
        return $this->view->fetch();
    }
    
    public function rank()
    {
        $mRank = model('Rank')->order("id","desc")->find();
        $mRankcontent = false;
        if($mRank){
            $mRankcontent = model('Rankcontent')->alias('rc')
            ->join("analyst a","a.id = rc.analyst_id")
            ->field("rc.*, a.analyst_name")
            ->where('rc.rank_id = '.$mRank->id)->order('rc.rank','asc')->select();
            if($mRankcontent){

            }
        }
        $this->view->assign('mRank', $mRank);
        $this->view->assign('mRankcontent', $mRankcontent);
        return $this->view->fetch();
    }
    
    public function analystinfo($id = null)
    {
        $mAnalyst = false;
        $mPred = false;
        if($id){
            $mRank = model('Rank')->order("id","desc")->find();
            $mAnalyst = model('Analyst')->where("id = ".$id)->find();
            if($mAnalyst AND $mRank){
                $mPred = model('Pred')->alias('p')
                ->join("event e","e.id = p.event_id")
                ->field("p.*, e.guests, e.master, e.starttime")
                ->where('p.comply <> 0 AND p.analyst_id = '.$mAnalyst->id.' AND e.starttime > '.$mRank->rtime1.' AND e.starttime < '.$mRank->rtime2)->order('e.starttime','desc')->select();
                if($mPred){
                    foreach($mPred as $v){
                        $v->score_str = "<span class='text-info'>".$v->guests_score."&nbsp;</span><br><span class='text-info'>".$v->master_score."&nbsp;</span>";
                        $v->event_str = "<span class=''>".$v->guests."</span><br><span class='text-info'>".$v->master."</span><span class='text-danger'>(主)</span>";
                        if($v->pred_type == 1){
                            if($v->guests_refund != ''){
                                $refund = $v->guests_refund;
                                if(strpos($refund, '-') !== false){
                                    $refund = str_replace('-','+',trim($refund));
                                }else{
                                    $refund = str_replace('+','-',trim($refund));
                                }
                                $v->pred_str = $v->winteam ? "主場&nbsp;受讓<br><span class='text-info'>".$refund."</span>":"客場&nbsp;讓分<br><span class='text-info'>".$v->guests_refund."</span>";
                            }else{
                                $refund = $v->master_refund;
                                if(strpos($refund, '-') !== false){
                                    $refund = str_replace('-','+',trim($refund));
                                }else{
                                    $refund = str_replace('+','-',trim($refund));
                                }
                                $v->pred_str = $v->winteam ? "主場&nbsp;讓分<br><span class='text-info'>".$v->master_refund."</span>":"客場&nbsp;受讓<br><span class='text-info'>".$refund."</span>";
                            }
                        }else{
                            $bigscore = $v->bigscore;
                            if(strpos($bigscore, '-') !== false){
                                $bigscore = str_replace('-','+',trim($bigscore));
                            }else{
                                $bigscore = str_replace('+','-',trim($bigscore));
                            }
                            if($v->bigsmall == 1){
                                $v->pred_str = "大分<br><span class='text-info'>".$v->bigscore."</span>";
                            }else{
                                $v->pred_str = "小分<br><span class='text-info'>".$bigscore."</span>";
                            }
                        }
                        if($v->comply == 1){
                            $v->comply_str = "<span class='text-success'>贏</span>";
                        }elseif($v->comply == 2){
                            $v->comply_str = "<span class='text-danger'>輸</span>";
                        }else{
                            $v->comply_str = "-";
                        }
                    }
                }
            }
        }
        $this->view->assign('mAnalyst', $mAnalyst);
        $this->view->assign('mPred', $mPred);
        return $this->view->fetch();
    }
    
    public function register()
    {
        if ($this->request->isPost()) {
            $line_user_id = $this->request->post('line_user_id');
            $mUser = model('User')->get(['line_user_id' => $line_user_id]);
            if($mUser){
                $apiLine = new \app\api\controller\Line;
                $apiLine->checkRichMenu($line_user_id);
                $this->success('已經註冊過, 重載選單');
            }

            $username = $this->request->post('username');
            $password = $this->request->post('password');
            $password2 = $this->request->post('password2');
            $mobile = $this->request->post('mobile', '');
            $email = $this->request->post('email', '');
            $token = $this->request->post('__token__');

            if($line_user_id == '----'){
                $this->error('發生錯誤, 請重新開啟視窗');
            }
            
            $rule = [
                'username'  => 'require|length:2,10',
                'password'  => 'require|length:6,30',
                'mobile'    => 'regex:/^09\d{2}-?\d{3}-?\d{3}$/',
                '__token__' => 'require|token',
            ];

            $msg = [
                'username.require' => 'Username can not be empty',
                'username.length'  => 'Username must be 2 to 10 characters',
                'password.require' => 'Password can not be empty',
                'password.length'  => 'Password must be 6 to 30 characters',
                'mobile'           => 'Mobile is incorrect',
            ];
            $data = [
                'username'  => $username,
                'password'  => $password,
                'mobile'    => $mobile,
                '__token__' => $token,
            ];
            $validate = new Validate($rule, $msg);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
            }
            if($password != $password2){
                $this->error('確認密碼與密碼不同');
            }
            
            if ($this->auth->register($username, $password, $email, $mobile, $line_user_id)) {
                $apiLine = new \app\api\controller\Line;
                $apiLine->checkRichMenu($line_user_id);
                $this->success(__('Sign up successful'));
            } else {
                $this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
            }
        }
        $this->view->assign('title', __('Register'));
        return $this->view->fetch();
    }

    public function login()
    {
        if ($this->request->isPost()) {
            $line_user_id = $this->request->post('line_user_id');
            if($line_user_id == '----'){
                $this->error('發生錯誤, 請重新開啟視窗');
            }
            $mobile = $this->request->post('mobile', '');
            $password = $this->request->post('password');
            $token = $this->request->post('__token__');

            
            $mOUser = model('User')->get(['line_user_id' => $line_user_id]);
            if($mOUser){
                $apiLine = new \app\api\controller\Line;
                $apiLine->checkRichMenu($line_user_id);
                $this->success('已經註冊過, 重載選單');
            }
            
            $mUser = model('User')->get(['mobile' => $mobile,]);
            if($mUser){
                if(md5(md5($password) . $mUser->salt) == $mUser->password){
                    
                    $mOUser = model('User')->get(['line_user_id' => $line_user_id]);
                    if($mOUser){
                        $mOUser->line_user_id = '已轉移';
                        $mOUser->save();
                    }
                    $mUser->line_user_id = $line_user_id;
                    $mUser->save();
                    $apiLine = new \app\api\controller\Line;
                    $apiLine->checkRichMenu($line_user_id);
                    $this->success('成功登入');
                }else{
                    $this->error('密碼錯誤');
                }
            }else{
                $this->error('此手機上未註冊過, 請先註冊');
            }
            
        }
        $this->view->assign('title', '登入');
        return $this->view->fetch();
    }
    
    public function setnotify()
    {
        if ($this->request->isPost()) {
            $line_user_id = $this->request->post('line_user_id');
            $notify_client_id = $this->request->post('notify_client_id');
            $notify_client_secret = $this->request->post('notify_client_secret');
            $token = $this->request->post('__token__');

            if($line_user_id == '----'){
                $this->error('發生錯誤, 請重新開啟視窗');
            }
            
            if ($this->auth->setnotify($notify_client_id, $notify_client_secret, $line_user_id)) {
                $apiLine = new \app\api\controller\Line;
                $apiLine->checkRichMenu($line_user_id);
                $this->success('連結成功');
            } else {
                $this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
            }
        }
        $notifycallback = $this->site_url['furl'].'/index/notifycallback';
        $this->view->assign('notifycallback', $notifycallback);
        $this->view->assign('title', '連結Notify');
        return $this->view->fetch();
    }

    public function rc($id = null, $back = 0)
    {
        if($back == 1){
            $this->redirect('/index/liff/backregistercustomer/key/'.$id); 
        }
        $this->redirect('/index/liff/registercustomer/key/'.$id); 
    }

    public function backregistercustomer($key = null)
    {
        if($key){
            $mUser = model('User')->where("status = 1 AND id = '".$key."' ")->find();
        }else{
            $mUser = false;
        }
        $this->view->assign('mUser', $mUser);
        $this->view->assign('title', '解除訂閱網址');
        $this->view->assign('description', '客戶點擊此連結後將自動解除訂閱');
        return $this->view->fetch();
    }
    
    public function registercustomer($key = null)
    {
        if($key){
            $mUser = model('User')->where("status = 1 AND id = '".$key."' ")->find();
        }else{
            $mUser = false;
        }
        $this->view->assign('mUser', $mUser);
        $this->view->assign('title', '個人訂閱網址');
        $this->view->assign('description', '請將此連結轉傳給客戶或請客戶掃描下方QR code，進入訂閱頁面');
        return $this->view->fetch();
    }
    
    public function subscriptionpage()
    {
        $this->view->assign('title', '個人訂閱網址');
        return $this->view->fetch();
    }
    
    public function customerlist()
    {
        $this->view->assign('title', '訂閱管理');
        return $this->view->fetch();
    }

    public function customertable()
    {
        $line_user_id = $this->request->request('line_user_id', null);
        $mTable = model('Customer')->alias('c')
        ->join("user u","u.id = c.user_id ")
        ->field('c.*')
        ->where("u.line_user_id = '".$line_user_id."' ")->select();
        
        foreach($mTable as $k => $v){
            if($v->gender == 0){
                $v->gender_str = '<span class="text-fuchsia"><i class="fa fa-venus"></i></span>';
            }else{
                $v->gender_str = '<span class="text-purple"><i class="fa fa-mars"></i></span>';
            }

            if($v->status == 0){
                $v->status_str = '<span class="text-orange"><i class="fa fa-exclamation-circle"></i></span>';
            }elseif($v->status == 1){
                $v->status_str = '<span class="text-success"><i class="fa fa-check-circle"></i></span>';
            }else{
                $v->status_str = '<span class="text-danger"><i class="fa fa-times-circle"></i></span>';
            }
            
        }
        
        $this->view->assign('table_data', $mTable);
        return $this->view->fetch('liff/customertable');
    }
    
    public function customertaglist()
    {
        $this->view->assign('title', '標籤工具');
        return $this->view->fetch();
    }
    
    public function customertagtable()
    {
        $line_user_id = $this->request->request('line_user_id', null);
        $mTable = model('Customertag')->alias('ct')
        ->join("user u","u.id = ct.user_id ")
        ->join("customer_has_tag cht","ct.id = cht.customer_tag_id ","left")
        ->field('ct.*,count(cht.id) as cht_count')
        ->where("u.line_user_id = '".$line_user_id."' ")->group('ct.id')->select();
        
        // foreach($mTable as $k => $v){
            
        // }
        
        $this->view->assign('table_data', $mTable);
        return $this->view->fetch('liff/customertagtable');
    }
    
    public function editcustomer($id = null)
    {
        $line_user_id = $this->request->request('line_user_id', null);
        $mCustomer = model('Customer')->alias('c')
        ->join("user u","u.id = c.user_id ")
        ->field('c.*')
        ->where("u.line_user_id = '".$line_user_id."' AND c.id = ".$id)->find();
        if(!$mCustomer){
            return "資料有誤請重整頁面";
        }

        
        $customer_tag = [];
        $mCustomertag = model('Customertag')->where("user_id = ".$mCustomer->user_id." ")->select();
        if($mCustomertag){
            foreach($mCustomertag as $v){
                $customer_tag[$v->id] = $v->tag_name;
            }
        }
        $customer_tag_id = [];
        $mCustomerhastag = model('Customerhastag')->where("customer_id = ".$mCustomer->id."")->select();
        if($mCustomerhastag){
            foreach($mCustomerhastag as $v){
                $customer_tag_id[] = $v->customer_tag_id;
            }
        }
        $mCustomer->customer_tag = $customer_tag_id;
        
        $this->view->assign('customer_tag', $customer_tag);

        $this->view->assign('line_user_id', $line_user_id);
        $this->view->assign('mCustomer', $mCustomer);
        return $this->view->fetch();
    }
    
    public function addcustomertag()
    {
        $line_user_id = $this->request->request('line_user_id', null);
        $this->view->assign('line_user_id', $line_user_id);
        return $this->view->fetch();
    }

    public function editcustomertag($id = null)
    {
        $line_user_id = $this->request->request('line_user_id', null);
        $mCustomertag = model('Customertag')->alias('ct')
        ->join("user u","u.id = ct.user_id ")
        ->field('ct.*')
        ->where("u.line_user_id = '".$line_user_id."' AND ct.id = ".$id)->find();
        if(!$mCustomertag){
            return "資料有誤請重整頁面";
        }
        $this->view->assign('line_user_id', $line_user_id);
        $this->view->assign('mCustomertag', $mCustomertag);
        return $this->view->fetch();
    }
    public function customertag_buylist()
    {
        $line_user_id = $this->request->request('line_user_id', null);
        $this->view->assign('line_user_id', $line_user_id);
        $buylist = [
            '8' => '8個標籤 $100',
            '16' => '16個標籤 $200',
            '36' => '36個標籤 $400'
        ];
        $this->view->assign('buylist', $buylist);
        return $this->view->fetch();
    }
    
    public function notifyreport()
    {
        $this->view->assign('title', '推播分析');
        return $this->view->fetch();
    }
    
    public function notifyreport_form($uid = null)
    {
        $mUser = model('User')->where("status < 3 AND line_user_id = '".$uid."' ")->find();
        if(!$mUser){
            $this->error('發生錯誤');
        }
        $mNotify = model('Notify')->field('SUM(success) as sum_success, SUM(isread) as sum_isread, SUM(failure) as sum_failure')->where('user_id = '.$mUser->id)->find();
        if($mNotify){
            $success = $mNotify->sum_success;
            $isread = $mNotify->sum_isread;
            $failure = $mNotify->sum_failure;
        }else{
            $success = 0;
            $isread = 0;
            $failure = 0;
        }

        $this->view->assign('success', $success);
        $this->view->assign('isread', $isread);
        $this->view->assign('failure', $failure);
        $this->view->assign('line_user_id', $uid);
        return $this->view->fetch();
    }
    
    public function notifysubtable($id, $uid)
    {
        $mUser = model('User')->where("status < 3 AND line_user_id = '".$uid."' ")->find();
        if(!$mUser){
            return "無權查看";
        }
        $mNotify = model('Notify')->alias('n')
        ->join("notify_sub ns","ns.notify_id = n.id")
        ->join("customer c","ns.customer_id = c.id")
        ->join("user u","n.user_id = u.id")
        ->field("n.*, ns.status as ns_status, ns.isread as ns_isread, ns.id as ns_id, c.customer_name, u.username as user_name")
        ->where("n.user_id = ".$mUser->id." AND n.id = ".$id)->select();

        foreach($mNotify as $k => $v){
            if($v->ns_status == 0){
                $v->statusstr = '<span class="text-orange"><i class="fa fa-clock-o"></i></span>';
            }else if($v->ns_status == 1){
                $v->statusstr = '<span class="text-success"><i class="fa fa-check"></i></span>';
            }else{
                $v->statusstr = '<span class="text-danger"><i class="fa fa-close"></i></span>';
            }
            
            if($v->ns_isread == 1){
                $v->isread_str = '<span class="text-success"><i class="fa fa-envelope-open"></i></span>';
            }else{
                $v->isread_str = '<span class="text-gray"><i class="fa fa-envelope"></i></span>';
            }
        }
        $this->view->assign('table_data', $mNotify);
        return $this->view->fetch('liff/notifysubtable');
    }

    public function infonotify()
    {
        $id = $this->request->request('id', 0);
        $uid = $this->request->request('line_user_id', '----');
        $mUser = model('User')->where("status < 3 AND line_user_id = '".$uid."' ")->find();
        if(!$mUser){
            return "無權查看";
        }
        $mNotify = model('Notify')->where("user_id = '".$mUser->id."' AND id = ".$id)->find();
        if($mNotify){

            if($mNotify->img){
                $mNotify->img_arr = explode(',',$mNotify->img);
            }
            
            $notifysubtable = $this->notifysubtable($id, $uid);
            $this->view->assign('uid', $uid);
            $this->view->assign('mNotify', $mNotify);
            $this->view->assign('notifysubtable', $notifysubtable);
            return $this->view->fetch();
        }
        return "無權查看";
    }
    
    
    public function usercenter()
    {
        $this->view->assign('title', '個人設定');
        return $this->view->fetch();
    }

    public function usercenter_form($uid = null)
    {
        $mUser = model('User')->where("status < 3 AND line_user_id = '".$uid."' ")->find();
        if(!$mUser){
            $this->error('發生錯誤');
        }
        $mUser->exptimecls = 'exptime_base';
        if($mUser->status == 2){
            $mUser->exptime = '您已被禁用';
            $mUser->exptimecls = 'exptime_red';
        }else{
            $mUser->exptime = date('Y-m-d',$mUser->exptime);
            if(strtotime($mUser->exptime.' +1 day') < time()){
                $mUser->exptime = '已到期';
                $mUser->exptimecls = 'exptime_red';
            }elseif(strtotime($mUser->exptime.' +8 day') < time()){
                $mUser->exptimecls = 'exptime_red';
            }
            
        }

        $this->view->assign('mUser', $mUser);
        $this->view->assign('line_user_id', $uid);
        return $this->view->fetch();
    }

    public function usercenter_buylist()
    {
        $line_user_id = $this->request->request('line_user_id', null);
        $this->view->assign('line_user_id', $line_user_id);
        $buylist = [
            '1' => '單月 $123',
            '7' => '半年 $738 (贈送1個月, 共7個月)',
            '14' => '一年 $1476 (贈送2個月, 共14個月)'
        ];
        $this->view->assign('buylist', $buylist);
        return $this->view->fetch();
    }
    
    public function notifynetwork()
    {
        $this->view->assign('title', '訊息推播');
        return $this->view->fetch();
    }

    public function notifynetwork_form($uid = null)
    {
        $mUser = model('User')->where("status = 1 AND line_user_id = '".$uid."' ")->find();
        if(!$mUser){
            $this->error('發生錯誤');
        }

        $allsendfortotal = model('Customer')->where("user_id = ".$mUser->id." AND status = 1 ")->count();
        
        $customer_tag = [
            'gender1' => '男生',
            'gender0' => '女生',
            '20_29y' => '20-29歲',
            '30_39y' => '30-39歲',
            '40_49y' => '40-49歲',
            '50y' => '50歲以上',
        ];
        $mCustomertag = model('Customertag')->where("user_id = ".$mUser->id." ")->select();
        if($mCustomertag){
            foreach($mCustomertag as $v){
                $customer_tag[$v->id] = $v->tag_name;
            }
        }
        $customer_tag['and'] = '交集(符合全部條件)';

        $this->view->assign('line_user_id', $uid);
        $this->view->assign('customer_tag', $customer_tag);
        $this->view->assign('allsendfortotal', $allsendfortotal);
        return $this->view->fetch();
    }
    
    public function notifynetwork_list($uid = null)
    {
        $mUser = model('User')->where("status = 1 AND line_user_id = '".$uid."' ")->find();
        if(!$mUser){
            $this->error('發生錯誤');
        }

        $month1 = time()-2592000;
        
        $mNotify = model('Notify')->alias('n')
        ->field("n.*")
        ->where("n.user_id = '".$mUser->id."' AND n.createtime > ".$month1)->order('n.id', 'desc')->select();

        foreach($mNotify as $k => $v){
            if($v->status == 0){
                if($v->type == 1){
                    $v->statusstr = '<span class="text-orange"><i class="fa fa-clock-o"></i></span>';
                }else{
                    $v->statusstr = '<span class="text-orange"><i class="fa fa-refresh fa-spin"></i></span>';
                }
            }else if($v->status == 1){
                $v->statusstr = '<span class="text-success"><i class="fa fa-check"></i></span>';
            }else{
                $v->statusstr = '<span class="text-danger"><i class="fa fa-close"></i></span>';
            }
        }
        $this->view->assign('table_data', $mNotify);
        return $this->view->fetch();
    }


    
    public function orderpage($number = '')
    {
        $mOrder = model('Order')->get(['order_number'=> $number, 'status' => 0]);
        if(!$mOrder){
            $this->error('查無訂單');
        }

        $mUser = model('User')->get($mOrder->user_id);
        if(!$mUser){
            $this->error('查無會員');
        }

        if($mOrder->buytype == 1){
            $ItemDesc = "續約".$mOrder->month."個月";
            $ReturnURL = $this->site_url['furl']."/index/liff/usercenter";
        }elseif($mOrder->buytype == 2){
            $ItemDesc = "購買".$mOrder->tag."個標籤";
            $ReturnURL = $this->site_url['furl']."/index/liff/customertaglist";
        }else{
            $ItemDesc = "購買服務";
            $ReturnURL = $this->site_url['furl']."/index/liff/usercenter";
        }

        $TradeInfo = [
            'MerchantID' => $this->newebpay_MerchantID,
            'RespondType' => 'JSON',
            'TimeStamp' => time(),
            'Version' => '1.5',
            'LangType' => 'zh-tw',
            'MerchantOrderNo' => $mOrder->order_number,
            'Amt' => $mOrder->amount,
            'ItemDesc' => $ItemDesc,
            'TradeLimit' => 0,
            'ExpireDate' => '',
            'ReturnURL' => $this->site_url['furl']."/index/liff/usercenter",
            'NotifyURL' => $this->site_url['api'].'/notify/order',
            'CustomerURL' => '',
            'ClientBackURL' => '',
            'Email' => $mUser->email,
            'EmailModify' => 1,
            'LoginType' => 0,
            'OrderComment' => '',
        ];
        
        $TradeInfo = create_mpg_aes_encrypt($TradeInfo, $this->newebpay_HashKey, $this->newebpay_HashIV); 
        $TradeSha = "HashKey=".$this->newebpay_HashKey."&".$TradeInfo."&HashIV=".$this->newebpay_HashIV;
        $TradeSha = strtoupper(hash("sha256", $TradeSha));
        $szHtmlData = [
            'url' => $this->newebpay_url,
            'MerchantID' => $this->newebpay_MerchantID,
            'TradeInfo' => $TradeInfo,
            'TradeSha' => $TradeSha,
            'Version' => '1.5',
        ];

        $szHtml = '<!doctype html>';
        $szHtml .= '<html>';
        $szHtml .= '<head>';
        $szHtml .= '<meta charset="utf-8">';
        $szHtml .= '</head>';
        $szHtml .= '<body>';
        $szHtml .= '<form name="newebpay" id="newebpay" method="post" action="' . $szHtmlData['url'] . '" style="display:none;">';
        $szHtml .= '<input name="MerchantID" value="' . $szHtmlData['MerchantID'] . '" type="hidden">';
        $szHtml .= '<input name="TradeInfo" value="' . $szHtmlData['TradeInfo'] . '"   type="hidden">';
        $szHtml .= '<input name="TradeSha" value="' . $szHtmlData['TradeSha'] . '" type="hidden">';
        $szHtml .= '<input name="Version"  value="' . $szHtmlData['Version'] . '" type="hidden">';
        $szHtml .= '</form>';
        $szHtml .= '<script type="text/javascript">';
        $szHtml .= 'document.getElementById("newebpay").submit();';
        $szHtml .= '</script>';
        $szHtml .= '</body>';
        $szHtml .= '</html>';

        return $szHtml;
    }
}
