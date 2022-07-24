<?php
namespace app\admin\command\cron;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Exception;
use think\Log;
use think\Config;
use fast\Random;

use app\common\model\Analyst;
use app\common\model\Pred;
use app\common\model\Event;
use app\common\model\Eventparam;
use app\common\model\Eventcategory;
use app\common\model\Analysttoeventcategory;
use app\common\model\Rank;
use app\common\model\Rankcontent;
use app\common\model\Analysttitle;

class Dayreport extends Command
{
    protected $taskName = '日結算';
    protected $site = [];
    protected $gameurl = "https://ag.bl868.net";

    protected function configure(){
        $this->setName('Dayreport')->setDescription("日結算");
    }

    protected function execute(Input $input, Output $output){
        Log::init(['type' => 'File', 'log_name' => 'cron_Dayreport']);
        $this->site = Config::get("site");
        // $this->Eventreport();
        // $this->Geteventcat();
        $this->Titlereport();
        // if(date('w') == 2){
        //     $this->Weekreport();
        // }
    }
    
    public function Eventreport()
    {
        try {
            $func_name = 'Eventreport';
            Log::notice("[command][Cron][".$func_name."] 開始執行 ".date('Y-m-d H:i:s',time()));
            
            //刪除1個月前資料
            $monthTime = strtotime(date("Y-m-d")." -2 month");
            $modelEvent = new Event;
            $modelEvent->where("createtime < ".$monthTime)->delete();
            $modelEventparam = new Eventparam;
            $modelEventparam->where("createtime < ".$monthTime)->delete();
            $modelPred = new Pred;
            $modelPred->where("createtime < ".$monthTime)->delete();
            
            $modelAnalysttoeventcategory = new Analysttoeventcategory;

            //補預測
            $ysday = strtotime(date("Y-m-d")." -2 day");
            $modelAnalyst = new Analyst;
            $mAnalyst = $modelAnalyst->alias('a')
            ->join("analyst_to_event_category atc","atc.analyst_id = a.id")
            ->join("event_category ec","atc.event_category_id = ec.id AND ec.status = 1")
            ->field("a.*, atc.id as atc_id, atc.event_category_id, atc.autopred_today as atc_autopred_today, atc.autopred_count as atc_autopred_count, ec.title as cat_name")
            ->where("(a.status = 1 OR (a.status = 0 AND atc.autopred_today <> 0)) AND a.autopred = 1 ")->order("updatetime","asc")->select();
            if($mAnalyst){
                foreach($mAnalyst as $v){
                    if($v->atc_autopred_today < $v->atc_autopred_count AND $v->status == 1){
                        $pcount = $v->atc_autopred_count - $v->atc_autopred_today;
                        Log::notice("分析師:[".$v->id."]".$v->analyst_name." / 分類:[".$v->event_category_id."]".$v->cat_name);
                        for($i = 0;$i <= $pcount;$i++){
                            $modelEvent = new Event;
                            $mEvent = $modelEvent->alias('e')
                            ->join("analyst_to_event_category atc","atc.event_category_id = e.event_category_id AND atc.analyst_id = ".$v->id)
                            ->join("event_category ec","ec.id = e.event_category_id AND ec.status = 1 AND ec.id = ".$v->event_category_id)
                            ->join("pred p1","e.id = p1.event_id AND p1.pred_type = 1 AND p1.analyst_id = ".$v->id,"LEFT")
                            ->join("pred p2","e.id = p2.event_id AND p2.pred_type = 2 AND p2.analyst_id = ".$v->id,"LEFT")
                            ->field("e.*, p1.id as type1, p2.id as type2")
                            ->where("((p2.id IS NULL AND e.master_refund <> '0' AND e.guests_refund <> '0') OR (p1.id IS NULL AND e.bigscore <> '0')) AND e.starttime > ".$ysday." AND e.starttime < ".time())->orderRaw('RAND()')->find();
                            
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
                                $modelPred = new Pred;
                                $mPredOtherType = $modelPred->alias('p')
                                ->field('p.*')
                                ->where("p.analyst_id = ".$v->id." AND p.event_id = ".$mEvent->id." AND p.pred_type = ".$other_type)->find();
                                if($mPredOtherType){
                                    $predtime = $mPredOtherType->predtime;
                                    $bigscore = $mPredOtherType->bigscore;
                                    $master_refund = $mPredOtherType->master_refund;
                                    $guests_refund = $mPredOtherType->guests_refund;
                                }else{
                                    $htime = strtotime(date("Y-m-d H:i:s", $mEvent->starttime)." -1 hours");
                                    $predtime = Rand($mEvent->createtime, $htime);
                                    if($predtime < $mEvent->updatetime){
                                        $modelEventparam = new Eventparam;
                                        $mEventparam = $modelEventparam->alias('ep')
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

                                $modelPred = new Pred;
                                $modelPred::create($ptpred);
                                $mEvent->pred = $mEvent->pred+1;
                                $mEvent->save();

                                Log::notice("預測師id[".$v->id."] 自動預測 預測時間[".date("Y-m-d H:i:s",$predtime)."] 預測賽事 ".json_encode($ptpred, JSON_UNESCAPED_UNICODE));
                            }else{
                                Log::notice("沒有可預測的賽事");
                                break;
                            }
                        }
                    }

                    $mAtc = $modelAnalysttoeventcategory->get($v->atc_id);
                    if($mAtc){
                        $mAtc->autopred_today = 0;
                        $mAtc->autopred_count = rand(1,5);
                        $mAtc->save();
                    }
                }
            }

            Log::notice("[command][Cron][".$func_name."] 完整結束 ".date('Y-m-d H:i:s',time()));
        } catch (ValidateException $e) {
            Log::notice("[command][Cron][".$func_name."] ValidateException :".$e->getMessage());
        } catch (PDOException $e) {
            Log::notice("[command][Cron][".$func_name."] PDOException :".$e->getMessage());
        } catch (Exception $e) {
            if($v){
                Log::notice("v data:");
                Log::notice($v);
            }
            if($mEvent){
                Log::notice("mEvent data:");
                Log::notice($mEvent);
            }
            Log::notice("[command][Cron][".$func_name."] Exception :".$e->getMessage());
        }
    }

    
    public function Geteventcat()
    {
        try {
            $func_name = 'Geteventcat';
            Log::notice("[command][Cron][".$func_name."] 開始執行 ".date('Y-m-d H:i:s',time()));
            $modelEventcategory = new Eventcategory;

            $url = $this->gameurl."/login.php";
            $post = [
                'luserid' => $this->site['luserid'],
                'lpassword' => $this->site['lpassword'],
                'paction' => 'login-processing',
                'remember' => 1
            ];
            $cookie = './cookie.txt';

            // $game_category = [
            //     '1' => '美棒',
            //     '4' => '日棒',
            //     '5' => '韓棒',
            //     '3' => '中華職棒',
            //     '9' => '其他棒球',
            //     '7' => '籃球',
            //     '11' => '其他籃球',
            //     '6' => '冰球',
            //     '10' => '其他冰球',
            //     '14' => '頂級足球',
            //     '13' => '其他足球',
            //     // '2' => '彩球',
            //     // '8' => '賽馬/賽狗',
            //     '15' => '美式足球',
            //     // '16' => '電子競技',
            // ];
            
            $getcategory_title = [
                '美棒',
                '日棒',
                '韓棒',
                '中華職棒',
                '其他棒球',
                '籃球',
                '其他籃球',
                '冰球',
                '其他冰球',
                '頂級足球',
                '其他足球',
                // '彩球',
                // '賽馬/賽狗',
                '美式足球',
                // '電子競技',
            ];

            Log::notice("[command][Cron][".$func_name."] 指定類別".json_encode($getcategory_title, JSON_UNESCAPED_UNICODE));

            Log::notice("[command][Cron][".$func_name."] 停用所有類別");
            $mAll = $modelEventcategory->all();
            if($mAll){
                foreach($mAll as $ma){
                    $ma->status = 0;
                    $ma->save();
                }
            }

            $category_title_now = []; //當前有效類別

            Log::notice("[command][Cron][".$func_name."] 模擬登錄");
            //模擬登錄
            $this->login_post($url, $cookie, $post);

            Log::notice("[command][Cron][".$func_name."] 抓取類別");
            //爬菜單
            $menu = $this->get_content($this->gameurl.'/personal_info_manager.php', $cookie);
            // Log::notice($menu);
            $menu_arr = explode(PHP_EOL,$menu);
            $mtitle = false; //菜單標題
            // Log::notice($menu_arr);
            if(sizeof($menu_arr)>0){
                foreach($menu_arr as $k=>$content_line){
                    $content_line = trim($content_line);
                    if($content_line != ''){
                        if($mtitle){
                            $start_str = 'class="title">';
                            $end_str = '</span>';
                            $category_title = $this->getValue($content_line, $start_str, $end_str);
                            // Log::notice("[command][Cron][".$func_name."] 取得類別:".$category_title);
                            if(in_array($category_title, $getcategory_title)){ 
                                $category_title_now[] = $category_title;
                                $url_content_line = trim($menu_arr[$k-2]); //取回路徑
                                $start_str = 'href="';
                                $end_str = '" class="nav-link';
                                $category_url = $this->getValue($url_content_line, $start_str, $end_str);
                                $start_str = 'game_category=';
                                $end_str = '" class="nav-link';
                                $game_category = $this->getValue($url_content_line, $start_str, $end_str);
                                // Log::notice("[command][Cron][".$func_name."] 取得路徑:".$category_url);

                                $mEventcategory = $modelEventcategory->where("title = '".$category_title."'")->find();
                                if($mEventcategory){
                                    $mEventcategory->url = $category_url;
                                    $mEventcategory->game_category = $game_category;
                                    $mEventcategory->status = 1;
                                    $mEventcategory->save();
                                    Log::notice("[command][Cron][".$func_name."] 啟用類別:".$category_title);
                                }else{
                                    Log::notice("[command][Cron][".$func_name."] 新類別,建立");
                                    $params = [
                                        'url' => $category_url,
                                        'title' => $category_title,
                                        'game_category' => $game_category,
                                    ];
                                    $modelEventcategory::create($params);
                                }
                            }else{
                                // Log::notice("[command][Cron][".$func_name."] 類別無指定,跳過");
                            }
                            $mtitle = false;
                            continue;
                        }
                        if($content_line == '<i class="icon-calculator"></i>'){
                            $mtitle = true;
                        }
                    }
                }
            }else{
                Log::notice("[command][Cron][".$func_name."] 查無類別");
                Log::notice($menu);
            }

            Log::notice("[command][Cron][".$func_name."] 當前有效類別:".implode(",", $category_title_now));
            Log::notice("[command][Cron][".$func_name."] 完整結束 ".date('Y-m-d H:i:s',time()));
        } catch (ValidateException $e) {
            Log::notice("[command][Cron][".$func_name."] ValidateException :".$e->getMessage());
        } catch (PDOException $e) {
            Log::notice("[command][Cron][".$func_name."] PDOException :".$e->getMessage());
        } catch (Exception $e) {
            Log::notice("[command][Cron][".$func_name."] Exception :".$e->getMessage());
        }
    }
    
    public function Titlereport()
    {
        try {
            $func_name = 'Titlereport';
            Log::notice("[command][Cron][".$func_name."] 開始執行 ".date('Y-m-d H:i:s',time()));
            
            //刪除稱號
            $modelAnalysttitle = new Analysttitle;
            $modelAnalysttitle->delete();
            
            //1.連贏N場
            //2.連贏N天
            //3.近N場 過N場
            //4.近N日 過N
            //5.近N日 N過N
            //5.近期過N場

            $modelAnalyst = new Analyst;
            $mAnalyst = $modelAnalyst->where("status = 1")->select();
            
            $lmonth = strtotime(date("Y-m-d")." -1 month");
            $mAnalyst = $modelAnalyst->alias('a')
            ->join("pred p","p.analyst_id = a.id")
            ->field("a.*")
            ->where("a.status = 1 AND p.createtime > ".$lmonth)->group("a.id")->select();
            if($mAnalyst){
                foreach($mAnalyst as $v){
                    Log::notice("[command][Cron][".$func_name."] 處理分析師id:".$v->id);
                    $this->titleType1($v->id);
                    // $this->titleType2($v->id);
                    // $this->titleType3($v->id);
                    // $this->titleType4($v->id);
                    // $this->titleType5($v->id);
                }
            }else{
                Log::notice("[command][Cron][".$func_name."] 沒有有效分析師");
            }

            Log::notice("[command][Cron][".$func_name."] 完整結束 ".date('Y-m-d H:i:s',time()));
        } catch (ValidateException $e) {
            Log::notice("[command][Cron][".$func_name."] ValidateException :".$e->getMessage());
        } catch (PDOException $e) {
            Log::notice("[command][Cron][".$func_name."] PDOException :".$e->getMessage());
        } catch (Exception $e) {
            Log::notice("[command][Cron][".$func_name."] Exception :".$e->getMessage());
        }
    }

    
    public function titleType1($id)
    {
        Log::notice("[command][Cron][".$func_name."] 檢查稱號1");
        try{
            $modelPred = new Pred;
            $type = 1;
            $lmonth = strtotime(date("Y-m-d")." -1 month");
            $mPred = $modelPred->alias('p')
            ->join("event e","p.event_id = e.id")
            ->field("p.*")
            ->where("p.comply <> 0 AND p.analyst_id = ".$id." AND e.starttime > ".$lmonth)->order(["e.starttime"=>"desc","p.predtime"=>"desc"])->select();
            if($mPred){
                $win = 0;
                foreach($mPred as $v){
                    if($v->comply == 1){
                        $win++;
                    }else{
                        break;
                    }
                }
                if($win >= 4){
                    $modelAnalysttitle = new Analysttitle;

                    $param = [
                        "title" => "連贏".$win."場",
                        "type" => $type,
                        "analyst_id" => $id,
                    ];
                    $modelAnalysttitle::create($param);
                }
            }else{
                Log::notice("[command][Cron][".$func_name."] 查無預測");
            }
        } catch (ValidateException $e) {
            Log::notice("[command][Cron][".$func_name."] ValidateException :".$e->getMessage());
        } catch (PDOException $e) {
            Log::notice("[command][Cron][".$func_name."] PDOException :".$e->getMessage());
        } catch (Exception $e) {
            Log::notice("[command][Cron][".$func_name."] Exception :".$e->getMessage());
        }
    }

    public function Weekreport()
    {
        try {
            $func_name = 'Weekreport';
            Log::notice("[command][Cron][".$func_name."] 開始執行 ".date('Y-m-d H:i:s',time()));
            
            $modelEventcategory = new Eventcategory;
            $mEventcategory = $modelEventcategory->where('status = 1')->select();
            if($mEventcategory){
                foreach($mEventcategory as $v){
                    Log::notice("[command][Cron][".$func_name."] 分類:".$v->title." 建立排行...");
                    $this->createRank($v->id);
                }
                Log::notice("[command][Cron][".$func_name."] 完成建立排行");
            }else{
                Log::notice("[command][Cron][".$func_name."] 沒有可用分類");
            }

            Log::notice("[command][Cron][".$func_name."] 完整結束 ".date('Y-m-d H:i:s',time()));
        } catch (ValidateException $e) {
            Log::notice("[command][Cron][".$func_name."] ValidateException :".$e->getMessage());
        } catch (PDOException $e) {
            Log::notice("[command][Cron][".$func_name."] PDOException :".$e->getMessage());
        } catch (Exception $e) {
            Log::notice("[command][Cron][".$func_name."] Exception :".$e->getMessage());
        }
    }

    
    public function createRank($id = 0)
    {
        try {
            $func_name = 'createRank';
            
            $todayTime = strtotime(date("Y-m-d")." -1 Day");
            // $todayTime = strtotime(date("Y-m-d")." +2 week");
            // $weekTime = strtotime(date("Y-m-d")." -1 week");
            $weekTime = strtotime(date("Y-m-d",$todayTime)." -2 week");
            $modelAnalyst = new Analyst;
            $mAnalyst = $modelAnalyst->alias('a')
            ->join("pred p","a.id = p.analyst_id")
            ->join("event e","e.id = p.event_id")
            ->join("event_category ec","ec.id = e.event_category_id AND ec.id = ".$id)
            ->field("a.id, ec.rankrule as rankrule, count(case when p.comply = 1 then 0 end)/count(p.id)*100 as winrate, count(case when p.comply = 1 then 0 end) as win,count(p.id) - count(case when p.comply = 1 then 0 end) as lose")
            ->where("p.comply <> 0 AND e.starttime > ".$weekTime." AND e.starttime < ".$todayTime."")->group("a.id")->having('count(p.id) >= rankrule AND win > 1')->order("winrate","desc")->limit(20)->select();
            if($mAnalyst){
                $param = [
                    'rtime1' => $weekTime,
                    'rtime2' => $todayTime,
                    'event_category_id' => $id,
                ];
                $modelRank = new Rank;
                $mRank = $modelRank::create($param);
                if($mRank){
                    foreach($mAnalyst as $k=>$v){
                        $param = [
                            'rank_id' => $mRank->id,
                            'analyst_id' => $v->id,
                            'winrate' => $v->winrate,
                            'win' => $v->win,
                            'lose' => $v->lose,
                            'rank' => $k+1,
                        ];
                        $modelRankcontent = new Rankcontent;
                        $modelRankcontent::create($param);
                    }
                }else{
                    Log::notice("[command][Cron][".$func_name."] 建立排行失敗");
                }
            }else{
                Log::notice("[command][Cron][".$func_name."] 無預測");
            }
        } catch (ValidateException $e) {
            Log::notice("[command][Cron][".$func_name."] ValidateException :".$e->getMessage());
        } catch (PDOException $e) {
            Log::notice("[command][Cron][".$func_name."] PDOException :".$e->getMessage());
        } catch (Exception $e) {
            Log::notice("[command][Cron][".$func_name."] Exception :".$e->getMessage());
        }
    }

    private function getValue($content, $start_str, $end_str) {
        $content = str_replace('&nbsp;','',str_replace('<br>',' ',str_replace('<br/>',' ',trim($content))));
        $start_index = strpos($content, $start_str);
        $end_index = strpos($content, $end_str);
        if($end_index !== false AND $start_index !== false){
            $start_index = $start_index + mb_strlen($start_str);
            return substr($content, $start_index, $end_index-$start_index);
        }else{
            return '';
        }
    }

    private function login_post($url, $cookie, $post) {
        $curl = curl_init();//初始化curl模塊
        curl_setopt($curl, CURLOPT_URL, $url);//登錄提交的地址
        curl_setopt($curl, CURLOPT_HEADER, 0);//是否显示头信息
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0);//是否自動顯示返回的信息
        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie); //設置Cookie信息保存在指定的文件中
        curl_setopt($curl, CURLOPT_POST, 1);//post方式提交
        
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));//要提交的信息
        curl_exec($curl);//執行cURL
        curl_close($curl);//關閉cURL資源，並且釋放系統資源
    }
        
    private function get_content($url, $cookie) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);//是否显示头信息
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie); //讀取cookie
        
        $rs = curl_exec($ch); //執行cURL抓取頁面內容
        curl_close($ch);
        return $rs;
    }


}