<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Log;
use fast\Random;

class Analyst extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = 'base';

    public function _initialize()
    {
        parent::_initialize();
        Log::init(['type' => 'File', 'log_name' => 'analyst']);
    }

    public function index()
    {
        // $mAnalyst = model('Analyst')->order("id","desc")->find();
        // $mRankcontent = false;
        // if($mRank){
        //     $mRankcontent = model('Rankcontent')->alias('rc')
        //     ->join("analyst a","a.id = rc.analyst_id")
        //     ->field("rc.*, a.analyst_name, a.avatar")
        //     ->where('rc.rank_id = '.$mRank->id)->order('rc.rank','asc')->select();
        //     if($mRankcontent){
        //         foreach($mRankcontent as $v){
        //             if(!$v->avatar) $v->avatar = "/uploads/20220608/822e61d8fe01146ce6aa1ec3742adca1.jpg";
        //         }
        //     }
        // }
        // $this->view->assign('mRank', $mRank);
        // $this->view->assign('mRankcontent', $mRankcontent);
        return $this->view->fetch();
    }

    public function list()
    {
        $cat_id = $this->request->request('cat', 0);
        $this->view->assign('cat_id', $cat_id);
        
        $mEventcategory = model('Eventcategory')->where('status = 1')->select();
        $this->view->assign('mEventcategory', $mEventcategory);
        
        $cat_where = "";
        if($cat_id != 0){
            $cat_where = " AND e.event_category_id = ".$cat_id;
        }

        $page = $this->request->request('page', 1);
        $mAnalyst = model('Analyst')->alias('a')
        ->join("pred p","p.analyst_id = a.id")
        ->join("event e","e.id = p.event_id")
        ->field("a.*")
        ->where("a.status = 1 ".$cat_where)->group('a.id')->orderRaw('RAND()')->paginate(20, false, $this->paginate_config);
        $count = $mAnalyst->total();
        $pagelist = $mAnalyst->render();
        if($count > 0){
            foreach($mAnalyst as $v){
                if(!$v->avatar) $v->avatar = $this->def_avatar;
            }
        }
        $this->view->assign('count', $count);
        $this->view->assign('page', $page);
        $this->view->assign('pagelist', $pagelist);

        $this->view->assign('mAnalyst', $mAnalyst);
        return $this->view->fetch();
    }

    
    public function profile($id = 0, $pt = 1)
    {
        $this->view->assign('pt', $pt);
        $mAnalyst = model('Analyst')->where("id = ".$id)->find();
        $content = "";
        if($mAnalyst){
            //頭像
            if(!$mAnalyst->avatar) $mAnalyst->avatar = $this->def_avatar;

            //檢查預測
            if($mAnalyst->autopred == 1){
                $this->checkPred($id);
            }

            if($pt == 1){
                $content = $this->pred($id);
            }
        }else{
            $this->redirect('/');
        }
        $this->view->assign('mAnalyst', $mAnalyst);
        $this->view->assign('content', $content);
        return $this->view->fetch();
    }
    
    public function pred($id)
    {
        $this->view->assign('id', $id);
        $eid = 0;
        $mEventcategory = model('Eventcategory')->alias('ec')
        ->join("event e","e.event_category_id = ec.id")
        ->join("pred p","p.event_id = e.id AND p.analyst_id = ".$id)
        ->distinct(true)
        ->field("ec.*")
        ->where("ec.status = 1")->order('ec.id')->group('ec.id')->find();
        if($mEventcategory) $eid = $mEventcategory->id;
        $sdate = $this->request->request('sdate', strtotime(date('Y-m-d')));
        $cat_id = $this->request->request('cat', $eid);
        $starttime_start = $sdate;
        $starttime_end = strtotime(date('Y-m-d',$sdate).' +1 day');
        
        $this->view->assign('sdate', $sdate);
        $this->view->assign('cat_id', $cat_id);


        // $mEventcategory = model('Eventcategory')->where('status = 1')->select();
        $mEventcategory = model('Eventcategory')->alias('ec')
        ->join("event e","e.event_category_id = ec.id")
        ->join("pred p","p.event_id = e.id AND p.analyst_id = ".$id)
        ->distinct(true)
        ->field("ec.*")
        ->where("ec.status = 1")->order('ec.id')->group('ec.id')->select();
        $this->view->assign('mEventcategory', $mEventcategory);
        
        $datelist = [];
        $weekStr =  ['日', '一', '二', '三', '四', '五', '六'];
        $time = strtotime(date('Y-m-d').' -1 day');
        $datelist[$time] = date('m/d', $time).'&nbsp;('.$weekStr[date('w', $time)].')';
        $time = strtotime(date('Y-m-d'));
        $datelist[$time] = date('m/d', $time).'&nbsp;('.$weekStr[date('w', $time)].')';
        $time = strtotime(date('Y-m-d').' +1 day');
        $datelist[$time] = date('m/d', $time).'&nbsp;('.$weekStr[date('w', $time)].')';

        $this->view->assign('datelist', $datelist);

        $user_id = 0;
        if($this->auth->id) {
            $user_id = $this->auth->id;
        }

        $page = $this->request->request('page', 1);
        $mPred = model('Pred')->alias('p')
        ->join("user_to_analyst uta","uta.analyst_id = ".$id." AND uta.user_id = ".$user_id." AND uta.buydate = ".$starttime_start, "LEFT")
        ->join("event e","e.id = p.event_id")
        ->join("analyst a","a.id = p.analyst_id")
        ->join("event_category ec","e.event_category_id = ec.id AND ec.status = 1 AND ec.id = ".$cat_id)
        ->field("p.*, e.guests, e.master, e.starttime, uta.id as uta_id, a.free, a.user_id as auid")
        ->where('p.analyst_id = '.$id.' AND e.starttime < '.$starttime_end.' AND e.starttime > '.$starttime_start)->order('e.starttime','desc')->select();
        // $count = $mPred->total();
        // $pagelist = $mPred->render();
        $mPred = $this->createPredStr($mPred);
        // $this->view->assign('count', $count);
        // $this->view->assign('page', $page);
        // $this->view->assign('pagelist', $pagelist);
        
        $buy_btn = true;
        $mAnalyst = model('Analyst')->where("id = ".$id." AND user_id = ".$user_id)->find();
        if(!$mPred OR $mAnalyst){
            $buy_btn = false;
        }else{
            $checktime = true;
            foreach($mPred as $v){
                if($v->free == 1){
                    $buy_btn = false;
                }
                if($v->uta_id){
                    $buy_btn = false;
                }
                if($v->starttime > time()){
                    $checktime = false;
                }
            }
            if($checktime){
                $buy_btn = false;
            }
        }
        $this->view->assign('buy_btn', $buy_btn);
        $this->view->assign('mPred', $mPred);

        //歷史紀錄
        
        $page = $this->request->request('page', 1);
        $mHPred = model('Pred')->alias('p')
        ->join("user_to_analyst uta","uta.analyst_id = ".$id." AND uta.user_id = ".$user_id." AND uta.buydate = ".$starttime_start, "LEFT")
        ->join("event e","e.id = p.event_id")
        ->join("analyst a","a.id = p.analyst_id")
        ->join("event_category ec","e.event_category_id = ec.id AND ec.status = 1 AND ec.id = ".$cat_id)
        ->field("p.*, e.guests, e.master, e.starttime, uta.id as uta_id, a.free, a.user_id as auid")
        ->where('p.comply <> 0 AND p.analyst_id = '.$id)->order('e.starttime','desc')->paginate(20, false, $this->paginate_config);
        $count = $mHPred->total();
        $pagelist = $mHPred->render();
        $mHPred = $this->createPredStr($mHPred);
        
        $this->view->assign('count', $count);
        $this->view->assign('page', $page);
        $this->view->assign('pagelist', $pagelist);
        $this->view->assign('mHPred', $mHPred);

        return $this->view->fetch('analyst/pred');
    }
    
    public function createPredStr($mPred){
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
                
                if($v->free != 1 AND !$v->uta_id AND $v->starttime > time() AND $v->auid != $this->auth->id){
                    $v->pred_str = "<span class='text-gray'>未購買</span>";
                }
                if($v->starttime > time()){
                    $v->eventstatus = "<span class='text-gray'>未開賽</span>";
                }else{
                    $v->eventstatus = "<span class='text-orange'>已開賽</span>";
                }
            }
        }
        return $mPred;
    }

    
    public function checkPred($id){
        $mAnalyst = model('Analyst')->alias('a')
        ->join("analyst_to_event_category atc","atc.analyst_id = a.id")
        ->join("event_category ec","atc.event_category_id = ec.id AND ec.status = 1")
        ->field("a.*, atc.id as atc_id, atc.event_category_id, atc.autopred_today as atc_autopred_today, atc.autopred_count as atc_autopred_count, ec.title as cat_name")
        ->where("a.autopred = 1 AND a.status = 1 AND atc.autopred_today < atc.autopred_count AND a.id = ".$id)->select();
        if(!$mAnalyst){
            // Log::notice("[".__METHOD__."] 查無分析師,或是無效的分類,或是已預測完");
        }else{
            foreach($mAnalyst as $v){
                Log::notice("分析師:[".$v->id."]".$v->analyst_name." / 分類:[".$v->event_category_id."]".$v->cat_name);
                $pcount = $v->atc_autopred_count - $v->atc_autopred_today;
                for($i = 0;$i <= $pcount;$i++){
                    $mEvent = model('Event')->alias('e')
                    ->join("analyst_to_event_category atc","atc.event_category_id = e.event_category_id AND atc.analyst_id = ".$v->id)
                    ->join("event_category ec","ec.id = e.event_category_id AND ec.status = 1 AND ec.id = ".$v->event_category_id)
                    ->join("pred p1","e.id = p1.event_id AND p1.pred_type = 1 AND p1.analyst_id = ".$v->id,"LEFT")
                    ->join("pred p2","e.id = p2.event_id AND p2.pred_type = 2 AND p2.analyst_id = ".$v->id,"LEFT")
                    ->field("e.*, p1.id as type1, p2.id as type2")
                    ->where("((p2.id IS NULL AND e.master_refund <> '0' AND e.guests_refund <> '0') OR (p1.id IS NULL AND e.bigscore <> '0')) AND e.starttime > ".strtotime(date("Y-m-d")))->orderRaw('RAND()')->find();
                    
                    if($mEvent){
                        if($mEvent->type1 == null AND $mEvent->type2 == null AND $mEvent->bigscore == '0' AND ($mEvent->master_refund == '0' OR $mEvent->guests_refund == '0')){
                            $pred_type = Rand(1,2);
                        }elseif($mEvent->type1 == null OR $mEvent->bigscore == '0'){
                            $pred_type = 1;
                        }else{
                            $pred_type = 2;
                        }
                        // Log::notice("pred_type:".$pred_type);
                        
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
                        ->where("p.analyst_id = ".$v->id." AND p.event_id = ".$mEvent->id." AND p.pred_type = ".$other_type)->find();
                        if($mPredOtherType){
                            $predtime = $mPredOtherType->predtime;
                            $bigscore = $mPredOtherType->bigscore;
                            $master_refund = $mPredOtherType->master_refund;
                            $guests_refund = $mPredOtherType->guests_refund;
                        }else{
                            // $htime = strtotime(date("Y-m-d H:i:s", $mEvent->starttime)." -1 hours");
                            $htime = time();
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
                        // $predtime = Rand($mEvent->createtime, time());
                        // Log::notice($predtime);
                        
                        $ptpred = [
                            'event_id' => $mEvent->id,
                            'analyst_id' => $v->id,
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
                        if($mEvent->status == 1){
                            // Log::notice("status:1");
                            $ptpred['master_score'] = $mEvent->master_score;
                            $ptpred['guests_score'] = $mEvent->guests_score;
                            if($pred_type == 1){
                                // Log::notice("讓分");
                                if($master_refund != null){
                                    // Log::notice($master_refund);
                                    $winscore = $mEvent->master_score - $mEvent->guests_score;
                                    $refund = $master_refund;
                                    $minus = false;
                                    $l = strpos($refund, '-');
                                    if($l === false){
                                        //+
                                        $l = strpos($refund, '+');
                                        if($l === false){
                                            $l = mb_strlen($refund);
                                        }
                                    }else{
                                        //-
                                        $minus = true;
                                    }
                                    $refund = substr($refund, 0, $l);
                                    if($minus){
                                        $refund = $refund+1;
                                    }
                                    if($winscore < $refund AND $ptpred['winteam'] == 0){
                                        $ptpred['comply'] = 1;
                                    }elseif($winscore >= $refund AND $ptpred['winteam'] == 1){
                                        $ptpred['comply'] = 1;
                                    }else{
                                        $ptpred['comply'] = 2;
                                    }
                                }elseif($guests_refund != null){
                                    // Log::notice($guests_refund);
                                    $winscore = $mEvent->guests_score - $mEvent->master_score;
                                    $refund = $guests_refund;
                                    $minus = false;
                                    $l = strpos($refund, '-');
                                    if($l === false){
                                        //+
                                        $l = strpos($refund, '+');
                                        if($l === false){
                                            $l = mb_strlen($refund);
                                        }
                                    }else{
                                        //-
                                        $minus = true;
                                    }
                                    $refund = substr($refund, 0, $l);
                                    if($minus){
                                        $refund = $refund+1;
                                    }
                                    if($winscore < $refund AND $ptpred['winteam'] == 1){
                                        $ptpred['comply'] = 1;
                                    }elseif($winscore >= $refund AND $ptpred['winteam'] == 0){
                                        $ptpred['comply'] = 1;
                                    }else{
                                        $ptpred['comply'] = 2;
                                    }
                                }else{
                                    Log::notice('讓分有誤');
                                    continue;
                                }
                            }else{
                                // Log::notice("大小");
                                $totalscore = $mEvent->master_score + $mEvent->guests_score;
                                $minus = false;
                                $l = strpos($bigscore, '-');
                                if($l === false){
                                    //+
                                    $l = strpos($bigscore, '+');
                                    if($l === false){
                                        $l = mb_strlen($bigscore);
                                    }
                                }else{
                                    //-
                                    $minus = true;
                                }
                                $bigscore = substr($bigscore, 0, $l);
                                if($minus){
                                    $bigscore = $bigscore+1;
                                }

                                if($totalscore < $bigscore AND $ptpred['bigsmall'] == 0){
                                    $ptpred['comply'] = 1;
                                }elseif($totalscore >= $bigscore AND $ptpred['bigsmall'] == 1){
                                    $ptpred['comply'] = 1;
                                }else{
                                    $ptpred['comply'] = 2;
                                }
                            }
                        }

                        model('Pred')::create($ptpred);
                        $mEvent->pred = $mEvent->pred+1;
                        $mEvent->save();

                        Log::notice("預測師id[".$v->id."] 自動預測 預測時間[".date("Y-m-d H:i:s",$predtime)."] 預測賽事 ".json_encode($ptpred, JSON_UNESCAPED_UNICODE));
                    }else{
                        Log::notice("沒有可預測的賽事");
                        break;
                    }
                }
                $mAtc = model('Analysttoeventcategory')->get($v->atc_id);
                if($mAtc){
                    $mAtc->autopred_today = $mAtc->autopred_count;
                    $mAtc->save();
                }
            }
        }
    }
    

}
