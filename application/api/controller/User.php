<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Validate;
use think\Log;

/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third','setcode','getanalystpred','getpred','findAnalyst','predEvent','getanalyst'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 会员中心
     */
    public function index()
    {
        $this->success('', ['welcome' => $this->auth->nickname]);
    }

    
    public function setcode(){
        $line_user_id = $this->request->request('line_user_id','----');
        $code = $this->request->request('code','');
        $today = strtotime(date('Y-m-d'));

        if(trim($code) == '') $this->error('請輸入代碼');
        $mUser = model('User')->get(['line_user_id' => $line_user_id]);
        if($mUser){
            $this->error('此手機已綁定過代碼, 請聯繫客服');
        }else{
            $mUserfree = model('Userfree')->get(['line_user_id' => $line_user_id]);
            if($mUserfree){
                $mUser = model('User')->get(['code' => trim($code), 'status' => 1]);
                if($mUser){
                    $mUsertopred = model('Usertopred')->where("userfree_id = ".$mUserfree->id." AND createtime > ".$today)->find();
                    if($mUsertopred){
                        $params = [
                            'user_id' => $mUser->id,
                            'pred_id' => $mUsertopred->pred_id
                        ];
                        model('Usertopred')::create($params);
                        $mUser->get_pred_time = time();
                    }

                    $mUser->line_user_id = $mUserfree->line_user_id;
                    $mUser->service_id = $mUserfree->service_id;
                    $mUser->save();
                    $this->success('綁定成功');
                }else{
                    $this->error('代碼有誤, 請聯繫客服');
                }
            }else{
                $this->error('發生錯誤, 請重啟視窗');
            }
        }
    }
    
    public function getanalystpred($id = 0){
        Log::init(['type' => 'File', 'log_name' => 'getanalystpred']);
        $line_user_id = $this->request->request('line_user_id','----');
        $randomType = [
            1 => 50,
            2 => 50,
        ];
        
        $today = strtotime(date('Y-m-d'));

        $freepred = 0;
        $lastpred = 0;

        $check = "";

        Log::notice("查詢用戶..");
        $mUser = model('User')->get(['line_user_id' => $line_user_id, 'status' => 1]);
        if($mUser){
            
            if(!$mUser->get_pred_time OR $mUser->get_pred_time < $today){
                Log::notice("還有免費次數");
                $isfree = 0;
            }else{
                Log::notice("免費次數已使用, 上次時間:".date("Y-m-d H:i:s",$mUser->get_pred_time));
                $isfree = 1;
            }
            //不使用免費觀看
            $isfree = 1;

            $lastpred = 0;
            if($mUser->ptime1 AND $mUser->ptime2){
                if($mUser->ptime1 <= time() AND time() <= $mUser->ptime2){
                    $lastpred = $mUser->pred2;
                }
            }else{
                $lastpred = $mUser->pred2;
            }


            if($lastpred > 0 OR $isfree == 0){
                //
                $mAnalyst = model('Analyst')->alias('a')
                ->field("a.*")
                ->where("a.id = ".$id)->find();
                if($mAnalyst){
                    if($mAnalyst->status != 1){
                        $this->error('分析師已停用');
                    }
                }else{
                    $this->error('查無分析師');
                }
                if($mAnalyst->autopred_count <= $mAnalyst->autopred_today){
                    $wherestr = " AND (p1.id IS NOT NULL OR p2.id IS NOT NULL)";
                    $predfull = true;
                }else{
                    $wherestr = "";
                    $predfull = false;
                }

                $mEvent = model('Event')->alias('e')
                ->join("analyst_to_event_category atc","atc.event_category_id = e.event_category_id AND atc.analyst_id = ".$mAnalyst->id)
                ->join("event_category ec","ec.id = e.event_category_id AND ec.status = 1")
                ->join("pred p1","e.id = p1.event_id AND p1.pred_type = 1 AND p1.analyst_id = ".$mAnalyst->id,"LEFT")
                ->join("pred p2","e.id = p2.event_id AND p2.pred_type = 2 AND p2.analyst_id = ".$mAnalyst->id,"LEFT")
                ->join("user_to_pred utp1","p1.id = utp1.pred_id AND utp1.user_id = ".$mUser->id,"LEFT")
                ->join("user_to_pred utp2","p2.id = utp2.pred_id AND utp2.user_id = ".$mUser->id,"LEFT")
                ->field("e.*, p1.id as type1, p2.id as type2")
                ->where("((e.master_refund <> '0' AND e.guests_refund <> '0' AND p1.id IS NULL) OR (e.bigscore <> '0' AND p2.id IS NULL) OR p1.id IS NOT NULL OR p2.id IS NOT NULL) AND utp1.id IS NULL AND utp2.id IS NULL AND e.starttime > ".time()." ".$wherestr)->orderRaw('RAND()')->find();
                
                // Log::notice(model('Event')->getLastsql());
                if($mEvent){
                    $pred_type = Rand(1,2);
                    if($predfull){
                        if($mEvent->type1 == null AND $mEvent->type2 == null){
                            Log::notice("沒有新的預測可以看");
                            $this->error('沒有新的預測可以看');
                        }elseif($mEvent->type1 == null){
                            $pred_type = 2;
                        }else{
                            $pred_type = 1;
                        }
                    }elseif(($mEvent->type1 == null AND $mEvent->type2 == null) OR ($pred_type == 1 AND $mEvent->type1 == null) OR ($pred_type == 2 AND $mEvent->type2 == null)){
                        if($pred_type == 1 AND ($mEvent->master_refund == '0' OR $mEvent->guests_refund == '0')){
                            $pred_type = 2;
                        }elseif($pred_type == 2 AND $mEvent->bigscore == '0'){
                            $pred_type = 1;
                        }
                        $randomPred = [
                            1 => 50,
                            0 => 50,
                        ];
                        $randomValue = Random::lottery($randomPred);
    
                        $bigscore = $mEvent->bigscore;
                        $master_refund = $mEvent->master_refund;
                        $guests_refund = $mEvent->guests_refund;
    
                        if($pred_type == 1){
                            $other_type = 2;
                        }else{
                            $other_type = 1;
                        }
                        $mPredOtherType = model('Pred')->alias('p')
                        ->field('p.*')
                        ->where("p.analyst_id = ".$mAnalyst->id." AND p.event_id = ".$mEvent->id." AND p.pred_type = ".$other_type)->find();
                        if($mPredOtherType){
                            $predtime = $mPredOtherType->predtime;
                            $bigscore = $mPredOtherType->bigscore;
                            $master_refund = $mPredOtherType->master_refund;
                            $guests_refund = $mPredOtherType->guests_refund;
                        }else{
                            $htime = strtotime(date("Y-m-d H:i:s")." -15 minute");
                            $predtime = Rand($mEvent->createtime, $htime);
                            if($predtime < $mEvent->updatetime){
                                $mEventparam = model('Eventparam')->alias('ep')
                                ->field('ep.*')
                                ->where("ep.event_id = ".$mEvent->id." AND ep.createtime <= ".$predtime)->order('ep.createtime','desc')->find();
                                if($mEventparam){
                                    $bigscore = $mEventparam->bigscore;
                                    $master_refund = $mEventparam->master_refund;
                                    $guests_refund = $mEventparam->guests_refund;
                                }
                            }
                        }
                        
                        $ptpred = [
                            'event_id' => $mEvent->id,
                            'analyst_id' => $mAnalyst->id,
                            'master_refund' => $master_refund,
                            'guests_refund' => $guests_refund,
                            'bigscore' => $bigscore,
                            'predtime' => $predtime,
                            'pred_type' => $pred_type,
                            'isauto' => 1,
                        ];
    
                        if($pred_type == 1){
                            $ptpred['winteam'] = $randomValue;
                        }else{
                            $ptpred['bigsmall'] = $randomValue;
                        }
    
                        model('Pred')::create($ptpred);
                        $mEvent->pred = $mEvent->pred+1;
                        $mEvent->save();
                        $mAnalyst->autopred_today = $mAnalyst->autopred_today+1;
                        $mAnalyst->save();
    
                        Log::notice("預測師id[".$mAnalyst->id."] 自動預測 預測時間[".date("Y-m-d H:i:s",$predtime)."] 預測賽事 ".json_encode($ptpred, JSON_UNESCAPED_UNICODE));
                    }else{
                        Log::notice("已預測過, 直接取得");
                    }
                }else{
                    Log::notice("沒有新的預測可以看");
                    $this->error('沒有新的預測可以看');
                }
                
                $mPred = model('Pred')->alias('p')
                ->join("analyst a","a.id = p.analyst_id")
                ->join("event e","e.id = p.event_id")
                ->join("event_category ec","ec.id = e.event_category_id")
                ->field('p.*, a.analyst_name, e.guests, e.master, e.starttime, ec.title')
                ->where("a.status = 1 AND p.analyst_id = ".$mAnalyst->id." AND p.event_id = ".$mEvent->id." AND p.pred_type = ".$pred_type)->find();
                if(!$mPred){
                    Log::notice("出錯");
                    $this->error('發生錯誤, 請聯繫客服');
                }

                $params = [
                    'user_id' => $mUser->id,
                    'pred_id' => $mPred->id
                ];
                model('Usertopred')::create($params);
                if($isfree == 0){
                    Log::notice("使用免費次數");
                    $mUser->get_pred_time = time();
                    $mUser->save();
                }else{
                    Log::notice("使用預測次數");
                    $lastpred = $lastpred - 1;
                    $mUser->pred2 = $lastpred;
                    $mUser->save();
                }
            }else{
                Log::notice("預測次數已用完，請洽客服");
                $this->error('預測次數已用完，請洽客服');
            }
        }else{
            //不使用免費觀看
            Log::notice("請綁定代碼");
            $this->error('請綁定代碼');
        }

        $predstrAll = "預測盤口時間:&nbsp;".date("Y-m-d H:i", $mPred->predtime)."<br>";
        $predstrAll .= "分析師:&nbsp;".$mPred->analyst_name."<br>";
        $linemsg = "【賽事預測】
分類: ".$mPred->title."
開賽時間: ".date("Y-m-d H:i", $mPred->starttime)."
客場: ".$mPred->guests."
主場: ".$mPred->master."

預測盤口時間: ".date("Y-m-d H:i", $mPred->predtime)."
分析師: ".$mPred->analyst_name."
預測";

        $type = $pred_type;
        if($type == 1){
            $mPred->isreadwin = 1;

            if($mPred->guests_refund != ''){
                $refund = $mPred->guests_refund;
                if(strpos($refund, '-') !== false){
                    $refund = str_replace('-','+',trim($refund));
                }else{
                    $refund = str_replace('+','-',trim($refund));
                }
                $predstr = $mPred->winteam ? "主 ".$mPred->master." 受讓 ".$refund:"客 ".$mPred->guests." 讓分 ".$mPred->guests_refund;
            }else{
                $refund = $mPred->master_refund;
                if(strpos($refund, '-') !== false){
                    $refund = str_replace('-','+',trim($refund));
                }else{
                    $refund = str_replace('+','-',trim($refund));
                }
                $predstr = $mPred->winteam ? "主 ".$mPred->master." 讓分 ".$mPred->master_refund:"客 ".$mPred->guests." 受讓 ".$refund;
            }
            $predstrAll .= "<span class='text-danger'>".$predstr."</span>";

            $linemsg .= "讓分: ".$predstr;
        }elseif($type == 2){
            $mPred->isreadbig = 1;
            $bigscore = $mPred->bigscore;
            if(strpos($bigscore, '-') !== false){
                $bigscore = str_replace('-','+',trim($bigscore));
            }else{
                $bigscore = str_replace('+','-',trim($bigscore));
            }
            $predstr = $mPred->bigsmall?"大分 ".$mPred->bigscore:"小分 ".$bigscore;
            $predstrAll .= "<span class='text-danger'>".$predstr."</span>";

            $linemsg .= "大小: ".$predstr;
        }
        $mPred->save();

        $this->success('取得預測',['eid' => $mEvent->id, 'linemsg' => $linemsg]);

    }

    public function getpred($id = 0){
        Log::init(['type' => 'File', 'log_name' => 'getpred']);
        $line_user_id = $this->request->request('line_user_id','----');
        $type = $this->request->request('type', 0);
        
        $today = strtotime(date('Y-m-d'));

        $freepred = 0;
        $lastpred = 0;

        $check = "";

        Log::notice("查詢用戶..");
        $mUser = model('User')->get(['line_user_id' => $line_user_id, 'status' => 1]);
        if($mUser){
            
            if(!$mUser->get_pred_time OR $mUser->get_pred_time < $today){
                Log::notice("還有免費次數");
                $isfree = 0;
            }else{
                Log::notice("免費次數已使用, 上次時間:".date("Y-m-d H:i:s",$mUser->get_pred_time));
                $isfree = 1;
            }
            //不使用免費觀看
            $isfree = 1;

            $lastpred = 0;
            if($mUser->ptime1 AND $mUser->ptime2){
                if($mUser->ptime1 <= time() AND time() <= $mUser->ptime2){
                    $lastpred = $mUser->pred2;
                }
            }else{
                $lastpred = $mUser->pred2;
            }

            $mUsertopred = model('Usertopred')->alias('utp')
            ->join("pred p","p.id = utp.pred_id")
            ->join("analyst a","a.id = p.analyst_id")
            ->join("event e","e.id = p.event_id")
            ->join("event_category ec","ec.id = e.event_category_id")
            ->field('p.*, a.analyst_name, e.guests, e.master, e.starttime, ec.title')
            ->where("utp.user_id = ".$mUser->id." AND p.event_id = ".$id." AND p.pred_type = ".$type." ")->find();
            if(!$mUsertopred){
                Log::notice("沒預測過此場次, 檢查次數..");
                if($lastpred > 0 OR $isfree == 0){

                    $mPred = $this->findAnalyst($id, $type);
                    if(!$mPred){
                        Log::notice("沒有分析師預測");
                        $this->error('沒有分析師預測');
                    }

                    $params = [
                        'user_id' => $mUser->id,
                        'pred_id' => $mPred->id
                    ];
                    model('Usertopred')::create($params);
                    if($isfree == 0){
                        Log::notice("使用免費次數");
                        $mUser->get_pred_time = time();
                        $mUser->save();
                    }else{
                        Log::notice("使用預測次數");
                        $lastpred = $lastpred - 1;
                        $mUser->pred2 = $lastpred;
                        $mUser->save();
                    }
                }else{
                    Log::notice("預測次數已用完，請洽客服");
                    $this->error('預測次數已用完，請洽客服');
                }
            }else{
                Log::notice("曾經預測過此場次,直接顯示上次預測");
                $mPred = $mUsertopred;
            }
        }else{
            //不使用免費觀看
            Log::notice("請綁定代碼");
            $this->error('請綁定代碼', 'setcode');

            Log::notice("查無用戶 使用免費用戶 line_user_id:".$line_user_id);
            $mUserfree = model('Userfree')->where("line_user_id = '".$line_user_id."' ")->find();
            if($mUserfree){
                $mUsertopred = model('Usertopred')->alias('utp')
                ->join("pred p","p.id = utp.pred_id")
                ->join("analyst a","a.id = p.analyst_id")
                ->join("event e","e.id = p.event_id")
                ->join("event_category ec","ec.id = e.event_category_id")
                ->field('p.*, a.analyst_name, e.guests, e.master, e.starttime, ec.title')
                ->where("utp.userfree_id = ".$mUserfree->id." AND p.event_id = ".$id." AND p.pred_type = ".$type." ")->find();
                if(!$mUsertopred){
                    Log::notice("沒預測過此場次, 檢查次數..");
                    if(!$mUserfree->get_pred_time OR $mUserfree->get_pred_time < $today){
                        
                        $mPred = $this->findAnalyst($id, $type);
                        if(!$mPred){
                            Log::notice("沒有分析師預測");
                            $this->error('沒有分析師預測');
                        }

                        $params = [
                            'userfree_id' => $mUserfree->id,
                            'pred_id' => $mPred->id
                        ];
                        model('Usertopred')::create($params);

                        Log::notice("使用免費次數");
                        $mUserfree->get_pred_time = time();
                        $mUserfree->save();
                    }else{
                        Log::notice("今日免費次數已用完, 請綁定代碼");
                        $this->error('今日免費次數已用完, 請綁定代碼', 'setcode');
                    }
                }else{
                    Log::notice("曾經預測過此場次,直接顯示上次預測");
                    $mPred = $mUsertopred;
                }
            }else{
                Log::notice("查無免費用戶");
                $this->error('發生錯誤, 請聯繫客服');
            }
        }

        $predstrAll = "預測盤口時間:&nbsp;".date("Y-m-d H:i", $mPred->predtime)."<br>";
        $predstrAll .= "分析師:&nbsp;".$mPred->analyst_name."<br>";
        $linemsg = "【賽事預測】
分類: ".$mPred->title."
開賽時間: ".date("Y-m-d H:i", $mPred->starttime)."
客場: ".$mPred->guests."
主場: ".$mPred->master."

預測盤口時間: ".date("Y-m-d H:i", $mPred->predtime)."
分析師: ".$mPred->analyst_name."
預測";

        if($type == 1){
            $mPred->isreadwin = 1;

            if($mPred->guests_refund != ''){
                $refund = $mPred->guests_refund;
                if(strpos($refund, '-') !== false){
                    $refund = str_replace('-','+',trim($refund));
                }else{
                    $refund = str_replace('+','-',trim($refund));
                }
                $predstr = $mPred->winteam ? "主 ".$mPred->master." 受讓 ".$refund:"客 ".$mPred->guests." 讓分 ".$mPred->guests_refund;
            }else{
                $refund = $mPred->master_refund;
                if(strpos($refund, '-') !== false){
                    $refund = str_replace('-','+',trim($refund));
                }else{
                    $refund = str_replace('+','-',trim($refund));
                }
                $predstr = $mPred->winteam ? "主 ".$mPred->master." 讓分 ".$mPred->master_refund:"客 ".$mPred->guests." 受讓 ".$refund;
            }
            $predstrAll .= "<span class='text-danger'>".$predstr."</span>";

            $linemsg .= "讓分: ".$predstr;
        }elseif($type == 2){
            $mPred->isreadbig = 1;
            $bigscore = $mPred->bigscore;
            if(strpos($bigscore, '-') !== false){
                $bigscore = str_replace('-','+',trim($bigscore));
            }else{
                $bigscore = str_replace('+','-',trim($bigscore));
            }
            $predstr = $mPred->bigsmall?"大分 ".$mPred->bigscore:"小分 ".$bigscore;
            $predstrAll .= "<span class='text-danger'>".$predstr."</span>";

            $linemsg .= "大小: ".$predstr;
        }
        $mPred->save();

        $this->success('取得預測',['predstr' => $predstrAll, 'linemsg' => $linemsg, 'freepred' => $freepred, 'lastpred' => $lastpred]);

    }

    public function findAnalyst($id = null, $pred_type = 1, $unanalyst = [])
    {
        $unaid = "";
        if(sizeof($unanalyst)>0){
            $unaid = " AND a.id NOT IN (".implode(",",$unanalyst).")";
        }
        if($pred_type == 1){
            Log::notice("預測讓分");
            // $check = " e.master_refund <> '0' AND e.guests_refund <> '0' ";
        }else{
            Log::notice("預測大小");
            // $check = " e.bigscore <> '0' ";
        }
        $mEvent = model('Analyst')->alias('a')
        ->join("analyst_to_event_category atc","atc.analyst_id = a.id")
        ->join("event e","atc.event_category_id = e.event_category_id AND e.id = ".$id)
        ->join("event_category ec","ec.id = e.event_category_id AND ec.status = 1")
        ->field("e.*, a.id as analyst_id, a.autopred_today, a.autopred_count")
        ->where("a.autopred = 1 AND a.status = 1 ".$unaid)->orderRaw('RAND()')->find();

        if($mEvent){
            if($pred_type == 1 AND ($mEvent->master_refund == '0' OR $mEvent->guests_refund == '0')){
                $this->error('盤口未開');
            }elseif($pred_type == 2 AND $mEvent->bigscore == '0'){
                $this->error('盤口未開');
            }

            $mPred = model('Pred')->alias('p')
            ->join("analyst a","a.id = p.analyst_id")
            ->join("event e","e.id = p.event_id")
            ->join("event_category ec","ec.id = e.event_category_id")
            ->field('p.*, a.analyst_name, e.guests, e.master, e.starttime, ec.title')
            ->where("a.status = 1 AND p.analyst_id = ".$mEvent->analyst_id." AND p.event_id = ".$id." AND p.pred_type = ".$pred_type)->find();
            if(!$mPred){
                if($mEvent->autopred_today >= $mEvent->autopred_count){
                    $unanalyst[] = $mEvent->analyst_id;
                    Log::notice("[".$mEvent->analyst_id."]分析師預測次數已用完,重新抓取..");
                    return $this->findAnalyst($id, $pred_type, $unanalyst);
                }
                
                $mPred = $this->predEvent($mEvent, $pred_type);
            }
        }else{
            Log::notice("沒有分析師預測 (findAnalyst)");
            $this->error('沒有分析師預測');
        }

        return $mPred;
    }
    
    public function predEvent($mEvent = false, $pred_type = 1)
    {
        if($mEvent){
            $randomPred = [
                1 => 50,
                0 => 50,
            ];
            $randomValue = Random::lottery($randomPred);
            $bigscore = $mEvent->bigscore;
            $master_refund = $mEvent->master_refund;
            $guests_refund = $mEvent->guests_refund;
            
            if($pred_type == 1){
                $other_type = 2;
            }else{
                $other_type = 1;
            }
            $mPredOtherType = model('Pred')->alias('p')
            ->field('p.predtime')
            ->where("p.analyst_id = ".$mEvent->analyst_id." AND p.event_id = ".$mEvent->id." AND p.pred_type = ".$other_type)->find();
            if($mPredOtherType){
                $predtime = $mPredOtherType->predtime;
            }else{
                $htime = strtotime(date("Y-m-d H:i:s")." -15 minute");
                $predtime = Rand($mEvent->createtime, $htime);
            }
            Log::notice($predtime);

            if($predtime < $mEvent->updatetime){
                $mEventparam = model('Eventparam')->alias('ep')
                ->field('ep.*')
                ->where("ep.event_id = ".$mEvent->id." AND ep.createtime <= ".$predtime)->order('ep.createtime','desc')->find();
                if($mEventparam){
                    $bigscore = $mEventparam->bigscore;
                    $master_refund = $mEventparam->master_refund;
                    $guests_refund = $mEventparam->guests_refund;
                }
            }
            
            $ptpred = [
                'event_id' => $mEvent->id,
                'analyst_id' => $mEvent->analyst_id,
                'master_refund' => $master_refund,
                'guests_refund' => $guests_refund,
                'bigscore' => $bigscore,
                'predtime' => $predtime,
                'pred_type' => $pred_type,
                'isauto' => 1,
            ];
            if($pred_type == 1){
                $ptpred['winteam'] = $randomValue;
            }else{
                $ptpred['bigsmall'] = $randomValue;
            }

            model('Pred')::create($ptpred);
            $mE = model('Event')->get($mEvent->id);
            $mE->pred = $mE->pred+1;
            $mE->save();
            $mA = model('Analyst')->get($mEvent->analyst_id);
            $mA->autopred_today = $mA->autopred_today+1;
            $mA->save();

            Log::notice("pred_type:".$pred_type);
            Log::notice("預測時間[".date("Y-m-d H:i:s",$predtime)."] 預測賽事 ".json_encode($ptpred, JSON_UNESCAPED_UNICODE));

            $mPred = model('Pred')->alias('p')
            ->join("analyst a","a.id = p.analyst_id")
            ->join("event e","e.id = p.event_id")
            ->join("event_category ec","ec.id = e.event_category_id")
            ->field('p.*, a.analyst_name, e.guests, e.master, e.starttime, ec.title')
            ->where("a.status = 1 AND p.analyst_id = ".$mEvent->analyst_id." AND p.event_id = ".$mEvent->id." AND p.pred_type = ".$pred_type)->find();
            if(!$mPred){
                Log::notice("出錯");
                $this->error('發生錯誤, 請聯繫客服');
            }
        }else{
            $this->error('發生錯誤, 請聯繫客服');
        }

        return $mPred;
    }

    public function getanalyst()
    {
        $analyst_name = $this->request->request('analyst_name','');
        $mAnalyst = model('Analyst')->where("status = 1 AND analyst_name = '".$analyst_name."'")->find();
        if($mAnalyst){
            $this->success(__('取得分析師'), ['id' => $mAnalyst->id]);
        }else{
            $this->error('查無分析師');
        }
    }
    
    /**
     * 会员登录
     *
     * @param string $account  账号
     * @param string $password 密码
     */
    public function login()
    {
        $account = $this->request->request('account');
        $password = $this->request->request('password');
        if (!$account || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($account, $password);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 手机验证码登录
     *
     * @param string $mobile  手机号
     * @param string $captcha 验证码
     */
    public function mobilelogin()
    {
        $mobile = $this->request->request('mobile');
        $captcha = $this->request->request('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "/^09\d{2}-?\d{3}-?\d{3}$/")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (!Sms::check($mobile, $captcha, 'mobilelogin')) {
            $this->error(__('Captcha is incorrect'));
        }
        $user = \app\common\model\User::getByMobile($mobile);
        if ($user) {
            if ($user->status != '1') {
                $this->error(__('Account is locked'));
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
        } else {
            $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, []);
        }
        if ($ret) {
            Sms::flush($mobile, 'mobilelogin');
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注册会员
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $email    邮箱
     * @param string $mobile   手机号
     * @param string $code   验证码
     */
    public function register()
    {
        $username = $this->request->request('username');
        $password = $this->request->request('password');
        $email = $this->request->request('email');
        $mobile = $this->request->request('mobile');
        $code = $this->request->request('code');
        if (!$username || !$password) {
            $this->error(__('Invalid parameters'));
        }
        if ($email && !Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if ($mobile && !Validate::regex($mobile, "/^09\d{2}-?\d{3}-?\d{3}$/")) {
            $this->error(__('Mobile is incorrect'));
        }
        $ret = Sms::check($mobile, $code, 'register');
        if (!$ret) {
            $this->error(__('Captcha is incorrect'));
        }
        $ret = $this->auth->register($username, $password, $email, $mobile, []);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Sign up successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }

    /**
     * 修改会员个人信息
     *
     * @param string $avatar   头像地址
     * @param string $username 用户名
     * @param string $nickname 昵称
     * @param string $bio      个人简介
     */
    public function profile()
    {
        $user = $this->auth->getUser();
        $nickname = $this->request->request('nickname','');
        $email = $this->request->request('email','');
        $mobile = $this->request->request('mobile','');
        $password = $this->request->request('password','');
        $avatar = $this->request->request('avatar', '', 'trim,strip_tags,htmlspecialchars');

        
        $rule = [
            'nickname'  => 'require|length:2,10',
            'email'  => 'require|email',
            'mobile'    => 'require|regex:/^09\d{2}-?\d{3}-?\d{3}$/',
            'password'  => $password != ''?'require|length:6,16':'',
        ];

        $msg = [
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
            'nickname'  => $nickname,
            'email'    => $email,
            'mobile'    => $mobile,
            'password'  => $password,
        ];

        $validate = new Validate($rule, $msg);
        $result = $validate->check($data);
        if (!$result) {
            $this->error(__($validate->getError()));
        }


        if ($nickname) {
            $exists = model('User')->where('nickname', $nickname)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error('暱稱已用過');
            }
            $user->nickname = $nickname;
        }
        if ($email) {
            $exists = model('User')->where('email', $email)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error('信箱已用過');
            }
            $user->email = $email;
        }
        if ($mobile) {
            $exists = model('User')->where('mobile', $mobile)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error('手機已用過');
            }
            $user->mobile = $mobile;
        }
        if($password != ''){
            $user->salt = Random::alnum();
            $user->password = $this->getEncryptPassword($password, $user->salt);
        }

        $user->avatar = $avatar;
        $user->save();
        $this->success();
    }

    /**
     * 修改邮箱
     *
     * @param string $email   邮箱
     * @param string $captcha 验证码
     */
    public function changeemail()
    {
        $user = $this->auth->getUser();
        $email = $this->request->post('email');
        $captcha = $this->request->request('captcha');
        if (!$email || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if (\app\common\model\User::where('email', $email)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Email already exists'));
        }
        $result = Ems::check($email, $captcha, 'changeemail');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->email = 1;
        $user->verification = $verification;
        $user->email = $email;
        $user->save();

        Ems::flush($email, 'changeemail');
        $this->success();
    }

    /**
     * 修改手机号
     *
     * @param string $mobile   手机号
     * @param string $captcha 验证码
     */
    public function changemobile()
    {
        $user = $this->auth->getUser();
        $mobile = $this->request->request('mobile');
        $captcha = $this->request->request('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "/^09\d{2}-?\d{3}-?\d{3}$/")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (\app\common\model\User::where('mobile', $mobile)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Mobile already exists'));
        }
        $result = Sms::check($mobile, $captcha, 'changemobile');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->mobile = 1;
        $user->verification = $verification;
        $user->mobile = $mobile;
        $user->save();

        Sms::flush($mobile, 'changemobile');
        $this->success();
    }

    /**
     * 第三方登录
     *
     * @param string $platform 平台名称
     * @param string $code     Code码
     */
    public function third()
    {
        $url = url('user/index');
        $platform = $this->request->request("platform");
        $code = $this->request->request("code");
        $config = get_addon_config('third');
        if (!$config || !isset($config[$platform])) {
            $this->error(__('Invalid parameters'));
        }
        $app = new \addons\third\library\Application($config);
        //通过code换access_token和绑定会员
        $result = $app->{$platform}->getUserInfo(['code' => $code]);
        if ($result) {
            $loginret = \addons\third\library\Service::connect($platform, $result);
            if ($loginret) {
                $data = [
                    'userinfo'  => $this->auth->getUserinfo(),
                    'thirdinfo' => $result
                ];
                $this->success(__('Logged in successful'), $data);
            }
        }
        $this->error(__('Operation failed'), $url);
    }

    /**
     * 重置密码
     *
     * @param string $mobile      手机号
     * @param string $newpassword 新密码
     * @param string $captcha     验证码
     */
    public function resetpwd()
    {
        $type = $this->request->request("type");
        $mobile = $this->request->request("mobile");
        $email = $this->request->request("email");
        $newpassword = $this->request->request("newpassword");
        $captcha = $this->request->request("captcha");
        if (!$newpassword || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if ($type == 'mobile') {
            if (!Validate::regex($mobile, "/^09\d{2}-?\d{3}-?\d{3}$/")) {
                $this->error(__('Mobile is incorrect'));
            }
            $user = \app\common\model\User::getByMobile($mobile);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Sms::check($mobile, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Sms::flush($mobile, 'resetpwd');
        } else {
            if (!Validate::is($email, "email")) {
                $this->error(__('Email is incorrect'));
            }
            $user = \app\common\model\User::getByEmail($email);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Ems::check($email, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Ems::flush($email, 'resetpwd');
        }
        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }
    
    public function changeStatus()
    {
        $id = $this->request->request('id');
        // $mUserAdmin = \app\common\model\User::get(['id'=> $this->auth->id, 'status'=> 1, 'level'=> 1]);
        // if(!$mUserAdmin){
        //     $this->error('無權操作');
        // }
        // $mUser = \app\common\model\User::get(['id'=> $id, 'merchant_id' =>$mUserAdmin->merchant_id]);
        // if(!$mUser){
        //     $this->error('查無帳號');
        // }
        // if($mUser->status == 0){
        //     $mUser->status = 1;
        // }else{
        //     $mUser->status = 0;
        // }
        // $mUser->save();
        $this->success();
    }
}
