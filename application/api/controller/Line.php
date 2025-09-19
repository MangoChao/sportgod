<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\controller\LineBot;
use think\Log;
use fast\Http;
use think\Config;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

class Line extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    protected $LineBot = null;

    protected $webhook_events_type = null;

    protected $webhook_replyToken = null;
    protected $webhook_postback_data = null;

    //source
    protected $webhook_userId = null;
    protected $webhook_type = null;
    protected $webhook_groupId = null;

    //message
    protected $webhook_events_message_id = null;
    protected $webhook_events_message_type = null;
    protected $webhook_events_message_text = "";

    public function _initialize()
    {
        parent::_initialize();
        Log::init(['type' => 'File', 'log_name' => 'line_bot']);

        $channel_access_token = Config::get("site.line_channel_access_token");
        $this->LineBot = new LineBot($channel_access_token);
    }

    public function index()
    {
        $this->success('請求成功');
    }

    public function webhook()
    {
        $this->request->filter([]);
        $post = $this->request->post();
        Log::info('------------------webhook------------------');
        Log::info($post);
        Log::info('-------------------------------------------');

        $events = $post['events'] ?? null;
        if (is_array($events) and sizeof($events) > 0) {
            foreach ($events as $e) {
                $this->webhook_events_type = $e['type'] ?? null;
                $source = $e['source'] ?? null;
                $this->webhook_replyToken = $e['replyToken'] ?? null;
                $postback = $e['postback'] ?? null;
                if ($postback)
                    $this->webhook_postback_data = $postback['data'] ?? null;
                if ($source) {
                    $this->webhook_userId = $source['userId'] ?? null;
                    $this->webhook_type = $source['type'] ?? null;
                    $this->webhook_groupId = $source['groupId'] ?? null;
                    if ($this->webhook_userId) $this->checkUser($this->webhook_userId);

                    if ($this->webhook_events_type) {
                        switch ($this->webhook_events_type) {
                            case 'message':
                                $this->webhook_events_message_id = $e['message']['id'] ?? null;
                                $this->webhook_events_message_type = $e['message']['type'] ?? null;
                                $this->webhook_events_message_text = $e['message']['text'] ?? "";
                                $this->webhook_message_event();
                                break;
                            case 'follow':
                                // $mUser = model('Userfree')->get(['line_user_id' => $this->webhook_userId]);
                                // if(!$mUser){
                                //     $params = [
                                //         'line_user_id' => $this->webhook_userId,
                                //     ];
                                //     model('Userfree')::create($params);
                                // }
                                break;
                            case 'unfollow':
                                break;
                            case 'postback':
                                $this->webhook_postback_event();
                                break;
                        }
                    }
                }
            }
        }
    }

    private function webhook_message_event()
    {
        $message = $this->webhook_events_message_text;
        $message_lower = trim(strtolower($message));

        // Log::notice("收到指令:" . $message . "");
        // Log::notice("編譯指令:" . $message_lower . "");
        $isSys = true;
        if ($isSys) {
            switch ($message_lower) {
                default:
                    // $this->sendReplyMessage($message_lower);
                    break;
                case "1":
                    $messages_obj = $this->buildMainMenuFlex();
                    $this->sendReplyMessageCus($messages_obj);
                    break;
                    // case "賽事":
                    //     $table_data_list = $this->eventlist();
                    //     $flexMessages = $this->tableDataListToFlexMessages($table_data_list, 5); // 每 bubble 8 場
                    //     $messages_obj = array_slice($flexMessages, 0, 5);
                    //     $this->sendReplyMessageCus($messages_obj);
                    break;
                case "#uid":
                    break;
            }
        } else {
        }
    }

    public function webhook_postback_event()
    {
        $data = $this->webhook_postback_data ?? '';
        $p = json_decode($data, true);
        if (!is_array($p)) {
            $this->sendReplyMessage("參數錯誤，請重試。");
            return;
        }

        $cmd = $p['cmd'] ?? null;
        $userId = $this->webhook_userId;

        switch ($cmd) {
            case 'menu':
                $action = $p['action'] ?? '';
                switch ($action) {
                    case 'mine':
                        $this->sendTodayEventsList();
                        break;

                    case 'results':
                        $this->sendMyPredResultsPage();
                        break;

                    case 'heatmap':
                        $this->sendReplyMessage("活動圖功能開發中");
                        break;

                    default:
                        $this->sendReplyMessage("尚未支援的選單功能");
                        break;
                }
                break;
            case 'pick':
                $eventId = isset($p['event']) ? (int)$p['event'] : 0;
                if (!$eventId) {
                    $this->sendReplyMessage("參數錯誤，請重試。");
                    return;
                }
                $this->setPredState($userId, $eventId, ['winner' => '', 'total' => '']);
                $msg = $this->buildPreviewText($eventId, '', '');
                $this->replyWithQuickReply($msg, $eventId);
                break;

            case 'toggle':
                $eventId = isset($p['event']) ? (int)$p['event'] : 0;
                if (!$eventId) {
                    $this->sendReplyMessage("參數錯誤，請重試。");
                    return;
                }
                $type  = $p['type']  ?? '';
                $value = $p['value'] ?? '';

                if (!in_array($type, ['winner', 'total'], true)) {
                    $this->sendReplyMessage("操作類型錯誤。");
                    return;
                }
                if ($type === 'winner' && !in_array($value, ['home', 'away'], true)) $value = '';
                if ($type === 'total'  && !in_array($value, ['over', 'under'], true)) $value = '';

                $state = $this->getPredState($userId, $eventId);
                // 再點同一個 => 取消
                if ($state[$type] === $value) $value = '';
                $state[$type] = $value;
                $this->setPredState($userId, $eventId, $state);

                $msg = $this->buildPreviewText($eventId, $state['winner'], $state['total']);
                $this->replyWithQuickReply($msg, $eventId);
                break;

            case 'clear':
                $eventId = isset($p['event']) ? (int)$p['event'] : 0;
                if (!$eventId) {
                    $this->sendReplyMessage("參數錯誤，請重試。");
                    return;
                }
                $this->setPredState($userId, $eventId, ['winner' => '', 'total' => '']);
                $msg = $this->buildPreviewText($eventId, '', '');
                $this->replyWithQuickReply($msg, $eventId);
                break;

            case 'submit':
                $eventId = isset($p['event']) ? (int)$p['event'] : 0;
                if (!$eventId) {
                    $this->sendReplyMessage("參數錯誤，請重試。");
                    return;
                }
                $state = $this->getPredState($userId, $eventId);
                $winner = $state['winner'] ?? '';
                $total  = $state['total']  ?? '';

                if ($winner === '' && $total === '') {
                    $this->replyWithQuickReply("尚未選擇任何項目，可選主勝/客勝或大分/小分。", $eventId);
                    return;
                }

                // 轉成 pred() 需要的兩個陣列
                [$paramsRefund, $paramsBigs] = $this->buildPredParamsFromState($eventId, $state);

                $res = $this->createPred($userId, $paramsRefund, $paramsBigs);
                if ($res) {
                    $replyMessage = "✅ 已送出你的預測！(event:{$eventId})";
                } else {
                    $replyMessage = "預測失敗, 請聯絡客服";
                }
                $this->clearPredState($userId, $eventId);
                $this->sendReplyMessage($replyMessage);
                break;

            default:
                $this->sendReplyMessage("尚未支援的操作。");
                break;
        }
    }

    private function sendTodayEventsList(int $cid = 0): void
    {
        // 1) 賽事清單（你原本已有）
        $table_data_list = $this->eventlist($cid);
        $flexMessages = $this->tableDataListToFlexMessages($table_data_list, 8); // 可能多則

        // 2) 我的今日預測（今天、未結算，用 carousel 一則）
        $analystId = $this->getAnalystIdByLineUserId($this->webhook_userId);
        $myPredMsgOne = []; // 最終只放一則（carousel或空）
        if ($analystId) {
            $todayStart    = strtotime(date('Y-m-d 00:00:00'));
            $tomorrowStart = strtotime(date('Y-m-d 00:00:00', strtotime('+1 day')));

            $todayAll = $this->fetchMyPredsCombinedInRange($analystId, $todayStart, $tomorrowStart);

            // 未結算（comply=0）
            $todayPending = array_values(array_filter($todayAll, function ($it) {
                return (int)(isset($it['comply']) ? $it['comply'] : 0) === 0;
            }));

            // 用共用清單：showResult=false、useCarousel=true（回傳一則或一則空）
            $tmp = $this->buildPredListBubbles($todayPending, "📝 我的今日預測", 8, false, true);
            if (!empty($tmp)) {
                // buildPredListBubbles 在空資料時也會回一則提示 bubble，所以直接取第一則即可
                $myPredMsgOne[] = $tmp[0];
            }
        }

        // 3) 合併：我的預測先、再補賽事；總數 ≤ 5
        $messages = [];
        foreach ($myPredMsgOne as $m) $messages[] = $m;

        $remain = 5 - count($messages);
        if ($remain > 0 && !empty($flexMessages)) {
            foreach (array_slice($flexMessages, 0, $remain) as $m) $messages[] = $m;
        }

        if (empty($messages)) {
            $messages = [["type" => "text", "text" => "今天暫無賽事與預測。"]];
        }

        $this->sendReplyMessageCus($messages);
    }

    private function fetchTodayMyPredsCombined(int $analystId): array
    {
        $start = strtotime(date('Y-m-d 00:00:00'));
        $end   = strtotime(date('Y-m-d 23:59:59'));
        $rows = model('Pred')->alias('p')
            ->join('event e', 'e.id = p.event_id')
            ->where('p.analyst_id = ' . $analystId)
            ->where('e.starttime', '>=', $start)
            ->where('e.starttime', '<=', $end)
            ->order('e.starttime asc')
            ->select();

        if (!$rows) return [];

        $byEvent = [];
        foreach ($rows as $r) {
            $eid = (int)$r->event_id;
            if (!isset($byEvent[$eid])) {
                $byEvent[$eid] = [
                    'event_id'  => $eid,
                    'starttime' => (int)$r->starttime,
                    'guests'    => (string)$r->guests,
                    'master'    => (string)$r->master,
                    'winteam'   => null,
                    'bigsmall'  => null,
                    'comply'    => 0, // 預設未結算
                ];
            }
            if ((int)$r->pred_type === 1) {
                $byEvent[$eid]['winteam'] = $r->winteam;
            } elseif ((int)$r->pred_type === 2) {
                $byEvent[$eid]['bigsmall'] = $r->bigsmall;
            }
            // ⚡ 新增：記錄 comply 狀態（若同場有多筆，以非 0 優先）
            if ((int)$r->comply > 0) {
                $byEvent[$eid]['comply'] = (int)$r->comply;
            }
        }

        return array_values($byEvent);
    }

    private function getAnalystIdByLineUserId(string $lineUserId): ?int
    {
        $uf = model('UserFree')->where('line_user_id', $lineUserId)->find();
        if (!$uf) return null;

        $analyst = model('Analyst')->where('user_free = ' . $uf->id)->find();
        if ($analyst) return (int)$analyst->id;

        // 若不存在就幫他建一個（與你 pred() 的行為一致）
        $analyst = model('Analyst')::create([
            'user_free'    => $uf->id,
            'analyst_name' => "Line用戶[{$uf->id}]",
            'avatar'       => '',
            'status'       => 1,
            'admin_id'     => 0,
            'autopred'     => 0,
            'free'         => 1,
        ]);
        return (int)$analyst->id;
    }

    private function buildMainMenuFlex(): array
    {
        return [[
            "type" => "flex",
            "altText" => "主選單",
            "contents" => [
                "type" => "bubble",
                "body" => [
                    "type" => "box",
                    "layout" => "vertical",
                    "spacing" => "md",
                    "contents" => [
                        ["type" => "text", "text" => "選單", "weight" => "bold", "size" => "lg"],
                        ["type" => "separator", "margin" => "sm"],
                        // 勝率排行榜
                        [
                            "type" => "button",
                            "style" => "primary",
                            "action" => [
                                "type" => "postback",
                                "label" => "勝率排行榜",
                                "data"  => json_encode(["cmd" => "menu", "action" => "winrate"], JSON_UNESCAPED_UNICODE),
                                "displayText" => "勝率排行榜"
                            ]
                        ],
                        // 獲利排行榜
                        [
                            "type" => "button",
                            "style" => "primary",
                            "action" => [
                                "type" => "postback",
                                "label" => "獲利排行榜",
                                "data"  => json_encode(["cmd" => "menu", "action" => "profit"], JSON_UNESCAPED_UNICODE),
                                "displayText" => "獲利排行榜"
                            ]
                        ],
                        // 我的預測
                        [
                            "type" => "button",
                            "style" => "secondary",
                            "action" => [
                                "type" => "postback",
                                "label" => "我的預測",
                                "data"  => json_encode(["cmd" => "menu", "action" => "mine"], JSON_UNESCAPED_UNICODE),
                                "displayText" => "我的預測"
                            ]
                        ],
                        // 預測結果
                        [
                            "type" => "button",
                            "style" => "secondary",
                            "action" => [
                                "type" => "postback",
                                "label" => "預測結果",
                                "data"  => json_encode(["cmd" => "menu", "action" => "results"], JSON_UNESCAPED_UNICODE),
                                "displayText" => "預測結果"
                            ]
                        ],
                        // 活動圖
                        [
                            "type" => "button",
                            "style" => "secondary",
                            "action" => [
                                "type" => "postback",
                                "label" => "活動圖",
                                "data"  => json_encode(["cmd" => "menu", "action" => "heatmap"], JSON_UNESCAPED_UNICODE),
                                "displayText" => "活動圖"
                            ]
                        ],
                    ]
                ]
            ]
        ]];
    }

    private function buildPreviewText(int $eventId, string $winner, string $total): string
    {
        $ev = model('Event')->find($eventId);
        if (!$ev) {
            return "賽事 {$eventId}\n（查無此賽事）";
        }
        $title = "{$ev->guests} vs {$ev->master}(主)\n時間：" . date('Y-m-d H:i', (int)$ev->starttime);
        $w = $winner === '' ? '未選' : ($winner === 'home' ? '主勝' : '客勝');
        $t = $total  === '' ? '未選' : ($total  === 'over' ? '大分' : '小分');
        return "🎯 選擇預測\n{$title}\n讓分：{$w}　大小：{$t}\n（可繼續點下方按鈕切換，完成後按「送出」）";
    }

    private function replyWithQuickReply(string $text, int $eventId): void
    {
        // 你已經有 sendReplyMessageCus()，可以直接用它送含 quickReply 的訊息
        $messages_obj = [[
            "type" => "text",
            "text" => $text,
            "quickReply" => $this->quickReplyForPrediction($eventId)
        ]];
        $this->sendReplyMessageCus($messages_obj);
    }

    public function checkUser($line_user_id)
    {
        $mUser = model('User')->get(['line_user_id' => $line_user_id, 'status' => 1]);
        if ($mUser) {
        } else {
            $mUser = model('Userfree')->get(['line_user_id' => $line_user_id]);
            if (!$mUser) {
                $params = [
                    'line_user_id' => $line_user_id,
                ];
                model('Userfree')::create($params);
                return 0;
            } else {
                return 1;
            }
        }
    }

    private function sendReplyMessage($reText)
    {
        $messages_obj = [
            [
                'type' => 'text',
                'text' => $reText,
            ]
        ];
        $this->sendReplyMessageCus($messages_obj);
    }

    private function sendReplyButton($action)
    {
        $messages_obj = [
            [
                'type' => 'action',
                'action' => $action,
            ]
        ];
        $response_sendReplyMessage = $this->LineBot->sendReplyMessage($this->webhook_replyToken, $messages_obj);
        Log::notice('response_sendReplyMessage:');
        Log::notice($response_sendReplyMessage);
        Log::notice('-------------------------------------------');
    }


    private function sendReplyMessageCus($messages_obj)
    {
        $response_sendReplyMessage = $this->LineBot->sendReplyMessage($this->webhook_replyToken, $messages_obj);
        Log::notice('response_sendReplyMessage:');
        Log::notice($response_sendReplyMessage);
        Log::notice('-------------------------------------------');
    }

    public function eventlist($cid = 0)
    {
        $table_data_list = [];
        $startdate = date('Y-m-d');
        $starttime = time();
        $starttime_next = strtotime($startdate . " +1 day");
        $day = 1;
        do {
            $starttime_fiter = "starttime > " . $starttime . " AND starttime < " . $starttime_next;
            if ($cid != 0) {
                $mEvent = model('Event')->where("event_category_id = " . $cid . " AND " . $starttime_fiter)->select();
            } else {
                $mEvent = model('Event')->where(" " . $starttime_fiter)->select();
            }
            if ($mEvent) {
                foreach ($mEvent as $v) {
                    $guests_refund_box = '';
                    $master_refund_box = '';
                    if ($v->guests_refund != '') {
                        if ($v->guests_refund == '0') {
                            $guests_refund_box = '<span class="refund_box">盤口未開</span>';
                        } else {
                            $guests_refund_box = '<span class="refund_box">' . $v->guests_refund . '</span>';
                        }
                    } else {
                        if ($v->master_refund == '0') {
                            $master_refund_box = '<span class="refund_box">盤口未開</span>';
                        } else {
                            $master_refund_box = '<span class="refund_box">' . $v->master_refund . '</span>';
                        }
                    }
                    $v->team_str = '<span class="text-black">' . $v->guests . '</span>&nbsp;' . $guests_refund_box . '<br><span class="text-info">' . $v->master . '</span><span class="text-danger">(主)</span>&nbsp;' . $master_refund_box;
                    $v->refund_str = '<span class="text-info">' . $v->guests_refund . '&nbsp;</span><br><span class="text-info">' . $v->master_refund . '&nbsp;</span>';
                    $v->bigscore_str = '<span class="text-info">' . $v->bigscore . '&nbsp;</span><br><span class="text-info">&nbsp;</span>';
                }
                $table_data_list[$startdate] = $mEvent;
            }
            $starttime = $starttime_next;
            $startdate = date("Y-m-d", $starttime);
            $starttime_next = strtotime($startdate . " +1 day");
            $day++;
        } while ($day <= 1);

        return $table_data_list;
    }

    /**
     * 將 eventlist() 產生的 $table_data_list 轉成 LINE 純文字訊息陣列
     * - 會自動分段避免超過 LINE 單則 5000 字限制（保守抓 4800）
     * - 格式：每一天一個大標，底下多場用項目符號，含(主)隊、盤口/大小
     */
    function formatEventListForLine(array $table_data_list, int $maxChars = 4800): array
    {
        // 將 refund 值正規化：'0' => '未開'，''/null => '-'，其他直接顯示
        $fmtRefund = function ($val) {
            if ($val === '0') return '未開';
            if ($val === '' || $val === null) return '-';
            return (string)$val;
        };

        $messages = [];
        $buf = '';

        if (empty($table_data_list)) {
            return ['目前無預測'];
        }

        foreach ($table_data_list as $date => $events) {
            // 日期抬頭
            $sectionHeader = "📅 {$date}\n";
            if (mb_strlen($buf . $sectionHeader, 'UTF-8') > $maxChars) {
                $messages[] = rtrim($buf);
                $buf = '';
            }
            $buf .= $sectionHeader;

            foreach ($events as $ev) {
                // 嘗試帶上時間（若資料表有 starttime）
                $timePart = '';
                if (isset($ev->starttime) && $ev->starttime) {
                    $timePart = date('H:i', (int)$ev->starttime) . ' ';
                }

                // 隊名與主客
                $guestName  = (string)($ev->guests ?? '');
                $masterName = (string)($ev->master ?? '');
                $titleLine  = "• {$timePart}{$guestName} vs {$masterName}(主)\n";

                // 盤口（讓分/賠率等）— 兩邊都顯示，沒有就以「-」或「未開」
                $guestRefund = $fmtRefund($ev->guests_refund ?? null);
                $masterRefund = $fmtRefund($ev->master_refund ?? null);
                $refundLine  = "  盤口：客 {$guestRefund} ／ 主 {$masterRefund}\n";

                // 大小分（若沒有就用「-」）
                $bigscore = ($ev->bigscore ?? '') === '' ? '-' : (string)$ev->bigscore;
                $bigLine  = "  大小：{$bigscore}\n";

                $one = $titleLine . $refundLine . $bigLine;

                // 加上空行區隔
                $one .= "\n";

                if (mb_strlen($buf . $one, 'UTF-8') > $maxChars) {
                    // 先送出前一段，再把這場塞到新的段落
                    $messages[] = rtrim($buf);
                    $buf = $sectionHeader . $one; // 保留抬頭，讓分段後可讀
                } else {
                    $buf .= $one;
                }
            }
        }

        if (trim($buf) !== '') {
            $messages[] = rtrim($buf);
        }

        return $messages;
    }

    /**
     * 建一列「可點擊的賽事 row」：顯示時間、對戰與盤口/大小；整列可點
     */
    function buildEventRowBox($ev): array
    {
        $time = (isset($ev->starttime) && $ev->starttime) ? date('H:i', (int)$ev->starttime) : '--:--';
        $guest = (string)($ev->guests ?? '');
        $master = (string)($ev->master ?? '');
        $guestRefund = ($ev->guests_refund ?? '') === '' ? '-' : (string)$ev->guests_refund;
        $masterRefund = ($ev->master_refund ?? '') === '' ? '-' : (string)$ev->master_refund;
        $bigscore = ($ev->bigscore ?? '') === '' ? '-' : (string)$ev->bigscore;

        $title = "{$time}  {$guest} vs {$master}(主)";
        $sub   = "盤口：客 {$guestRefund} ／ 主 {$masterRefund}　大小：{$bigscore}";

        return [
            "type" => "box",
            "layout" => "vertical",
            "spacing" => "xs",
            "margin" => "xs",
            "action" => [
                "type" => "postback",
                "label" => "開始預測",
                // JSON 格式，避免 &amp; 問題
                "data" => json_encode([
                    "cmd"   => "pick",
                    "event" => (int)$ev->id
                ], JSON_UNESCAPED_UNICODE),
                "displayText" => $title
            ],
            "contents" => [
                [
                    "type" => "text",
                    "text" => $title,
                    "wrap" => true,
                    "weight" => "bold",
                    "size" => "sm"
                ],
                [
                    "type" => "text",
                    "text" => $sub,
                    "wrap" => true,
                    "size" => "xs",
                    "color" => "#666666"
                ],
                ["type" => "separator", "margin" => "xs"]
            ]
        ];
    }

    /**
     * 一天一個 bubble；若當天場次太多，分頁（bubble 標題會顯示 Page 2/3…）
     * @param string $date  e.g., '2025-09-10'
     * @param array  $events 該日的 Event 物件陣列
     * @param int    $rowsPerBubble 每個 bubble 容納幾列（建議 8~10）
     */
    function buildDayBubbles(string $date, array $events, int $rowsPerBubble = 8): array
    {
        $bubbles = [];
        $chunks = array_chunk($events, $rowsPerBubble);
        $totalPages = count($chunks);

        foreach ($chunks as $idx => $chunk) {
            $page = $idx + 1;
            $title = "📅 {$date}" . ($totalPages > 1 ? "（Page {$page}/{$totalPages}）" : "");
            $rows = [];
            foreach ($chunk as $ev) {
                $rows[] = $this->buildEventRowBox($ev);
            }
            // 去掉最後一個 row 的分隔線，比較漂亮
            if (!empty($rows)) {
                $last = &$rows[count($rows) - 1]["contents"];
                if (!empty($last) && end($last)["type"] === "separator") array_pop($last);
            }

            $bubbles[] = [
                "type" => "bubble",
                "size" => "mega",
                "header" => [
                    "type" => "box",
                    "layout" => "vertical",
                    "contents" => [[
                        "type" => "text",
                        "text" => $title,
                        "weight" => "bold",
                        "size" => "md"
                    ]]
                ],
                "body" => [
                    "type" => "box",
                    "layout" => "vertical",
                    "spacing" => "sm",
                    "contents" => $rows
                ]
            ];
        }
        return $bubbles;
    }

    /**
     * 將 $table_data_list（date => [events...]）轉成「多個 Flex 訊息」
     * - 每個 Flex 訊息是一個 carousel（最多 10 個 bubbles）
     * - 回傳為 LINE Messaging API 的 message 陣列（可直接拿去 reply/push）
     */
    function tableDataListToFlexMessages(array $table_data_list, int $rowsPerBubble = 8): array
    {
        // 先把所有日的 bubbles 串起來
        $allBubbles = [];
        foreach ($table_data_list as $date => $events) {
            if (!is_array($events) || empty($events)) continue;
            $dayBubbles = $this->buildDayBubbles($date, $events, $rowsPerBubble);
            $allBubbles = array_merge($allBubbles, $dayBubbles);
        }
        if (empty($allBubbles)) {
            // 沒資料時，回一則簡單文字
            return [[
                "type" => "text",
                "text" => "目前沒有賽事資訊。"
            ]];
        }

        // 10 個 bubble 為一個 carousel（LINE 限制）
        $messages = [];
        foreach (array_chunk($allBubbles, 10) as $bubblesPage) {
            $messages[] = [
                "type" => "flex",
                "altText" => "賽事清單",
                "contents" => [
                    "type" => "carousel",
                    "contents" => $bubblesPage
                ]
            ];
        }

        return $messages;
    }

    private function quickReplyForPrediction(int $eventId): array
    {
        $mk = function ($label, array $payload) {
            return [
                "type" => "action",
                "action" => [
                    "type" => "postback",
                    "label" => $label,
                    "data"  => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    "displayText" => $label
                ]
            ];
        };

        return [
            "items" => [
                $mk("主勝",  ["cmd" => "toggle", "event" => $eventId, "type" => "winner", "value" => "home"]),
                $mk("客勝",  ["cmd" => "toggle", "event" => $eventId, "type" => "winner", "value" => "away"]),
                $mk("大分",  ["cmd" => "toggle", "event" => $eventId, "type" => "total",  "value" => "over"]),
                $mk("小分",  ["cmd" => "toggle", "event" => $eventId, "type" => "total",  "value" => "under"]),
                $mk("清除",  ["cmd" => "clear",  "event" => $eventId]),
                $mk("送出",  ["cmd" => "submit", "event" => $eventId])
            ]
        ];
    }


    private function predKey(string $userId, int $eventId): string
    {
        return "pred:session:{$userId}:{$eventId}";
    }

    private function getPredState(string $userId, int $eventId): array
    {
        $redis = getRedis();
        $h = $redis->hGetAll($this->predKey($userId, $eventId)) ?: [];
        return [
            'winner' => $h['winner'] ?? '',
            'total'  => $h['total']  ?? '',
        ];
    }

    private function setPredState(string $userId, int $eventId, array $state): void
    {
        $redis = getRedis();
        $key = $this->predKey($userId, $eventId);
        $redis->hMset($key, [
            'winner' => $state['winner'] ?? '',
            'total'  => $state['total']  ?? '',
            'ts'     => time(),
        ]);
        $redis->expire($key, 1800); // 30 分鐘
    }

    private function clearPredState(string $userId, int $eventId): void
    {
        $redis = getRedis();
        $redis->del($this->predKey($userId, $eventId));
    }

    // 將 getPredState() 的 winner/total 轉成 DB 需要的 1/0
    private function buildPredParamsFromState(int $eventId, array $state): array
    {
        $paramsRefund = [];
        $paramsBigs   = [];

        // 讓分 winner: 'home' | 'away' | ''
        if (!empty($state['winner'])) {
            // home = 1（主場）、away = 0（客場）
            $paramsRefund[$eventId] = ($state['winner'] === 'home') ? 1 : 0;
        }

        // 大小 total: 'over' | 'under' | ''
        if (!empty($state['total'])) {
            // over = 1（大）、under = 0（小）
            $paramsBigs[$eventId] = ($state['total'] === 'over') ? 1 : 0;
        }

        return [$paramsRefund, $paramsBigs];
    }

    private function createPred($userId, $paramsRefund, $paramsBigs)
    {
        try {
            $m = model('UserFree')->where('line_user_id', $userId)->find();
            if (!$m) {
                Log::notice("UserFree 查無");
                return false;
            }
            $this->pred($m->id, $paramsRefund, $paramsBigs, true);
        } catch (ValidateException $e) {
            Log::notice("ValidateException :" . $e->getMessage());
            return false;
        } catch (PDOException $e) {
            Log::notice("PDOException :" . $e->getMessage());
            return false;
        } catch (Exception $e) {
            Log::notice("Exception :" . $e->getMessage());
            return false;
        }
        return true;
    }

    private function buildPredRow(array $it, bool $showResult = false): array
    {
        $time  = !empty($it['starttime']) ? date('H:i', (int)$it['starttime']) : '--:--';
        $title = "{$time} {$it['guests']} vs {$it['master']}(主)";

        $winnerText = ($it['winteam'] === null || $it['winteam'] === '') ? '未選'
            : ((int)$it['winteam'] === 1 ? '主勝' : '客勝');
        $totalText  = ($it['bigsmall'] === null || $it['bigsmall'] === '') ? '未選'
            : ((int)$it['bigsmall'] === 1 ? '大分' : '小分');
        $sub = "讓分：{$winnerText}　大小：{$totalText}";

        $contents = [
            ["type" => "text", "text" => $title, "size" => "sm", "weight" => "bold", "wrap" => true],
            ["type" => "text", "text" => $sub,   "size" => "xs", "color" => "#666666", "wrap" => true]
        ];

        if ($showResult) {
            $resultMap = [0 => '⏳ 未確認', 1 => '✅ 命中', 2 => '❌ 未中'];
            $contents[] = [
                "type" => "text",
                "text" => "結果：" . ($resultMap[$it['comply']] ?? '⏳ 未確認'),
                "size" => "xs",
                "color" => "#333333"
            ];
        }

        return [
            "type" => "box",
            "layout" => "vertical",
            "spacing" => "xs",
            "margin"  => "xs",
            "action" => [
                "type" => "postback",
                "label" => "查看",
                "data"  => json_encode(["cmd" => "pick", "event" => (int)$it['event_id']], JSON_UNESCAPED_UNICODE),
                "displayText" => "查看預測"
            ],
            "contents" => $contents
        ];
    }

    private function buildPredListBubbles(array $preds, string $title, int $rowsPerPage = 8, bool $showResult = false, bool $useCarousel = true): array
    {
        if (empty($preds)) {
            return [[
                "type" => "flex",
                "altText" => $title,
                "contents" => [
                    "type" => "bubble",
                    "header" => [
                        "type" => "box",
                        "layout" => "vertical",
                        "contents" => [[
                            "type" => "text",
                            "text" => $title,
                            "weight" => "bold",
                            "size" => "md"
                        ]]
                    ],
                    "body" => [
                        "type" => "box",
                        "layout" => "vertical",
                        "contents" => [[
                            "type" => "text",
                            "text" => "沒有資料。",
                            "size" => "sm",
                            "color" => "#666666",
                            "wrap" => true
                        ]]
                    ]
                ]
            ]];
        }

        $chunks = array_chunk($preds, $rowsPerPage);
        $bubbles = [];

        foreach ($chunks as $pageIdx => $chunk) {
            $rows = [];
            foreach ($chunk as $it) {
                $rows[] = $this->buildPredRow($it, $showResult);
                $rows[] = ["type" => "separator", "margin" => "xs"];
            }
            if (!empty($rows)) array_pop($rows);

            $bubbleTitle = $title;
            if (count($chunks) > 1) {
                $bubbleTitle .= "（" . ($pageIdx + 1) . "/" . count($chunks) . "）";
            }

            $bubbles[] = [
                "type" => "bubble",
                "size" => "mega",
                "header" => [
                    "type" => "box",
                    "layout" => "vertical",
                    "contents" => [[
                        "type" => "text",
                        "text" => $bubbleTitle,
                        "weight" => "bold",
                        "size" => "md"
                    ]]
                ],
                "body" => [
                    "type" => "box",
                    "layout" => "vertical",
                    "spacing" => "sm",
                    "contents" => $rows
                ]
            ];
        }

        if ($useCarousel) {
            return [[
                "type" => "flex",
                "altText" => $title,
                "contents" => [
                    "type" => "carousel",
                    "contents" => $bubbles
                ]
            ]];
        } else {
            return array_map(function ($bubble) use ($title) {
                return [
                    "type" => "flex",
                    "altText" => $title,
                    "contents" => $bubble
                ];
            }, $bubbles);
        }
    }

    private function sendMyPredResultsPage(): void
    {
        $analystId = $this->getAnalystIdByLineUserId($this->webhook_userId);
        if (!$analystId) {
            $this->sendReplyMessageCus([["type" => "text", "text" => "尚未綁定帳號。"]]);
            return;
        }

        // 近 7 天（含今天）
        $sevenDaysAgo  = strtotime(date('Y-m-d 00:00:00', strtotime('-6 days')));
        $tomorrowStart = strtotime(date('Y-m-d 00:00:00', strtotime('+1 day')));

        $all = $this->fetchMyPredsCombinedInRange($analystId, $sevenDaysAgo, $tomorrowStart);

        $pending = array_values(array_filter($all, function ($it) {
            return (int)(isset($it['comply']) ? $it['comply'] : 0) === 0;
        }));

        $settled = array_values(array_filter($all, function ($it) {
            return (int)(isset($it['comply']) ? $it['comply'] : 0) > 0;
        }));

        $messages = [];

        // 1) 未結算（近7天）— 不顯示結果；多則 bubble 分頁
        $messages = array_merge(
            $messages,
            $this->buildPredListBubbles($pending, "📝 未結算預測（近7天）", 8, false, false)
        );

        // 2) 已結算（近7天）— 顯示結果；多則 bubble 分頁
        $messages = array_merge(
            $messages,
            $this->buildPredListBubbles($settled, "📊 已結算預測（近7天）", 8, true, false)
        );

        // 3) 勝率（先留空）
        $messages[] = [
            "type" => "flex",
            "altText" => "勝率",
            "contents" => [
                "type" => "bubble",
                "header" => [
                    "type" => "box",
                    "layout" => "vertical",
                    "contents" => [[
                        "type" => "text",
                        "text" => "📈 勝率（近7天）",
                        "weight" => "bold",
                        "size" => "md"
                    ]]
                ],
                "body" => [
                    "type" => "box",
                    "layout" => "vertical",
                    "contents" => [[
                        "type" => "text",
                        "text" => "敬請期待。",
                        "size" => "sm",
                        "color" => "#666666"
                    ]]
                ]
            ]
        ];

        // 一次最多 5 則
        $this->sendReplyMessageCus(array_slice($messages, 0, 5));
    }

    /**
     * 依時間窗抓取我的預測，合併同一場（讓分/大小），並帶出 comply
     * - pred_type: 1=讓分(讓分, winteam), 2=大小(bigsmall)
     * - comply: 0=未確認, 1=贏, 2=輸
     * - $startTs（含）~ $endTs（不含）
     */
    private function fetchMyPredsCombinedInRange(int $analystId, int $startTs, int $endTs): array
    {
        $rows = model('Pred')->alias('p')
            ->join('event e', 'e.id = p.event_id')
            ->where('p.analyst_id', $analystId)
            ->where('e.starttime', '>=', $startTs)
            ->where('e.starttime', '<',  $endTs)
            ->order('p.predtime desc') // 新的覆蓋舊的
            ->select();

        if (!$rows) return [];

        $byEvent = [];
        foreach ($rows as $r) {
            $eid = (int)$r->event_id;
            if (!isset($byEvent[$eid])) {
                $byEvent[$eid] = [
                    'event_id'     => $eid,
                    'starttime'    => (int)$r->starttime,
                    'guests'       => (string)$r->guests,
                    'master'       => (string)$r->master,
                    'winteam'      => null, // 1=主、0=客
                    'bigsmall'     => null, // 1=大、0=小
                    'comply'       => 0,    // 0=未確認, 1=贏, 2=輸
                    '_win_predtime' => 0,
                    '_big_predtime' => 0,
                ];
            }

            // 讓分/讓分
            if ((int)$r->pred_type === 1) {
                if ((int)$r->predtime >= $byEvent[$eid]['_win_predtime']) {
                    $byEvent[$eid]['winteam']       = $r->winteam;
                    $byEvent[$eid]['_win_predtime'] = (int)$r->predtime;
                }
            }
            // 大小
            elseif ((int)$r->pred_type === 2) {
                if ((int)$r->predtime >= $byEvent[$eid]['_big_predtime']) {
                    $byEvent[$eid]['bigsmall']      = $r->bigsmall;
                    $byEvent[$eid]['_big_predtime'] = (int)$r->predtime;
                }
            }

            // 結算狀態：若同場兩筆不同，就用最大值（2>1>0）
            $complyVal = (int)$r->comply;
            if ($complyVal > $byEvent[$eid]['comply']) {
                $byEvent[$eid]['comply'] = $complyVal;
            }
        }

        $list = array_values($byEvent);
        usort($list, function ($a, $b) {
            return $a['starttime'] <=> $b['starttime'];
        });

        return $list;
    }
}
