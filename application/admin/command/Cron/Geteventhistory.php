<?php

namespace app\admin\command\cron;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Exception;
use think\Log;
use think\Config;

use app\common\model\Event;
use app\common\model\Eventcategory;
use app\common\model\Pred;

class Geteventhistory extends Command
{
    protected $taskName = '抓取比分';
    protected $site = [];
    private $seenEventKeys = [];

    protected function configure()
    {
        $this->setName('Geteventhistory')->setDescription("抓取比分");
    }

    protected function execute(Input $input, Output $output)
    {
        Log::init(['type' => 'File', 'log_name' => 'cron_Geteventhistory']);
        $this->site = Config::get("site");
        if (date('H:i') != "00:00") {
            $this->GeteventhistoryV2();
        }
    }

    public function GeteventhistoryV2()
    {
        try {
            $func_name = 'Geteventhistory';
            Log::notice("[command][Cron][{$func_name}] 開始執行 " . date('Y-m-d H:i:s'));

            // 找出有未結算且已過開賽時間的賽事日期
            $modelEvent = new Event;
            $rows = $modelEvent
                ->field("FROM_UNIXTIME(starttime, '%Y-%m-%d') as date")
                ->where('status', 0)
                ->where('starttime', '<', time())
                ->group("FROM_UNIXTIME(starttime, '%Y-%m-%d')")
                ->order('starttime asc')
                ->select();
            $unsettledDates = array_column(collection($rows)->toArray(), 'date');

            // 加上今天（避免今天剛結束的賽事來不及進清單）
            $today = date('Y-m-d');
            if (!in_array($today, $unsettledDates)) {
                $unsettledDates[] = $today;
            }

            Log::notice("[command][Cron][{$func_name}] 待處理日期: " . implode(', ', $unsettledDates));

            $modelEventcategory = new Eventcategory;
            $cats = $modelEventcategory->where('status', 1)->select();
            if (!$cats || count($cats) === 0) {
                Log::notice("[command][Cron][{$func_name}] 無啟用類別");
                return;
            }

            $twoDaysAgo = strtotime('-2 days 00:00:00');

            foreach ($unsettledDates as $billingDate) {
                Log::notice("[command][Cron][{$func_name}] 處理日期: {$billingDate}");
                $anySucceeded = false;
                foreach ($cats as $cat) {
                    $this->seenEventKeys = [];
                    if ($this->fetchForDateAndCat($billingDate, $cat, $func_name)) {
                        $anySucceeded = true;
                    }
                }
                // 超過2天前且成功解析到回傳內容，把仍未結算的賽事標為放棄
                if ($anySucceeded && strtotime($billingDate) < $twoDaysAgo) {
                    $this->abandonUnsettledForDate($billingDate, $func_name);
                }
            }

            Log::notice("[command][Cron][{$func_name}] 完整結束 " . date('Y-m-d H:i:s'));
        } catch (ValidateException $e) {
            Log::notice("[command][Cron][Geteventhistory] ValidateException :" . $e->getMessage());
        } catch (PDOException $e) {
            Log::notice("[command][Cron][Geteventhistory] PDOException :" . $e->getMessage());
        } catch (Exception $e) {
            Log::notice("[command][Cron][Geteventhistory] Exception :" . $e->getMessage());
        }
    }

    // 回傳 true 表示成功拿到回應（不論有無賽事），false 表示 HTTP 失敗
    private function fetchForDateAndCat(string $billingDate, \think\Model $cat, string $func_name): bool
    {
        $baseParams = [
            'billing_date'        => $billingDate,
            'game_category'       => $cat->game_category,
            'game_type'           => 1,
            'hd_type'             => 'undefined',
            'bet_amount_type'     => 1,
            'change_element_name' => 'page_num',
            'page_num'            => 1,
        ];

        $first = fetchSportSiteHistoryPage($baseParams);
        if ($first === null) {
            Log::notice("[command][Cron][{$func_name}] {$cat->title} / {$billingDate} 首頁取不到資料");
            return false;
        }

        $totalPages = $this->parseTotalPages($first['ajaxdata']) ?? 1;
        $this->parseAndUpsertEventsFromAjaxBlocks($first['ajaxdata'], $cat->id);

        if ($totalPages > 1) {
            for ($p = 2; $p <= $totalPages; $p++) {
                $resp = fetchSportSiteHistoryPage(array_merge($baseParams, ['page_num' => $p]));
                if ($resp === null) continue;
                $this->parseAndUpsertEventsFromAjaxBlocks($resp['ajaxdata'], $cat->id);
            }
        } else {
            for ($p = 2; ; $p++) {
                $resp = fetchSportSiteHistoryPage(array_merge($baseParams, ['page_num' => $p]));
                if ($resp === null) break;
                $cnt = $this->parseAndUpsertEventsFromAjaxBlocks($resp['ajaxdata'], $cat->id);
                if ($cnt <= 0) break;
            }
        }

        Log::notice("[command][Cron][{$func_name}] {$cat->title} / {$billingDate} 已同步");
        return true;
    }

    private function abandonUnsettledForDate(string $billingDate, string $func_name): void
    {
        $dayStart = strtotime($billingDate . ' 00:00:00');
        $dayEnd   = strtotime($billingDate . ' 23:59:59');

        $count = (new Event)
            ->where('status', 0)
            ->where('starttime', '>=', $dayStart)
            ->where('starttime', '<=', $dayEnd)
            ->update(['status' => 2]);

        if ($count > 0) {
            Log::notice("[command][Cron][{$func_name}] {$billingDate} 有 {$count} 筆賽事查無結果，標記放棄(status=2)");
        }
    }

    private function upEvent($data)
    {
        if (isset($data['starttime']) and !empty($data['starttime'])) {
            $data['starttime'] = strtotime($data['starttime']);
        } else {
            $data['starttime'] = null;
        }
        if ($data['starttime'] !== null) {
            $modelEvent = new Event;
            $modelPred = new Pred;

            $startMin = $data['starttime'] - 7200;
            $startMax = $data['starttime'] + 7200;

            $mEvent = $modelEvent
                ->where('status', 0)
                ->where('event_category_id', $data['event_category_id'])
                ->where('master', $data['master'])
                ->where('guests', $data['guests'])
                ->where('starttime', 'between', [$startMin, $startMax])
                ->find();

            if ($mEvent) {
                $mEvent->starttime = $data['starttime'];
                $mEvent->master_score = $data['mscore'];
                $mEvent->guests_score = $data['gscore'];
                $mEvent->status = 1;
                $mEvent->save();
                $mPred = $modelPred->where('event_id', $mEvent->id)->select();
                if ($mPred) {
                    foreach ($mPred as $v) {
                        calculateComply($v, $mEvent->master_score, $mEvent->guests_score, $mEvent->event_category_id, $modelPred);
                    }
                }
            }
        }
    }

    private function parseTotalPages(array $ajaxdata): ?int
    {
        foreach ($ajaxdata as $blk) {
            if (($blk['spanid'] ?? '') === '#page_num') {
                $html = $blk['rtntext'] ?? '';
                if ($html === '') continue;
                $dom = new \DOMDocument();
                @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
                $xp = new \DOMXPath($dom);
                $opts = $xp->query('//option');
                $max = 0;
                foreach ($opts as $opt) {
                    if (!($opt instanceof \DOMElement)) continue;
                    $v = (int)trim($opt->getAttribute('value'));
                    if ($v > $max) $max = $v;
                }
                return $max > 0 ? $max : 1;
            }
        }
        return null;
    }

    private function parseAndUpsertEventsFromAjaxBlocks(array $ajaxdata, int $eventCategoryId): int
    {
        $extractScores = function (\DOMDocument $dom, \DOMElement $scoreCell): array {
            $scores = [];
            $divs = $scoreCell->getElementsByTagName('div');
            foreach ($divs as $sd) {
                if ($sd instanceof \DOMElement) {
                    $txt = trim($sd->textContent);
                    if ($txt !== '') $scores[] = $txt;
                }
            }
            if (count($scores) < 2) {
                $html = $dom->saveHTML($scoreCell);
                $tmpDom = new \DOMDocument();
                @$tmpDom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
                $xp = new \DOMXPath($tmpDom);
                $nodes = $xp->query("//span|//strong|//em|//div");
                $alt = [];
                foreach ($nodes as $n) {
                    if ($n instanceof \DOMElement) {
                        $t = trim($n->textContent);
                        if ($t !== '') $alt[] = $t;
                    }
                }
                if (count($scores) < 2 && !empty($alt)) {
                    foreach ($alt as $t) {
                        if (preg_match('/^\s*(\d+)\s*[:：]\s*(\d+)\s*$/u', $t, $m)) {
                            $scores = [$m[1], $m[2]];
                            break;
                        }
                    }
                    if (count($scores) < 2) {
                        $nums = [];
                        foreach ($alt as $t) {
                            if (preg_match_all('/\d+/', $t, $mm)) {
                                foreach ($mm[0] as $num) $nums[] = $num;
                            }
                        }
                        if (count($nums) >= 2) {
                            $scores = [$nums[0], $nums[1]];
                        }
                    }
                }
            }
            return [(string)($scores[0] ?? ''), (string)($scores[1] ?? '')];
        };

        $count = 0;

        foreach ($ajaxdata as $blk) {
            if (($blk['spanid'] ?? '') !== '#events-div') continue;
            $html = $blk['rtntext'] ?? '';
            if ($html === '' || mb_strpos($html, '目前無任何賽事') !== false) continue;

            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            $xp  = new \DOMXPath($dom);

            $rows = $xp->query("//tr[contains(concat(' ', normalize-space(@class), ' '), ' event-tr ')]");

            foreach ($rows as $tr) {
                if (!($tr instanceof \DOMElement)) continue;

                $tds = $tr->getElementsByTagName('td');
                if ($tds->length < 4) continue;

                // 時間欄
                $startNode = $tds->item(1);
                if (!($startNode instanceof \DOMElement)) continue;
                $startText = trim(preg_replace('/\s+/u', ' ', strip_tags($dom->saveHTML($startNode))));
                $startTextForTs = preg_replace('/^(\d{2}\/\d{2})\s+/', date('Y') . '/$1 ', $startText);
                $startTs = strtotime($startTextForTs) ?: null;

                // 比分欄
                $scoreNode = $tds->item(2);
                if (!($scoreNode instanceof \DOMElement)) continue;
                [$gscore, $mscore] = $extractScores($dom, $scoreNode);

                // 隊名欄
                $teamNode = $tds->item(3);
                if (!($teamNode instanceof \DOMElement)) continue;
                $teamDivs = $teamNode->getElementsByTagName('div');

                $guests = '';
                $master = '';
                if ($teamDivs->length >= 2) {
                    $guests = trim($teamDivs->item(0)->textContent);
                    $master = trim($teamDivs->item(1)->textContent);
                }

                $guests = trim(preg_replace(['/\s*[\(（]\s*客\s*[\)）]\s*/u', '/\s+/u'], ['', ' '], $guests));
                $master = trim(preg_replace(['/\s*[\(（]\s*主(隊)?\s*[\)）]\s*/u', '/\s+/u'], ['', ' '], $master));

                if (!$startTs || $guests === '' || $master === '') continue;

                $dedupKey = md5($master . '|' . $guests . '|' . $startTs);
                if (isset($this->seenEventKeys[$dedupKey])) continue;
                $this->seenEventKeys[$dedupKey] = true;

                $this->upEvent([
                    'event_category_id' => $eventCategoryId,
                    'gscore'            => $gscore,
                    'mscore'            => $mscore,
                    'starttime'         => $startTextForTs,
                    'master'            => $master,
                    'guests'            => $guests,
                ]);
                $count++;
            }
        }

        return $count;
    }

    private function oneLine(string $s, int $maxLen = 300): string
    {
        $t = trim(preg_replace('/\s+/u', ' ', $s));
        return mb_strlen($t) > $maxLen ? mb_substr($t, 0, $maxLen) . ' …' : $t;
    }

    private function debugLog(string $msg): void
    {
        return;
        Log::notice('[history-debug] ' . $msg);
    }
}
