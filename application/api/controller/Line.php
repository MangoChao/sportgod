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
                case "賽事":
                    $table_data_list = $this->eventlist();
                    $flexMessages = $this->tableDataListToFlexMessages($table_data_list, 5); // 每 bubble 8 場
                    $messages_obj = array_slice($flexMessages, 0, 5);
                    $this->sendReplyMessageCus($messages_obj);
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
        $p = [];
        parse_str($data, $p); // 這裡因為我們用 cmd=...&event=...，可直接 parse

        $cmd = $p['cmd'] ?? null;
        $eventId = isset($p['event']) ? (int)$p['event'] : 0;
        $userId = $this->webhook_userId;

        if (!$userId || !$eventId) {
            $this->sendReplyMessage("參數錯誤，請重試。");
            return;
        }

        switch ($cmd) {
            case 'pick':
                $this->setPredState($userId, $eventId, ['winner' => '', 'total' => '']);
                $msg = $this->buildPreviewText($eventId, '', '');
                $this->replyWithQuickReply($msg, $eventId);
                break;

            case 'toggle':
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
                $this->setPredState($userId, $eventId, ['winner' => '', 'total' => '']);
                $msg = $this->buildPreviewText($eventId, '', '');
                $this->replyWithQuickReply($msg, $eventId);
                break;

            case 'submit':
                $state = $this->getPredState($userId, $eventId);
                $winner = $state['winner'] ?? '';
                $total  = $state['total']  ?? '';

                if ($winner === '' && $total === '') {
                    $this->replyWithQuickReply("尚未選擇任何項目，可選主勝/客勝或大分/小分。", $eventId);
                    return;
                }

                // 可選：開賽前鎖（這裡先示範 60 秒前鎖）
                $ev = model('Event')->find($eventId);
                if ($ev && isset($ev->starttime) && time() >= ((int)$ev->starttime - 60)) {
                    $this->sendReplyMessage("⛔ 已進入開賽前鎖定時間，無法送出。");
                    return;
                }

                // 寫 DB（你專案有自己的 DB 包裝可替換）
                $now = time();
                $winnerSql = ($winner !== '') ? "'{$winner}'" : "NULL";
                $totalSql  = ($total  !== '') ? "'{$total}'"   : "NULL";
                $sql = "
                INSERT INTO user_prediction (user_id, event_id, winner, total, created_at, updated_at)
                VALUES (?, ?, {$winnerSql}, {$totalSql}, ?, ?)
                ON DUPLICATE KEY UPDATE winner = VALUES(winner), total = VALUES(total), updated_at = VALUES(updated_at)
            ";
                db()->query($sql, [$userId, $eventId, $now, $now]);

                $this->clearPredState($userId, $eventId);
                $this->sendReplyMessage("✅ 已送出你的預測！(event:{$eventId})");
                break;

            default:
                $this->sendReplyMessage("尚未支援的操作。");
                break;
        }
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
        return "🎯 選擇預測\n{$title}\n勝負：{$w}　大小：{$t}\n（可繼續點下方按鈕切換，完成後按「送出」）";
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
            "margin" => "md",
            "action" => [
                "type" => "postback",
                "label" => "開始預測",
                "data" => "cmd=pick&event={$ev->id}",
                "displayText" => "{$title}"
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
                ["type" => "separator", "margin" => "md"]
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
    $mk = function($label, $data) {
        return [
            "type" => "action",
            "action" => [
                "type" => "postback",
                "label" => $label,
                "data"  => $data,
                "displayText" => $label
            ]
        ];
    };

    return [
        "items" => [
            $mk("主勝",  "cmd=toggle&event={$eventId}&type=winner&value=home"),
            $mk("客勝",  "cmd=toggle&event={$eventId}&type=winner&value=away"),
            $mk("大分",  "cmd=toggle&event={$eventId}&type=total&value=over"),
            $mk("小分",  "cmd=toggle&event={$eventId}&type=total&value=under"),
            $mk("清除",  "cmd=clear&event={$eventId}"),
            $mk("送出",  "cmd=submit&event={$eventId}")
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
}
