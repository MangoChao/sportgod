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

use app\common\model\Event;
// use app\common\model\Eventparam;
use app\common\model\Eventcategory;
use app\common\model\Pred;

class Geteventhistory extends Command
{
    protected $taskName = '抓取比分';
    protected $site = [];
    protected $gameurl = "https://agiv-2.hau888.net";
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
            // $this->Geteventhistory();
            $this->GeteventhistoryV2();
        }
    }

    public function Geteventhistory()
    {
        try {
            $func_name = 'Geteventhistory';
            Log::notice("[command][Cron][" . $func_name . "] 開始執行 " . date('Y-m-d H:i:s', time()));
            $modelEventcategory = new Eventcategory;

            $url = $this->gameurl . "/login.php";
            $post = [
                'luserid' => $this->site['luserid'],
                'lpassword' => $this->site['lpassword'],
                'paction' => 'login-processing',
                'remember' => 1
            ];

            Log::notice("[command][Cron][" . $func_name . "] 模擬登錄");
            //模擬登錄
            // $this->login_post($url, $cookie, $post);
            $cookie = loginSetCookie($url, $post, 'hau888_cookie.txt');

            $co = 0;
            $comax = $modelEventcategory->where("status = 1")->count();
            $next = false;
            do {
                //爬賽事
                $mEventcategory = $modelEventcategory->where("status = 1")->order(['lastcron' => 'ASC', 'id' => 'ASC'])->find();
                if ($mEventcategory) {
                    $mEventcategory->lastcron = time();
                    $mEventcategory->save();
                    Log::notice("[command][Cron][" . $func_name . "] 開始取得比分 - 類別:" . $mEventcategory->title);
                    $content = $this->get_content($this->gameurl . '/history_events_show_list.php?game_category=' . $mEventcategory->game_category, $cookie);
                    // $content = $this->get_content($this->gameurl.'/history_events_show_list.php?game_category=7', $cookie);
                    // Log::notice($content);
                    if (strpos($content, '目前無任何賽事') === false) {
                        $content_arr = explode(PHP_EOL, $content);
                        $trstart = false; //行開始
                        $tdstart = false; //列開始
                        $tdrow = 0;
                        $htime = false; //帳務日期
                        $stime = false; //時間
                        $gscore = false; //客場比分
                        $mscore = false; //主場比分
                        $gteam = false; //客
                        $mteam = false; //主
                        $grefund  = false; //客讓
                        $mrefund  = false; //主讓
                        $bigscore  = false; //大小
                        $eventdata = [];
                        if (sizeof($content_arr) > 0) {
                            // Log::notice($content_arr);
                            foreach ($content_arr as $content_line) {
                                $content_line = trim($content_line);

                                if (strpos($content_line, '<tr class="event-tr') !== false) { //偵測行開始
                                    $trstart = true;
                                    $eventdata = [
                                        'event_category_id' => $mEventcategory->id,
                                        'gscore' => '',
                                        'mscore' => '',
                                        'starttime' => '',
                                        'master' => '',
                                        'guests' => '',
                                    ];
                                }
                                if (strpos($content_line, '</tr>') !== false) { //偵測行結束
                                    $trstart = false;
                                    $tdrow = 0;
                                    if (sizeof($eventdata) > 0) {
                                        $this->upEvent($eventdata);
                                    }
                                    $eventdata = [];
                                }

                                if ($content_line != '' and $trstart) {
                                    if ($htime) { //帳務日期
                                        // Log::notice($content_line);
                                        if (strpos($content_line, '</td>') !== false) {
                                            $htime = false;
                                            $stime = true;
                                        }
                                        continue;
                                    }
                                    if ($stime) { //時間
                                        // Log::notice($content_line);
                                        $start_str = '<td>';
                                        $end_str = '</td>';
                                        $eventdata['starttime'] = $this->getValue($content_line, $start_str, $end_str);
                                        $stime = false;
                                        continue;
                                    }
                                    if ($gscore) { //客場比分
                                        $start_str = '<div>';
                                        $end_str = '</div>';
                                        $eventdata['gscore'] = $this->getValue($content_line, $start_str, $end_str);
                                        $gscore = false;
                                        $mscore = true; //主場比分
                                        continue;
                                    }
                                    if ($mscore) { //主場比分
                                        $start_str = '<div>';
                                        $end_str = '</div>';
                                        $eventdata['mscore'] = $this->getValue($content_line, $start_str, $end_str);
                                        $mscore = false;
                                        continue;
                                    }
                                    if ($gteam) { //客場
                                        $start_str = '">';
                                        $end_str = '</div>';
                                        $eventdata['guests'] = $this->getValue($content_line, $start_str, $end_str);
                                        $gteam = false;
                                        $mteam = true; //主場
                                        continue;
                                    }
                                    if ($mteam) { //主場
                                        $start_str = '">';
                                        $end_str = '<font class="master';
                                        $eventdata['master'] = $this->getValue($content_line, $start_str, $end_str);
                                        $mteam = false;
                                        continue;
                                    }
                                }

                                if ($trstart = true) {
                                    if ($content_line == '<tr class="event-tr close-status">') {
                                        $htime = true; //帳務日期
                                    }
                                    if ($content_line == '<td class="rank_score">') {
                                        $gscore = true; //客場比分
                                    }
                                    if ($content_line == '<td class="ranks-td">') {
                                        $gteam = true; //客場隊伍
                                    }
                                }
                            }
                        }
                        $next = false;
                        Log::notice("[command][Cron][" . $func_name . "] 已同步賽事");
                    } else {
                        $next = true;
                        Log::notice("[command][Cron][" . $func_name . "] 無賽事");
                    }
                    Log::notice("[command][Cron][" . $func_name . "] 結束取得賽事");
                } else {
                    $next = false;
                    Log::notice("[command][Cron][" . $func_name . "] DB無菜單");
                }
                $co++;
            } while ($co < $comax);
            // }while($next && $co < $comax);

            Log::notice("[command][Cron][" . $func_name . "] 完整結束 " . date('Y-m-d H:i:s', time()));
        } catch (ValidateException $e) {
            Log::notice("[command][Cron][" . $func_name . "] ValidateException :" . $e->getMessage());
        } catch (PDOException $e) {
            Log::notice("[command][Cron][" . $func_name . "] PDOException :" . $e->getMessage());
        } catch (Exception $e) {
            Log::notice("[command][Cron][" . $func_name . "] Exception :" . $e->getMessage());
            Log::notice($content);
        }
    }

    private function upEvent($data)
    {
        // Log::notice($data);
        if (isset($data['starttime']) and !empty($data['starttime'])) {
            $data['starttime'] = strtotime($data['starttime']);
        } else {
            $data['starttime'] = null;
        }
        if ($data['starttime'] !== null) {
            $modelEvent = new Event;
            $modelPred = new Pred;
            
            // 🔹 改為 ±2 小時範圍內都視為同一場
            $startMin = $data['starttime'] - 7200; // 2小時
            $startMax = $data['starttime'] + 7200; // 2小時

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
                $mPred = $modelPred
                    ->where('event_id', $mEvent->id)
                    ->select();
                if ($mPred) {
                    foreach ($mPred as $v) {
                        calculateComply($v, $mEvent->master_score, $mEvent->guests_score, $mEvent->event_category_id, $modelPred);
                    }
                }
            }
        }
    }

    private function getValue($content, $start_str, $end_str)
    {
        $content = str_replace('&nbsp;', '', str_replace('<br>', ' ', str_replace('<br/>', ' ', trim($content))));
        $start_index = strpos($content, $start_str);
        $end_index = strpos($content, $end_str);
        if ($end_index !== false and $start_index !== false) {
            $start_index = $start_index + mb_strlen($start_str);
            return substr($content, $start_index, $end_index - $start_index);
        } else {
            return '';
        }
    }

    private function login_post($url, $cookie, $post)
    {
        $curl = curl_init(); //初始化curl模塊
        curl_setopt($curl, CURLOPT_URL, $url); //登錄提交的地址
        curl_setopt($curl, CURLOPT_HEADER, 0); //是否显示头信息
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0); //是否自動顯示返回的信息
        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie); //設置Cookie信息保存在指定的文件中
        curl_setopt($curl, CURLOPT_POST, 1); //post方式提交

        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post)); //要提交的信息
        curl_exec($curl); //執行cURL
        curl_close($curl); //關閉cURL資源，並且釋放系統資源
    }

    private function get_content($url, $cookie)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0); //是否显示头信息
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie); //讀取cookie

        $rs = curl_exec($ch); //執行cURL抓取頁面內容
        curl_close($ch);
        return $rs;
    }

    // 取代原本的 Geteventhistory()，改成呼叫新 OP API + 逐頁解析
    public function GeteventhistoryV2()
    {
        try {
            $func_name = 'Geteventhistory';
            Log::notice("[command][Cron][{$func_name}] 開始執行 " . date('Y-m-d H:i:s'));
            $modelEventcategory = new Eventcategory;

            // 1) 先登入（延用你現有 login_post/cookie 機制）
            $urlLogin = $this->gameurl . "/login.php";
            $postLogin = [
                'luserid'   => $this->site['luserid'],
                'lpassword' => $this->site['lpassword'],
                'paction'   => 'login-processing',
                'remember'  => 1,
            ];
            $cookie = './cookie.txt';
            Log::notice("[command][Cron][{$func_name}] 模擬登錄");
            $this->login_post($urlLogin, $cookie, $postLogin);

            // 2) 逐一跑啟用中的類別
            $comax = $modelEventcategory->where("status = 1")->count();
            $co = 0;

            while ($co < $comax) {
                $cat = $modelEventcategory->where("status = 1")
                    ->order(['lastcron' => 'ASC', 'id' => 'ASC'])
                    ->find();
                if (!$cat) {
                    Log::notice("[command][Cron][{$func_name}] DB無菜單");
                    break;
                }

                $cat->lastcron = time();
                $cat->save();

                Log::notice("[command][Cron][{$func_name}] 類別: {$cat->title} (gc={$cat->game_category}) 逐頁抓取");

                // 3) 先打第 1 頁以取得總頁數（若頁碼選單存在）；否則以「抓不到任何 event-tr 就停」的方式遍歷
                $billingDate = date('Y-m-d');      // 你也可改成指定日
                $gameType    = 1;                  // 依需求可調（全場）
                $betAmtType  = 1;                  // 依你的頁面預設
                $page        = 1;

                // 第一次請求
                $first = $this->fetchHistoryPage($cookie, [
                    'billing_date'        => $billingDate,
                    'game_category'       => $cat->game_category,
                    'game_type'           => $gameType,
                    'hd_type'             => 'undefined',
                    'bet_amount_type'     => $betAmtType,
                    'change_element_name' => 'page_num',
                    'page_num'            => $page,
                ]);

                if ($first === null) {
                    Log::notice("[command][Cron][{$func_name}] 類別 {$cat->title} 首頁取不到資料");
                    $co++;
                    continue;
                }

                // 解析總頁數（若 response 內有頁碼下拉 #page_num）
                $totalPages = $this->parseTotalPages($first['ajaxdata']) ?? 1;

                // 解析第 1 頁
                $eventsCount = $this->parseAndUpsertEventsFromAjaxBlocks($first['ajaxdata'], $cat->id);

                // 若沒有頁碼資訊，就以「直到抓不到賽事」為停止
                if ($totalPages === 1) {
                    $page = 2;
                    while (true) {
                        $resp = $this->fetchHistoryPage($cookie, [
                            'billing_date'        => $billingDate,
                            'game_category'       => $cat->game_category,
                            'game_type'           => $gameType,
                            'hd_type'             => 'undefined',
                            'bet_amount_type'     => $betAmtType,
                            'change_element_name' => 'page_num',
                            'page_num'            => $page,
                        ]);
                        if ($resp === null) break;
                        $cnt = $this->parseAndUpsertEventsFromAjaxBlocks($resp['ajaxdata'], $cat->id);
                        if ($cnt <= 0) break; // 這頁沒有任何 event-tr，停止
                        $page++;
                    }
                } else {
                    // 有明確總頁數就照頁數依序抓完整
                    for ($p = 2; $p <= $totalPages; $p++) {
                        $resp = $this->fetchHistoryPage($cookie, [
                            'billing_date'        => $billingDate,
                            'game_category'       => $cat->game_category,
                            'game_type'           => $gameType,
                            'hd_type'             => 'undefined',
                            'bet_amount_type'     => $betAmtType,
                            'change_element_name' => 'page_num',
                            'page_num'            => $p,
                        ]);
                        if ($resp === null) continue;
                        $this->parseAndUpsertEventsFromAjaxBlocks($resp['ajaxdata'], $cat->id);
                    }
                }

                Log::notice("[command][Cron][{$func_name}] 類別 {$cat->title} 已同步賽事");
                $co++;
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

    /**
     * 依你提供的新請求格式打 OP 端點
     * 回傳 json_decode 後的陣列（含 ajaxdata），失敗回 null
     */
    private function fetchHistoryPage(string $cookie, array $fields): ?array
    {
        $url = $this->gameurl . '/op/history_events_show_op.php?pdisplay=select_change_reload';
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_COOKIEFILE     => $cookie,
            CURLOPT_HTTPHEADER     => [
                'accept: application/json, text/javascript, */*; q=0.01',
                'content-type: application/x-www-form-urlencoded; charset=UTF-8',
                'origin: ' . $this->gameurl,
                'referer: ' . $this->gameurl . '/history_events_show_list.php?game_category=' . $fields['game_category'],
                'x-requested-with: XMLHttpRequest',
                'sec-ch-ua: "Chromium";v="140", "Not=A?Brand";v="24", "Google Chrome";v="140"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Windows"',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36',
            ],
            CURLOPT_TIMEOUT        => 20,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $raw === '' || $err) {
            Log::notice("[fetchHistoryPage] cURL error: {$err}");
            return null;
        }
        $json = json_decode($raw, true);
        if (!is_array($json) || !isset($json['root']['ajaxdata'])) {
            Log::notice("[fetchHistoryPage] JSON 格式不符");
            return null;
        }
        return $json['root'];
    }

    /**
     * 從 ajaxdata 找頁碼選單（#page_num）的 <option> 算出總頁數
     * 找不到就回傳 null
     */
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
                /** @var DOMElement $opt */
                foreach ($opts as $opt) {
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
        // 若外層沒宣告，這裡保險初始化（避免未定義屬性）
        if (!isset($this->seenEventKeys) || !is_array($this->seenEventKeys)) {
            /** @var array<string,bool> */
            $this->seenEventKeys = [];
        }

        // 小工具：更強壯地從一個 <td> 內抽兩個比分（先 div，失敗再抓 span/strong/em 或直接取數字）
        $extractScores = function (\DOMDocument $dom, \DOMElement $scoreCell): array {
            $scores = [];

            // 1) 先用 div 抓
            /** @var \DOMNodeList $divs */
            $divs = $scoreCell->getElementsByTagName('div');
            foreach ($divs as $sd) {
                if ($sd instanceof \DOMElement) {
                    $txt = trim($sd->textContent);
                    if ($txt !== '') $scores[] = $txt;
                }
            }

            // 2) 若不足，抓 span/strong/em
            if (count($scores) < 2) {
                $html = $dom->saveHTML($scoreCell);
                $tmpDom = new \DOMDocument();
                @$tmpDom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
                $xp = new \DOMXPath($tmpDom);
                /** @var \DOMNodeList $nodes */
                $nodes = $xp->query("//span|//strong|//em|//div");
                $alt = [];
                foreach ($nodes as $n) {
                    if ($n instanceof \DOMElement) {
                        $t = trim($n->textContent);
                        if ($t !== '') $alt[] = $t;
                    }
                }
                // 嘗試找像 "2" "1" 或 "2:1"
                if (count($scores) < 2 && !empty($alt)) {
                    // 先找 "d:d" 形式
                    foreach ($alt as $t) {
                        if (preg_match('/^\s*(\d+)\s*[:：]\s*(\d+)\s*$/u', $t, $m)) {
                            $scores = [$m[1], $m[2]];
                            break;
                        }
                    }
                    // 再退而求其次：擷取所有數字，取前兩個
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

            // 最終標準化：只保留兩個
            $g = $scores[0] ?? '';
            $m = $scores[1] ?? '';
            return [(string)$g, (string)$m];
        };

        $count = 0;

        foreach ($ajaxdata as $blk) {
            if (($blk['spanid'] ?? '') !== '#events-div') continue;
            $html = $blk['rtntext'] ?? '';
            if ($html === '' || mb_strpos($html, '目前無任何賽事') !== false) continue;

            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            $xp  = new \DOMXPath($dom);

            /** @var \DOMNodeList $rows */
            $rows = $xp->query("//tr[contains(concat(' ', normalize-space(@class), ' '), ' event-tr ')]");
            $rowIdx = 0;

            foreach ($rows as $tr) {
                $rowIdx++;
                if (!($tr instanceof \DOMElement)) {
                    $this->debugLog("row#$rowIdx: not a DOMElement, skip");
                    continue;
                }

                /** @var \DOMNodeList $tds */
                $tds = $tr->getElementsByTagName('td');
                if ($tds->length < 4) {
                    $this->debugLog("row#$rowIdx: tds.length < 4，跳過");
                    continue;
                }

                // ===== 1) 時間欄（第2格 <td>）→ 產生 int timestamp =====
                $startNode = $tds->item(1);
                if (!($startNode instanceof \DOMElement)) {
                    $this->debugLog("row#$rowIdx: start cell not DOMElement");
                    continue;
                }
                $startCellHtml = $dom->saveHTML($startNode);
                $startText = trim(preg_replace('/\s+/u', ' ', strip_tags($startCellHtml))); // e.g. "09/20 02:30"
                $startTextForTs = preg_replace('/^(\d{2}\/\d{2})\s+/', date('Y') . '/$1 ', $startText);
                $startTs = strtotime($startTextForTs) ?: null;

                // ===== 2) 比分欄（第3格 <td>）=====
                $scoreNode = $tds->item(2);
                if (!($scoreNode instanceof \DOMElement)) {
                    $this->debugLog("row#$rowIdx: score cell not DOMElement");
                    continue;
                }
                $scoreCellHtml = trim($dom->saveHTML($scoreNode));
                [$gscore, $mscore] = $extractScores($dom, $scoreNode);

                // ===== 3) 隊名欄（第4格 <td>）=====
                $teamNode = $tds->item(3);
                if (!($teamNode instanceof \DOMElement)) {
                    $this->debugLog("row#$rowIdx: team cell not DOMElement");
                    continue;
                }
                $teamCellHtml = $dom->saveHTML($teamNode);
                /** @var \DOMNodeList $teamDivs */
                $teamDivs = $teamNode->getElementsByTagName('div');

                $guests = '';
                $master = '';

                if ($teamDivs->length >= 2) {
                    $gd = $teamDivs->item(0);
                    $md = $teamDivs->item(1);

                    if ($gd instanceof \DOMElement) {
                        // 直接取純文字
                        $guests = trim($gd->textContent);
                    }
                    if ($md instanceof \DOMElement) {
                        // 直接取純文字（可能含「(主)」或全形括號）
                        $master = trim($md->textContent);
                    }
                } else {
                    // 結構不一致時，列印所有 div 文字供你檢視
                    $tmpNames = [];
                    foreach ($teamDivs as $tdv) {
                        if ($tdv instanceof \DOMElement) {
                            $tmpNames[] = trim($tdv->textContent);
                        }
                    }
                    $this->debugLog("row#$rowIdx: teamDivs.length={$teamDivs->length} tmpNames=" . json_encode($tmpNames, JSON_UNESCAPED_UNICODE));
                }

                // ★ 清理尾綴：(主)/(客)/（主）/（客）/(主隊)/（主隊）
                $guestsRaw = $guests;
                $masterRaw = $master;

                $guests = preg_replace('/\s*[\(（]\s*客\s*[\)）]\s*/u', '', $guests);
                $master = preg_replace('/\s*[\(（]\s*主(隊)?\s*[\)）]\s*/u', '', $master);

                // 清多餘空白
                $guests = trim(preg_replace('/\s+/u', ' ', $guests));
                $master = trim(preg_replace('/\s+/u', ' ', $master));

                // 偵錯看看正規化前後
                $this->debugLog("  team normalize: guestsRaw={$guestsRaw} -> guests={$guests} | masterRaw={$masterRaw} -> master={$master}");

                // ===== 偵錯輸出 =====
                $this->debugLog("row#$rowIdx START =======================");
                $this->debugLog("  startCellHtml: " . $this->oneLine($startCellHtml));
                $this->debugLog("  startTextForTs: {$startTextForTs}  => ts(int)=" . ($startTs ?? 'null') . " (" . ($startTs ? date('Y-m-d H:i:s', $startTs) : '') . ")");

                $this->debugLog("  scoreCellHtml: " . $this->oneLine($scoreCellHtml));
                $this->debugLog("  gscore={$gscore}, mscore={$mscore}");

                $this->debugLog("  teamCellHtml: " . $this->oneLine($teamCellHtml));
                $this->debugLog("  guests={$guests} | master={$master}");
                $this->debugLog("row#$rowIdx END   =======================");

                // ===== 基本欄位檢查 =====
                if (!$startTs || $guests === '' || $master === '') {
                    $this->debugLog("row#$rowIdx: 欄位不足（startTs/guests/master），跳過");
                    continue;
                }

                // ===== 去重（主＋客＋開賽時間(int)）=====
                $dedupKey = md5($master . '|' . $guests . '|' . $startTs);
                if (isset($this->seenEventKeys[$dedupKey])) {
                    $this->debugLog("row#$rowIdx: duplicate detected, 跳過（{$master} vs {$guests} @ {$startTs}）");
                    continue;
                }
                $this->seenEventKeys[$dedupKey] = true;

                // ===== 組 payload 丟回原本入庫/對比流程 =====
                $payload = [
                    'event_category_id' => $eventCategoryId,
                    'gscore'    => (string)$gscore,
                    'mscore'    => (string)$mscore,
                    'starttime' => $startTextForTs,
                    'master'    => $master,
                    'guests'    => $guests,
                ];
                $this->upEvent($payload);
                $count++;
            }
        }

        return $count;
    }

    /** 把多行字串壓成單行，方便在 Log 內閱讀 */
    private function oneLine(string $s, int $maxLen = 300): string
    {
        $t = trim(preg_replace('/\s+/u', ' ', $s));
        if (mb_strlen($t) > $maxLen) {
            $t = mb_substr($t, 0, $maxLen) . ' …';
        }
        return $t;
    }

    /** 同時 Log 與 echo，方便你貼回來 */
    private function debugLog(string $msg): void
    {
        return;
        // 你系統既有：use think\facade\Log;
        Log::notice('[history-debug] ' . $msg);
        // if (PHP_SAPI === 'cli') {
        //     echo "[history-debug] {$msg}\n";
        // }
    }
}
