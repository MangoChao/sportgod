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
        // 1) 讀原始 JSON
        $raw = $this->request->getInput();
        $post = json_decode($raw, true);

        // 2) 驗簽（LINE: X-Line-Signature）
        $channelSecret = Config::get("site.line_channel_secret");
        $signature = $this->request->header('X-Line-Signature');
        $hash = base64_encode(hash_hmac('sha256', $raw, $channelSecret, true));
        if (!$signature || !hash_equals($hash, $signature)) {
            Log::info('LINE signature verify failed');
            return $this->error('Forbidden', null, 403);
        }

        // 3) 紀錄少量必要資訊
        Log::info('--- webhook ---');
        Log::info([
            'events_count' => isset($post['events']) && is_array($post['events']) ? count($post['events']) : 0,
        ]);
        Log::info('---------------');

        // 後續照你的流程
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
                    case 'winrate':
                        $this->sendAnalystRanking("winrate");
                        break;

                    case 'profit':
                        $this->sendAnalystRanking("profit");
                        break;

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
            case 'analyst_result':
                $analystId = (int)($p['analyst'] ?? 0);
                if ($analystId > 0) {
                    $this->sendAnalystPredResults($analystId);
                } else {
                    $this->sendReplyMessageCus(["分析師參數錯誤"]);
                }
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

            $todayPending = $this->fetchPredsCombined($analystId, $todayStart, $tomorrowStart, false);

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

    private function sendReplyMessageCus($messages_obj)
    {
        $response_sendReplyMessage = $this->LineBot->sendReplyMessage($this->webhook_replyToken, $messages_obj);
        Log::notice('response_sendReplyMessage:');
        Log::notice($response_sendReplyMessage);
        Log::notice('-------------------------------------------');
    }

    /**
     * 取得未來 N 天（預設 5 天）的賽事列表（依天分組）
     * - 僅回傳乾淨資料欄位，不含任何 HTML
     * - 供 LINE 顯示邏輯（formatListOddsLine / buildPredRow）統一使用
     *
     * @param int $cid  事件分類 ID（0 = 全部）
     * @param int $days 天數（預設 5）
     * @return array 形如：['YYYY-mm-dd' => [ [event...], ... ], ...]
     */
    public function eventlist($cid = 0, $days = 5)
    {
        $table_data_list = [];

        // 從今天 00:00 開始
        $currentTs  = strtotime(date('Y-m-d 00:00:00'));
        $dayIndex   = 0;

        do {
            $dayStart = $currentTs;
            $dayEnd   = strtotime(date('Y-m-d 00:00:00', $dayStart) . ' +1 day');
            $dateKey  = date('Y-m-d', $dayStart);

            // 組 where 條件（ThinkPHP 5 相容）
            $query = model('Event')
                ->where('starttime', '>=', $dayStart)
                ->where('starttime', '<',  $dayEnd);

            if ((int)$cid !== 0) {
                $query = $query->where('event_category_id', (int)$cid);
            }

            // 依開賽時間排序
            $rows = $query->order('starttime asc')->select();

            if ($rows && count($rows) > 0) {
                $list = [];
                foreach ($rows as $v) {
                    // 僅保留顯示會用到的欄位（全部為純資料）
                    $item = [
                        'id'             => isset($v->id) ? (int)$v->id : 0,
                        'starttime'      => isset($v->starttime) ? (int)$v->starttime : 0,
                        'guests'         => isset($v->guests) ? (string)$v->guests : '',
                        'master'         => isset($v->master) ? (string)$v->master : '',

                        // 讓分 & 大小分（保留原字串；可能為 '' 或 '0'）
                        'guests_refund'  => isset($v->guests_refund) ? (string)$v->guests_refund : '',
                        'master_refund'  => isset($v->master_refund) ? (string)$v->master_refund : '',
                        'bigscore'       => isset($v->bigscore) ? (string)$v->bigscore : '',

                        // 比分（若尚未有分數，可為 null）
                        'guests_score'   => isset($v->guests_score) && $v->guests_score !== '' ? (int)$v->guests_score : null,
                        'master_score'   => isset($v->master_score) && $v->master_score !== '' ? (int)$v->master_score : null,
                    ];

                    $list[] = $item;
                }

                if (!empty($list)) {
                    $table_data_list[$dateKey] = $list;
                }
            }

            // 下一天
            $currentTs = $dayEnd;
            $dayIndex++;
        } while ($dayIndex < (int)$days);

        return $table_data_list;
    }

    function formatEventListForLine(array $table_data_list, int $maxChars = 4800): array
    {
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
                $guest  = (string)($ev['guests'] ?? '');
                $master = (string)($ev['master'] ?? '');

                // 第一行：隊伍
                $line1 = "• {$guest} vs {$master}(主)\n";

                // 第二行：時間 + 讓分/大小
                $time = !empty($ev['starttime']) ? date('H:i', (int)$ev['starttime']) : '--:--';
                $oddsLine = $this->formatListOddsLine([
                    'guests_refund' => $ev['guests_refund'] ?? '',
                    'master_refund' => $ev['master_refund'] ?? '',
                    'bigscore'      => $ev['bigscore'] ?? '',
                ]);
                $line2 = $oddsLine !== '' ? "  {$time}  {$oddsLine}\n\n" : "  {$time}\n\n";

                $one = $line1 . $line2;

                if (mb_strlen($buf . $one, 'UTF-8') > $maxChars) {
                    $messages[] = rtrim($buf);
                    $buf = $sectionHeader . $one;
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

    function buildEventRowBox(array $ev): array
    {
        $guest  = (string)($ev['guests'] ?? '');
        $master = (string)($ev['master'] ?? '');
        $teamLine = "{$guest} vs {$master}(主)";

        $time = !empty($ev['starttime']) ? date('m/d H:i', (int)$ev['starttime']) : '--:--';
        $oddsOneLine = $this->formatListOddsLine([
            'guests_refund' => $ev['guests_refund'] ?? '',
            'master_refund' => $ev['master_refund'] ?? '',
            'bigscore'      => $ev['bigscore'] ?? '',
        ]);
        $infoLine = $oddsOneLine !== '' ? ($time . '  ' . $oddsOneLine) : $time;

        return [
            "type" => "box",
            "layout" => "vertical",
            "spacing" => "xs",
            "margin" => "md",
            "action" => [
                "type" => "postback",
                "label" => "開始預測",
                "data"  => json_encode([
                    "cmd"   => "pick",
                    "event" => (int)($ev['id'] ?? 0)
                ], JSON_UNESCAPED_UNICODE),
                "displayText" => $teamLine
            ],
            "contents" => [
                ["type" => "text", "text" => $teamLine, "wrap" => true, "size" => "sm"],
                ["type" => "text", "text" => $infoLine, "wrap" => true, "size" => "xs", "color" => "#666666"],
                ["type" => "separator", "margin" => "md"]
            ]
        ];
    }

    function buildDayBubbles(string $date, array $events, int $rowsPerBubble = 8): array
    {
        $bubbles = [];
        $chunks = array_chunk($events, $rowsPerBubble);

        foreach ($chunks as $chunk) {
            $rows = [];
            foreach ($chunk as $ev) {
                $rows[] = $this->buildEventRowBox($ev);
            }
            // 去掉最後一列的分隔線（若存在）
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
                        "text" => "📅 {$date}",
                        "weight" => "bold",
                        "size" => "md"
                    ]]
                ],
                "body" => [
                    "type" => "box",
                    "layout" => "vertical",
                    "spacing" => "sm",
                    "contents" => $rows
                ],
                // 不在這裡放 footer（頁碼）
            ];
        }

        return $bubbles;
    }

    function tableDataListToFlexMessages(array $table_data_list, int $rowsPerBubble = 8): array
    {
        // 1) 先組出所有日期的 bubbles（不含頁碼）
        $allBubbles = [];
        foreach ($table_data_list as $date => $events) {
            if (!is_array($events) || empty($events)) continue;
            $dayBubbles = $this->buildDayBubbles($date, $events, $rowsPerBubble);
            $allBubbles = array_merge($allBubbles, $dayBubbles);
        }

        if (empty($allBubbles)) {
            return [[
                "type" => "text",
                "text" => "目前沒有賽事資訊。"
            ]];
        }

        // 2) 跨所有日期統一編號頁碼（從 1 到 total）
        $total = count($allBubbles);
        foreach ($allBubbles as $i => &$bubble) {
            $pageNum = $i + 1;
            // 補上 footer：第 X 頁 / 共 Y 頁
            $bubble["footer"] = [
                "type" => "box",
                "layout" => "vertical",
                "contents" => [[
                    "type"   => "text",
                    "text"   => "第 {$pageNum} 頁 / 共 {$total} 頁",
                    "size"   => "xxs",
                    "color"  => "#888888"
                ]]
            ];
        }
        unset($bubble);

        // 3) 10 個 bubble 為一個 carousel（LINE 限制）
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
        $guest  = (string)($it['guests'] ?? '');
        $master = (string)($it['master'] ?? '');
        $teamLine = "{$guest} vs {$master}(主)";
        $time  = !empty($it['starttime']) ? date('m/d H:i', (int)$it['starttime']) : '--:--';

        $lines = [];

        if ($showResult) {
            if ($it['winteam'] !== null && $it['winteam'] !== '') {
                $lines[] = $this->formatSpreadPickLine($it, $it['winteam']) . ' ' . $this->markByComply($it['comply_refund'] ?? 0);
            }
            if ($it['bigsmall'] !== null && $it['bigsmall'] !== '') {
                $lines[] = $this->formatTotalPickLine($it, $it['bigsmall']) . ' ' . $this->markByComply($it['comply_big'] ?? 0);
            }
            if ($score = $this->formatScoreLine($it)) {
                $lines[] = $score;
            }
        } else {
            if ($it['winteam'] !== null && $it['winteam'] !== '') {
                $lines[] = $this->formatSpreadPickLine($it, $it['winteam']);
            }
            if ($it['bigsmall'] !== null && $it['bigsmall'] !== '') {
                $lines[] = $this->formatTotalPickLine($it, $it['bigsmall']);
            }
            if (empty($lines)) {
                $lines[] = $this->formatListOddsLine($it);
            }
        }

        // 第二行塞時間
        array_unshift($lines, $time);

        return [
            "type" => "box",
            "layout" => "vertical",
            "spacing" => "xs",
            "margin"  => "xs",
            "contents" => array_merge(
                [["type" => "text", "text" => $teamLine, "size" => "sm", "wrap" => true]],
                array_map(function ($t) {
                    return ["type" => "text", "text" => $t, "size" => "xs", "wrap" => true];
                }, $lines)
            )
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

            $footerText = "第 " . ($pageIdx + 1) . " 頁 / 共 " . count($chunks) . " 頁";

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
                ],
                "footer" => [
                    "type" => "box",
                    "layout" => "vertical",
                    "contents" => [[
                        "type" => "text",
                        "text" => $footerText,
                        "size" => "xxs",
                        "color" => "#888888"
                    ]]
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

        // 已結算：近 7 天（含今天），維持原邏輯
        $sevenDaysAgo  = strtotime(date('Y-m-d 00:00:00', strtotime('-6 days')));
        $tomorrowStart = strtotime(date('Y-m-d 00:00:00', strtotime('+1 day')));

        // 未結算：不套日期（全部未結算）
        $pending = $this->fetchPredsCombined($analystId, null, null, 'pending');

        // 已結算（近 7 天）
        $settled = $this->fetchPredsCombined($analystId, $sevenDaysAgo, $tomorrowStart, 'settled');

        $messages = [];

        // 1) 未結算（全部）— 不顯示結果；多則 bubble 分頁
        $messages = array_merge(
            $messages,
            $this->buildPredListBubbles($pending, "📝 未結算預測", 8, false, true)
        );

        // 2) 已結算（近7天）— 顯示結果；多則 bubble 分頁
        $messages = array_merge(
            $messages,
            $this->buildPredListBubbles($settled, "📊 已結算預測（近7天）", 8, true, true)
        );

        // 3) 勝率 placeholder
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
     * 取某分析師的預測（可選日期範圍），並合併同場 pred_type
     * @param int        $analystId
     * @param int|null   $start     以 event.starttime 為基準（秒），可為 null
     * @param int|null   $end       同上，可為 null（若 start/end 皆 null，則不套日期條件）
     * @param string     $status    'pending' | 'settled' | 'all'
     *   - pending: p.comply = 0
     *   - settled: p.comply > 0
     *   - all: 不限制
     */
    private function fetchPredsCombined(int $analystId, ?int $start, ?int $end, string $status = 'all'): array
    {
        $query = model('Pred')
            ->alias('p')
            ->join('event e', 'e.id = p.event_id')
            ->field('p.*, e.guests, e.master, e.starttime, e.guests_refund, e.master_refund, e.bigscore, e.guests_score, e.master_score')
            ->where('p.analyst_id', $analystId);

        if ($start !== null) $query->where('e.starttime', '>=', $start);
        if ($end   !== null) $query->where('e.starttime', '<',  $end);

        if ($status === 'pending') {
            $query->where('p.comply', '=', 0);
        } elseif ($status === 'settled') {
            $query->where('p.comply', '>', 0);
        }

        $rows = $query->order('e.starttime desc')->select();

        $merged = [];
        foreach ($rows as $r) {
            $eid = (int)$r['event_id'];
            if (!isset($merged[$eid])) {
                $gs = $r['guests_score'];
                $ms = $r['master_score'];
                $merged[$eid] = [
                    'event_id'      => $eid,
                    'starttime'     => (int)$r['starttime'],
                    'guests'        => (string)$r['guests'],
                    'master'        => (string)$r['master'],
                    'guests_refund' => (string)$r['guests_refund'],
                    'master_refund' => (string)$r['master_refund'],
                    'bigscore'      => (string)$r['bigscore'],
                    'guests_score'  => ($gs === '' || $gs === null) ? null : (int)$gs,
                    'master_score'  => ($ms === '' || $ms === null) ? null : (int)$ms,
                    'winteam'       => null, // 讓分的預測方向
                    'bigsmall'      => null, // 大小的預測方向
                    'comply_refund' => 0,    // 讓分結果：0未確認 1贏 2輸
                    'comply_big'    => 0,    // 大小結果：0未確認 1贏 2輸
                ];
            }

            if ((int)$r['pred_type'] === 1) {
                $merged[$eid]['winteam']       = $r['winteam'];
                $merged[$eid]['comply_refund'] = (int)$r['comply'];
            } elseif ((int)$r['pred_type'] === 2) {
                $merged[$eid]['bigsmall']   = $r['bigsmall'];
                $merged[$eid]['comply_big'] = (int)$r['comply'];
            }
        }

        return array_values($merged);
    }

    // ===== 共用工具：盤口/大小/比分 顯示 =====

    // 將盤口字串的 + / - 反號（例：2+25 ↔ 2-25）
    private function reverseSignStr(string $s): string
    {
        if ($s === '') return $s;
        $out = '';
        for ($i = 0; $i < strlen($s); $i++) {
            $ch = $s[$i];
            if ($ch === '+') $out .= '-';
            elseif ($ch === '-') $out .= '+';
            else $out .= $ch;
        }
        return $out;
    }

    // 是否有有效盤口值（''、'0'、'-' 都視為無）
    private function hasOdds($v): bool
    {
        $s = trim((string)$v);
        return $s !== '' && $s !== '0' && $s !== '-';
    }

    // 賽事列表的一行盤口顯示（例：客讓 2+25 大小 9-20）
    private function formatListOddsLine(array $ev): string
    {
        $gr = trim((string)($ev['guests_refund'] ?? ''));
        $mr = trim((string)($ev['master_refund'] ?? ''));
        $bs = trim((string)($ev['bigscore'] ?? ''));

        $parts = [];
        if ($this->hasOdds($gr)) {
            $parts[] = "客讓 {$gr}";
        } elseif ($this->hasOdds($mr)) {
            $parts[] = "主讓 {$mr}";
        }
        if ($this->hasOdds($bs)) {
            $parts[] = "大小 {$bs}";
        }
        return implode(' ', $parts);
    }

    // 讓分（pred_type=1）的預測文字：會自動在「受讓」時反號
    // 例：客 樂天桃猿 讓分 2+25  或  主 恐龍 受讓 2-25
    private function formatSpreadPickLine(array $ev, $winteam): string
    {
        $guestName = (string)($ev['guests'] ?? '客隊');
        $masterName = (string)($ev['master'] ?? '主隊');
        $gr = trim((string)($ev['guests_refund'] ?? ''));
        $mr = trim((string)($ev['master_refund'] ?? ''));

        if ($this->hasOdds($gr)) {
            return (int)$winteam === 0
                ? "客 {$guestName} 讓分 {$gr}"
                : "主 {$masterName} 受讓 " . $this->reverseSignStr($gr);
        } elseif ($this->hasOdds($mr)) {
            return (int)$winteam === 1
                ? "主 {$masterName} 讓分 {$mr}"
                : "客 {$guestName} 受讓 " . $this->reverseSignStr($mr);
        }
        return "讓分 未開";
    }

    // 大小（pred_type=2）的預測文字：小分時反號
    // 例：大分 9-20  或  小分 9+20
    private function formatTotalPickLine(array $ev, $bigsmall): string
    {
        $bs = trim((string)($ev['bigscore'] ?? ''));
        if (!$this->hasOdds($bs)) return "大小 未開";
        return (int)$bigsmall === 1
            ? "大分 {$bs}"
            : "小分 " . $this->reverseSignStr($bs);
    }

    // 結算狀態符號
    private function markByComply($comply): string
    {
        $c = (int)$comply;
        if ($c === 1) return "✅";
        if ($c === 2) return "❌";
        return "⏳";
    }

    // 已結算比分行（有分數才顯示），例：賽果 5 : 4
    private function formatScoreLine(array $ev): string
    {
        if (
            isset($ev['guests_score'], $ev['master_score'])
            && is_numeric($ev['guests_score']) && is_numeric($ev['master_score'])
        ) {
            return "比分 {$ev['guests_score']} : {$ev['master_score']}";
        }
        return "";
    }

    private function fetchTopAnalysts(int $limit = 10): array
    {
        $rows = model('Analyst')
            ->alias('a')
            ->join('pred p', 'p.analyst_id = a.id')
            ->field('a.*, COUNT(p.id) AS pred_count')
            ->group('a.id')
            ->having('pred_count > 0')
            ->order('a.id asc') // 先假裝這就是排名
            ->limit($limit)
            ->select();

        return $rows ? $rows : [];
    }

    private function buildAnalystRow($a): array
    {
        $name = (string)($a['analyst_name'] ?? '分析師');
        $id   = (int)($a['id'] ?? 0);

        return [
            "type" => "box",
            "layout" => "vertical",
            "spacing" => "xs",
            "margin" => "md",
            "action" => [
                "type" => "postback",
                "label" => "查看預測結果",
                "data"  => json_encode(["cmd" => "analyst_result", "analyst" => $id], JSON_UNESCAPED_UNICODE),
                "displayText" => "查看 {$name} 的預測"
            ],
            "contents" => [
                ["type" => "text", "text" => $name, "wrap" => true, "size" => "sm"],
            ]
        ];
    }

    private function sendAnalystRanking(string $type)
    {
        $title = $type === 'winrate' ? "🏆 勝率排行榜" : "💰 獲利排行榜";
        $analysts = $this->fetchTopAnalysts(10);

        if (empty($analysts)) {
            $this->sendReplyMessageCus([["type" => "text", "text" => "目前沒有分析師預測資料"]]);
            return;
        }

        $chunks = array_chunk($analysts, 8);
        $bubbles = [];
        for ($i = 0; $i < count($chunks); $i++) {
            $rows = [];
            foreach ($chunks[$i] as $a) $rows[] = $this->buildAnalystRow($a);

            $footer = "第 " . ($i + 1) . " 頁 / 共 " . count($chunks) . " 頁";

            $bubbles[] = [
                "type" => "bubble",
                "body" => [
                    "type" => "box",
                    "layout" => "vertical",
                    "contents" => array_merge(
                        [["type" => "text", "text" => $title, "weight" => "bold", "size" => "md"]],
                        $rows
                    )
                ],
                "footer" => [
                    "type" => "box",
                    "layout" => "vertical",
                    "contents" => [[
                        "type" => "text",
                        "text" => $footer,
                        "size" => "xxs",
                        "color" => "#888888"
                    ]]
                ]
            ];
        }

        $msg = [
            "type" => "flex",
            "altText" => $title,
            "contents" => ["type" => "carousel", "contents" => $bubbles]
        ];
        $this->sendReplyMessageCus([$msg]);
    }

    private function sendAnalystPredResults(int $analystId)
    {
        $start = strtotime(date('Y-m-d 00:00:00', strtotime('-6 days')));
        $end   = time();

        $pending = $this->fetchPredsCombined($analystId, $start, $end, false);
        $settled = $this->fetchPredsCombined($analystId, $start, $end, true);

        $msgs = [];
        if (!empty($pending)) {
            $msgs = array_merge($msgs, $this->buildPredListBubbles($pending, "⏳ 未結算預測（近7天）", 8, false, true));
        }
        if (!empty($settled)) {
            $msgs = array_merge($msgs, $this->buildPredListBubbles($settled, "📊 已結算預測（近7天）", 8, true, true));
        }
        if (empty($msgs)) {
            $msgs[] = ["type" => "text", "text" => "這位分析師近七天沒有預測"];
        }
        $this->sendReplyMessageCus($msgs);
    }
}
