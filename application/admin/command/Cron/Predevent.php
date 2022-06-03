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

use app\common\model\Event;
use app\common\model\Eventcategory;
use app\common\model\Analyst;
use app\common\model\Pred;

class Predevent extends Command
{
    protected $taskName = '預測賽事';
    protected $site = [];

    protected function configure(){
        $this->setName('Predevent')->setDescription("預測賽事");
    }

    protected function execute(Input $input, Output $output){
        Log::init(['type' => 'File', 'log_name' => 'cron_Predevent']);
        $this->site = Config::get("site");
        $this->Predevent();
    }
    
    public function Predevent()
    {
        try {
            $func_name = 'Predevent';
            Log::notice("[command][Cron][".$func_name."] 開始執行 ".date('Y-m-d H:i:s',time()));
            $modelEvent = new Event;
            $modelEventcategory = new Eventcategory;
            $modelAnalyst = new Analyst;
            $modelPred = new Pred;

            $mAnalyst = $modelAnalyst->alias('a')
            ->where("a.status = 1 AND a.autopred = 1 ")->select();
            if($mAnalyst){
                foreach($mAnalyst as $v){
                    
                    $dopred = [
                        1 => 50,
                        0 => 50,
                    ];

                    //讓分
                    $mEvent = $modelEvent->alias('e')
                    ->join("analyst_to_event_category atc","atc.event_category_id = e.event_category_id AND atc.analyst_id = ".$v->id)
                    ->join("event_category ec","ec.id = e.event_category_id AND ec.status = 1")
                    ->join("pred p","p.event_id = e.id AND p.pred_type = 1 AND p.analyst_id = ".$v->id,"LEFT")
                    ->join("pred pall","pall.event_id = e.id AND p.pred_type = 1 ","LEFT")
                    ->field("e.*, ec.analyst as analystcount, count(pall.id) as pallcount")
                    ->where("p.id IS NULL AND e.master_refund <> '0' AND e.guests_refund <> '0' AND e.starttime > ".time())->group('e.id')->having("pallcount < ((analystcount)*0.1) OR pallcount = 0")->select();
                    
                    $dop1 = true;
                    $ch = Random::lottery($dopred);
                    if($ch == 0){
                        $dop1 = false;
                    }elseif(!$mEvent){
                        $dop1 = false;
                    }

                    if($dop1){
                        //讓分
                        if($mEvent){
                            Log::notice("[command][Cron][".$func_name."] 分析師:".$v->id);
                            foreach($mEvent as $va){
                                // Log::notice("[command][Cron][".$func_name."] analystcount:".$va->analystcount);
                                // Log::notice("[command][Cron][".$func_name."] pallcount:".$va->pallcount);
                                // continue;
                                $dopred = [
                                    1 => 80,
                                    0 => 20,
                                ];
                                // Log::notice("[command][Cron][".$func_name."] dopred:".Random::lottery($dopred));
                                if(Random::lottery($dopred) == 1){
                                    $rWinteam = [
                                        1 => 50,
                                        0 => 50,
                                    ];
                                    $ptpred = [
                                        'event_id' => $va->id,
                                        'analyst_id' => $v->id,
                                        'winteam' => Random::lottery($rWinteam),
                                        'master_refund' => $va->master_refund,
                                        'guests_refund' => $va->guests_refund,
                                        'pred_type' => 1,
                                        'isauto' => 1,
                                    ];
                                    $modelPred::create($ptpred);
                                    $va->pred = $va->pred+1;
                                    $va->save();
                                    Log::notice("[command][Cron][".$func_name."] 預測賽事 讓分 ".json_encode($ptpred, JSON_UNESCAPED_UNICODE));
                                }else{
                                    Log::notice("[command][Cron][".$func_name."] 不預測 讓分");
                                }
                            }
                        }else{
                            // Log::notice("[command][Cron][".$func_name."] 已預測");
                        }

                    }else{
                        //大小
                        $mEvent = $modelEvent->alias('e')
                        ->join("analyst_to_event_category atc","atc.event_category_id = e.event_category_id AND atc.analyst_id = ".$v->id)
                        ->join("event_category ec","ec.id = e.event_category_id AND ec.status = 1")
                        ->join("pred p","p.event_id = e.id AND p.pred_type = 2 AND p.analyst_id = ".$v->id,"LEFT")
                        ->join("pred pall","pall.event_id = e.id AND p.pred_type = 2 ","LEFT")
                        ->field("e.*, ec.analyst as analystcount, count(pall.id) as pallcount")
                        ->where("p.id IS NULL AND e.bigscore <> '0' AND e.starttime > ".time())->group('e.id')->having("pallcount < ((analystcount)*0.1) OR pallcount = 0")->select();
                        // ->where("p.id IS NULL AND e.starttime > ".time())->group('e.id')->select();
                        if($mEvent){
                            Log::notice("[command][Cron][".$func_name."] 分析師:".$v->id);
                            foreach($mEvent as $va){
                                // Log::notice("[command][Cron][".$func_name."] analystcount:".$va->analystcount);
                                // Log::notice("[command][Cron][".$func_name."] pallcount:".$va->pallcount);
                                // continue;
                                $dopred = [
                                    1 => 80,
                                    0 => 20,
                                ];
                                // Log::notice("[command][Cron][".$func_name."] dopred:".Random::lottery($dopred));
                                if(Random::lottery($dopred) == 1){
                                    $rBigsmall = [
                                        1 => 50,
                                        0 => 50,
                                    ];
                                    $ptpred = [
                                        'event_id' => $va->id,
                                        'analyst_id' => $v->id,
                                        'bigsmall' => Random::lottery($rBigsmall),
                                        'bigscore' => $va->bigscore,
                                        'pred_type' => 2,
                                        'isauto' => 1,
                                    ];
                                    $modelPred::create($ptpred);
                                    $va->pred = $va->pred+1;
                                    $va->save();
                                    Log::notice("[command][Cron][".$func_name."] 預測賽事 大小 ".json_encode($ptpred, JSON_UNESCAPED_UNICODE));
                                }else{
                                    Log::notice("[command][Cron][".$func_name."] 不預測 大小 ");
                                }
                            }
                        }else{
                            // Log::notice("[command][Cron][".$func_name."] 已預測");
                        }
                    }
                }
            }else{
                Log::notice("[command][Cron][".$func_name."] 無可用分析師");
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


}