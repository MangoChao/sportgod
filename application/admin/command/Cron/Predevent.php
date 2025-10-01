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

    protected function configure()
    {
        $this->setName('Predevent')->setDescription("預測賽事");
    }

    protected function execute(Input $input, Output $output)
    {
        Log::init(['type' => 'File', 'log_name' => 'cron_Predevent']);
        $this->site = Config::get("site");
        $this->Predevent();
    }

    public function Predevent()
    {
        try {
            $func_name = 'Predevent';
            Log::notice("[command][Cron][" . $func_name . "] 開始執行 " . date('Y-m-d H:i:s', time()));
            $modelEvent = new Event;
            $modelEventcategory = new Eventcategory;
            $modelAnalyst = new Analyst;
            $modelPred = new Pred;

            $mAnalyst = $modelAnalyst->alias('a')
                ->where("a.status = 1 AND a.autopred = 1 AND a.autopred_count > 0 AND IFNULL(a.autopred_today,0) < IFNULL(a.autopred_count,0)")
                ->select();
            if ($mAnalyst) {
                foreach ($mAnalyst as $v) {
                    // 先在『起始處』過濾掉今日額度已滿的分析師，避免後續白跑查詢
                    $remain = max(0, (int)$v->autopred_count - (int)$v->autopred_today);
                    if ($remain <= 0) {
                        Log::notice("[command][Cron][{$func_name}] analyst={$v->id} 今日上限已滿(起始篩選)");
                        continue;
                    }
                    $made = 0;
                    // 先決定要跑哪種玩法（1=讓分, 2=大小）
                    $choice = Random::lottery([1 => 50, 2 => 50]);

                    // 依選擇查詢候選賽事；若沒找到，嘗試另一種
                    $mEvent = null;
                    if ($choice === 1) {
                        $mEvent = $this->querySpreadCandidates($v->id, $modelEvent);
                        if (!$mEvent || count($mEvent) === 0) {
                            $mEvent = $this->queryTotalCandidates($v->id, $modelEvent);
                            $choice = 2; // fallback 成大小
                        }
                    } else {
                        $mEvent = $this->queryTotalCandidates($v->id, $modelEvent);
                        if (!$mEvent || count($mEvent) === 0) {
                            $mEvent = $this->querySpreadCandidates($v->id, $modelEvent);
                            $choice = 1; // fallback 成讓分
                        }
                    }

                    if ($mEvent && count($mEvent) > 0) {
                        Log::notice("[command][Cron][" . $func_name . "] 分析師:" . $v->id . " 玩法:" . ($choice === 1 ? '讓分' : '大小'));
                        foreach ($mEvent as $va) {
                            // 每場 80% 機率落單
                            $doPred = (Random::lottery([1 => 80, 0 => 20]) === 1);
                            if (!$doPred) {
                                Log::notice("[command][Cron][" . $func_name . "] 不預測 " . ($choice === 1 ? '讓分' : '大小'));
                                continue;
                            }

                            if ($choice === 1) {
                                // 讓分
                                $winteam = Random::lottery([1 => 50, 0 => 50]);
                                $ptpred = [
                                    'event_id' => $va->id,
                                    'analyst_id' => $v->id,
                                    'winteam' => $winteam,
                                    'master_refund' => $va->master_refund,
                                    'guests_refund' => $va->guests_refund,
                                    'pred_type' => 1,
                                    'isauto' => 1,
                                ];
                            } else {
                                // 大小
                                $bigsmall = Random::lottery([1 => 50, 0 => 50]);
                                $ptpred = [
                                    'event_id' => $va->id,
                                    'analyst_id' => $v->id,
                                    'bigsmall' => $bigsmall,
                                    'bigscore' => $va->bigscore,
                                    'pred_type' => 2,
                                    'isauto' => 1,
                                ];
                            }

                            $incRows = $modelAnalyst
                                ->where('id', $v->id)
                                ->where("autopred_today < autopred_count")
                                ->setInc('autopred_today', 1);
                            if ($incRows === 0) {
                                Log::notice("[command][Cron][{$func_name}] analyst={$v->id} 今日上限已滿，停止本輪");
                                break;
                            }
                            $modelPred::create($ptpred);
                            $modelEvent->where('id', $va->id)->setInc('pred', 1);
                            $made++;
                            if ($made >= $remain) {
                                Log::notice("[command][Cron][{$func_name}] analyst={$v->id} 已達本輪剩餘額度 {$remain}，停止");
                                break;
                            }

                            Log::notice("[command][Cron][" . $func_name . "] 預測賽事 " . ($choice === 1 ? '讓分 ' : '大小 ') . json_encode($ptpred, JSON_UNESCAPED_UNICODE));
                        }
                    } else {
                        // 沒候選可預測
                        Log::notice("[command][Cron][" . $func_name . "] 無候選可預測 for 分析師:" . $v->id);
                    }
                }
            } else {
                Log::notice("[command][Cron][" . $func_name . "] 無可用分析師");
            }

            Log::notice("[command][Cron][" . $func_name . "] 完整結束 " . date('Y-m-d H:i:s', time()));
        } catch (ValidateException $e) {
            Log::notice("[command][Cron][" . $func_name . "] ValidateException :" . $e->getMessage());
        } catch (PDOException $e) {
            Log::notice("[command][Cron][" . $func_name . "] PDOException :" . $e->getMessage());
        } catch (Exception $e) {
            Log::notice("[command][Cron][" . $func_name . "] Exception :" . $e->getMessage());
        }
    }

    /**
     * 讓分候選查詢（pred_type = 1）
     */
    private function querySpreadCandidates($analystId, $modelEvent)
    {
        // NOT EXISTS 版本：避免以 LEFT JOIN + p.id IS NULL 方式判斷未預測
        // 並用派生表彙總 (pred_type=1) 的 event 累積預測數，取名 pc
        return $modelEvent->alias('e')
            ->join("analyst_to_event_category atc", "atc.event_category_id = e.event_category_id AND atc.analyst_id = " . $analystId)
            ->join("event_category ec", "ec.id = e.event_category_id AND ec.status = 1")
            ->join("(SELECT event_id, COUNT(*) AS cnt FROM pred WHERE pred_type = 1 GROUP BY event_id) pc", "pc.event_id = e.id", "LEFT")
            ->field("e.*, ec.analyst as analystcount, IFNULL(pc.cnt,0) as pallcount")
            ->where("NOT EXISTS (SELECT 1 FROM pred p WHERE p.event_id = e.id AND p.pred_type = 1 AND p.analyst_id = " . $analystId . ") AND e.master_refund <> '0' AND e.guests_refund <> '0' AND e.starttime > " . time() . " AND (pc.cnt IS NULL OR pc.cnt < (ec.analyst*0.1))")
            ->select();
    }

    /**
     * 大小候選查詢（pred_type = 2）
     */
    private function queryTotalCandidates($analystId, $modelEvent)
    {
        // NOT EXISTS 版本 + 大小玩法的彙總派生表 (pred_type=2)
        return $modelEvent->alias('e')
            ->join("analyst_to_event_category atc", "atc.event_category_id = e.event_category_id AND atc.analyst_id = " . $analystId)
            ->join("event_category ec", "ec.id = e.event_category_id AND ec.status = 1")
            ->join("(SELECT event_id, COUNT(*) AS cnt FROM pred WHERE pred_type = 2 GROUP BY event_id) pc", "pc.event_id = e.id", "LEFT")
            ->field("e.*, ec.analyst as analystcount, IFNULL(pc.cnt,0) as pallcount")
            ->where("NOT EXISTS (SELECT 1 FROM pred p WHERE p.event_id = e.id AND p.pred_type = 2 AND p.analyst_id = " . $analystId . ") AND e.bigscore <> '0' AND e.starttime > " . time() . " AND (pc.cnt IS NULL OR pc.cnt < (ec.analyst*0.1))")
            ->select();
    }
}
