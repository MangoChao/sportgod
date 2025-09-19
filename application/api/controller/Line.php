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
        $raw = $this->webhook_postback_data ?? '';
        $p = json_decode($raw, true);

        $cmd = $p['cmd'] ?? null;
        $action  = $p['action'] ?? null;
        $userId = $this->webhook_userId;

        if (!$userId) {
            $this->sendReplyMessage("參數錯誤，請重試。");
            return;
        }

        switch ($cmd) {
            case 'menu':
                $this->handleMainMenuAction($action);
                return;
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

    // 主選單分流：這裡只先實作 'mine'，其他 action 可再補
    private function handleMainMenuAction(?string $action): void
    {
        switch ($action) {
            case 'mine':
                // 就像之前輸入「賽事」那條路徑：列出賽事清單，讓用戶點賽事開始預測
                $this->sendTodayEventsList(); // ←看下面方法
                return;

            case 'winrate':
            case 'profit':
            case 'results':
            case 'heatmap':
                // 先回占位文字，之後你再換成實作
                $this->sendReplyMessageCus([[
                    "type" => "text",
                    "text" => "功能開發中：{$action}"
                ]]);
                return;

            default:
                // 回主選單
                $messages_obj = $this->buildMainMenuFlex();
                $this->sendReplyMessageCus($messages_obj);
                return;
        }
    }

    /**
     * 合併：把賽事 bubbles + 我的今日預測 bubble 放進同一個 carousel
     * - 最多 10 個 bubbles（LINE 限制）
     * - 只回「一則」訊息：type=flex
     */
    private function buildUnifiedEventsAndMyPredsFlex(array $table_data_list, array $myPredsCombined, int $rowsPerBubble = 8): array
    {
        // 1) 先做賽事 bubbles（你原本的方法）
        $eventBubbles = [];
        foreach ($table_data_list as $date => $events) {
            if (!is_array($events) || empty($events)) continue;
            // 你現有的：buildDayBubbles($date, $events, $rowsPerBubble)
            $dayBubbles = $this->buildDayBubbles($date, $events, $rowsPerBubble);

            // 建議：把 row 的 margin / spacing 降到更緊湊
            foreach ($dayBubbles as &$b) {
                if (isset($b['body']['spacing'])) $b['body']['spacing'] = 'xs';
                // 如果你在 row 裡有 "margin":"md" 的 separator，可以改成 "xs"
            }
            unset($b);

            $eventBubbles = array_merge($eventBubbles, $dayBubbles);
        }

        // 2) 我的今日預測（合併版）→ 單一 bubble
        $myPredBubbleMsgs = $this->buildMyPredsBubbleCombined($myPredsCombined); // 回傳的是 [ 一則 flex ]
        $myPredBubble = $myPredBubbleMsgs[0]['contents']; // 取出 bubble 本體

        // 3) 合併到同一個 carousel，最多 10 個 bubble
        $allBubbles = $eventBubbles;
        // 最後插入「我的今日預測」這顆
        $allBubbles[] = $myPredBubble;
        $allBubbles = array_slice($allBubbles, 0, 10);

        // 4) 打包成「一則」flex 訊息
        $oneMessage = [
            "type" => "flex",
            "altText" => "賽事清單與我的今日預測",
            "contents" => [
                "type" => "carousel",
                "contents" => $allBubbles
            ]
        ];

        // 回傳「一則」訊息（陣列）
        return [$oneMessage];
    }
    private function buildCompactMyPredRow(array $it): array
    {
        $time  = $it['starttime'] ? date('H:i', (int)$it['starttime']) : '--:--';
        $title = "{$time} {$it['guests']} vs {$it['master']}(主)";

        // enum：winteam 1=主、0=客；bigsmall 1=大、0=小
        $winnerText = ($it['winteam'] === null || $it['winteam'] === '') ? '未選'
            : ((int)$it['winteam'] === 1 ? '主勝' : '客勝');

        $totalText  = ($it['bigsmall'] === null || $it['bigsmall'] === '') ? '未選'
            : ((int)$it['bigsmall'] === 1 ? '大分' : '小分');

        $sub = "勝負：{$winnerText}  大小：{$totalText}";

        return [
            "type" => "box",
            "layout" => "vertical",
            "spacing" => "xs",
            "margin" => "xs",
            "action" => [
                "type" => "postback",
                "label" => "修改",
                "data"  => json_encode(["cmd" => "pick", "event" => $it['event_id']], JSON_UNESCAPED_UNICODE),
                "displayText" => "修改預測"
            ],
            "contents" => [
                ["type" => "text", "text" => $title, "size" => "sm", "weight" => "bold", "wrap" => true],
                ["type" => "text", "text" => $sub,   "size" => "xs", "color" => "#666666", "wrap" => true],
            ]
        ];
    }

    /**
     * 建立單一 bubble，同時包含：
     *  - 上半部：今日可預測賽事（前 $maxEvents 筆）
     *  - 下半部：我的今日預測（前 $maxPreds 筆）
     *  - 底部：更多按鈕（postback）
     */
    private function buildUnifiedTodayBubble(array $eventsToday, array $myPredsCombined, int $maxEvents = 6, int $maxPreds = 4): array
    {
        // 上半部：賽事
        $eventRows = [];
        foreach (array_slice($eventsToday, 0, $maxEvents) as $ev) {
            $eventRows[] = $this->buildCompactEventRow($ev);
            $eventRows[] = ["type" => "separator", "margin" => "xs"];
        }
        if (!empty($eventRows)) array_pop($eventRows); // 去掉最後一條分隔線

        // 下半部：我的預測
        $predRows = [];
        foreach (array_slice($myPredsCombined, 0, $maxPreds) as $it) {
            $predRows[] = $this->buildCompactMyPredRow($it);
            $predRows[] = ["type" => "separator", "margin" => "xs"];
        }
        if (!empty($predRows)) array_pop($predRows);

        $hasMoreEvents = count($eventsToday) > $maxEvents;
        $hasMorePreds  = count($myPredsCombined) > $maxPreds;

        $footerBtns = [];
        if ($hasMoreEvents) {
            $footerBtns[] = [
                "type" => "button",
                "height" => "sm",
                "style" => "secondary",
                "action" => [
                    "type" => "postback",
                    "label" => "更多賽事",
                    "data"  => json_encode(["cmd" => "events_more"], JSON_UNESCAPED_UNICODE),
                    "displayText" => "更多賽事"
                ]
            ];
        }
        if ($hasMorePreds) {
            $footerBtns[] = [
                "type" => "button",
                "height" => "sm",
                "style" => "secondary",
                "action" => [
                    "type" => "postback",
                    "label" => "更多我的預測",
                    "data"  => json_encode(["cmd" => "mypreds_more"], JSON_UNESCAPED_UNICODE),
                    "displayText" => "更多我的預測"
                ]
            ];
        }

        $sections = [];

        $sections[] = [
            "type" => "box",
            "layout" => "vertical",
            "spacing" => "xs",
            "margin" => "none",
            "contents" => array_merge(
                [["type" => "text", "text" => "📋 今日可預測賽事", "weight" => "bold", "size" => "sm"]],
                empty($eventRows) ? [["type" => "text", "text" => "今天暫無賽事。", "size" => "xs", "color" => "#666666"]]
                    : $eventRows
            )
        ];

        $sections[] = ["type" => "separator", "margin" => "sm"];

        $sections[] = [
            "type" => "box",
            "layout" => "vertical",
            "spacing" => "xs",
            "margin" => "none",
            "contents" => array_merge(
                [["type" => "text", "text" => "📝 我的今日預測", "weight" => "bold", "size" => "sm"]],
                empty($predRows) ? [["type" => "text", "text" => "今天尚未有預測。", "size" => "xs", "color" => "#666666"]]
                    : $predRows
            )
        ];

        $bubble = [
            "type" => "bubble",
            "size" => "mega",
            "body" => [
                "type" => "box",
                "layout" => "vertical",
                "spacing" => "sm",
                "margin" => "md",
                "contents" => $sections
            ],
        ];

        if (!empty($footerBtns)) {
            $bubble["footer"] = [
                "type" => "box",
                "layout" => "vertical",
                "spacing" => "sm",
                "contents" => $footerBtns
            ];
        }

        return $bubble;
    }


    private function buildCompactEventRow($ev): array
    {
        $time = (isset($ev->starttime) && $ev->starttime) ? date('H:i', (int)$ev->starttime) : '--:--';
        $guest = (string)($ev->guests ?? '');
        $master = (string)($ev->master ?? '');
        $guestRefund = ($ev->guests_refund ?? '') === '' ? '-' : (string)$ev->guests_refund;
        $masterRefund = ($ev->master_refund ?? '') === '' ? '-' : (string)$ev->master_refund;
        $bigscore = ($ev->bigscore ?? '') === '' ? '-' : (string)$ev->bigscore;

        $title = "{$time} {$guest} vs {$master}(主)";
        $sub   = "盤口：客 {$guestRefund}／主 {$masterRefund}  大小：{$bigscore}";

        return [
            "type" => "box",
            "layout" => "vertical",
            "spacing" => "xs",
            "margin" => "xs",
            "action" => [
                "type" => "postback",
                "label" => "開始預測",
                "data" => json_encode(["cmd" => "pick", "event" => (int)$ev->id], JSON_UNESCAPED_UNICODE),
                "displayText" => $title
            ],
            "contents" => [
                ["type" => "text", "text" => $title, "size" => "sm", "weight" => "bold", "wrap" => true],
                ["type" => "text", "text" => $sub,   "size" => "xs", "color" => "#666666", "wrap" => true],
            ]
        ];
    }

    private function sendTodayEventsList(int $cid = 0): void
    {
        // 取今日賽事（你的 eventlist 會分日回來）
        $table = $this->eventlist($cid);
        $today = date('Y-m-d');
        $eventsToday = $table[$today] ?? [];

        // 取我的今日預測（合併同場）
        $analystId = $this->getAnalystIdByLineUserId($this->webhook_userId);
        $combined  = $analystId ? $this->fetchTodayMyPredsCombined($analystId) : [];

        // 組一個 bubble（上賽事，下我的預測）
        $bubble = $this->buildUnifiedTodayBubble($eventsToday, $combined, 6, 4);

        // 一則 flex 訊息
        $messages = [[
            "type" => "flex",
            "altText" => "今日賽事與我的預測",
            "contents" => $bubble
        ]];

        $this->sendReplyMessageCus($messages);
    }

    /**
     * 取「今天」我的預測，並把同一場的 pred_type=1/2 合併
     * - 以 predtime 較新者覆蓋同類型
     * - 回傳為已合併的列表，依開賽時間排序
     */
    private function fetchTodayMyPredsCombined(int $analystId): array
    {
        $start = strtotime('today');
        $end   = strtotime('tomorrow');

        // 把今天該分析師所有（大小/勝負）都抓出來
        $rows = model('Pred')->alias('p')
            ->join('event e', 'e.id = p.event_id')
            ->where('p.analyst_id = ' . $analystId . ' AND e.starttime >= ' . $start . ' AND e.starttime < ' . $end)
            ->order('p.predtime desc')              // 讓較新的排前面，方便合併時「新覆蓋舊」
            ->select();

        if (!$rows) return [];

        // 合併：以 event_id 為 key
        $byEvent = [];
        foreach ($rows as $r) {
            $eid = (int)$r->event_id;
            if (!isset($byEvent[$eid])) {
                $byEvent[$eid] = [
                    'event_id'     => $eid,
                    'starttime'    => (int)$r->starttime,
                    'guests'       => (string)$r->guests,
                    'master'       => (string)$r->master,
                    // 兩種預測，初始化為 null
                    'winteam'      => null,  // 1=主、0=客
                    'bigsmall'     => null,  // 1=大、0=小
                    // 記錄各自最新的 predtime
                    '_win_predtime' => 0,
                    '_big_predtime' => 0,
                ];
            }
            // 依 pred_type 寫入；較新的 predtime 覆蓋較舊的
            if ((int)$r->pred_type === 1) { // 勝負
                if ($r->predtime >= $byEvent[$eid]['_win_predtime']) {
                    $byEvent[$eid]['winteam']       = $r->winteam;
                    $byEvent[$eid]['_win_predtime'] = (int)$r->predtime;
                }
            } elseif ((int)$r->pred_type === 2) { // 大小
                if ($r->predtime >= $byEvent[$eid]['_big_predtime']) {
                    $byEvent[$eid]['bigsmall']      = $r->bigsmall;
                    $byEvent[$eid]['_big_predtime'] = (int)$r->predtime;
                }
            }
        }

        // 轉成索引陣列並依開賽時間排序
        $list = array_values($byEvent);
        usort($list, function ($a, $b) {
            return ($a['starttime'] <=> $b['starttime']);
        });

        return $list;
    }

    private function buildMyPredsBubbleCombined(array $combined): array
    {
        if (empty($combined)) {
            return [[
                "type" => "flex",
                "altText" => "我的今日預測",
                "contents" => [
                    "type" => "bubble",
                    "header" => [
                        "type" => "box",
                        "layout" => "vertical",
                        "contents" => [[
                            "type" => "text",
                            "text" => "📝 我的今日預測",
                            "weight" => "bold",
                            "size" => "md"
                        ]]
                    ],
                    "body" => [
                        "type" => "box",
                        "layout" => "vertical",
                        "contents" => [[
                            "type" => "text",
                            "text" => "今天尚未有預測。",
                            "size" => "sm",
                            "color" => "#666666",
                            "wrap" => true
                        ]]
                    ]
                ]
            ]];
        }

        $rows = [];
        foreach ($combined as $it) {
            $time  = $it['starttime'] ? date('H:i', (int)$it['starttime']) : '--:--';
            $title = "{$time}  {$it['guests']} vs {$it['master']}(主)";

            // 你的 enum：winteam 1=主、0=客；bigsmall 1=大、0=小
            $winnerText = ($it['winteam'] === null || $it['winteam'] === '') ? '未選'
                : ((int)$it['winteam'] === 1 ? '主勝' : '客勝');

            $totalText  = ($it['bigsmall'] === null || $it['bigsmall'] === '') ? '未選'
                : ((int)$it['bigsmall'] === 1 ? '大分' : '小分');

            $sub = "勝負：{$winnerText}　大小：{$totalText}";

            $rows[] = [
                "type" => "box",
                "layout" => "vertical",
                "spacing" => "xs",
                "margin" => "md",
                "contents" => [
                    ["type" => "text", "text" => $title, "size" => "sm", "weight" => "bold", "wrap" => true],
                    ["type" => "text", "text" => $sub,   "size" => "xs", "color" => "#666666", "wrap" => true],
                ],
                // （可選）給一鍵修改入口
                // "action" => [
                //   "type" => "postback",
                //   "label" => "修改",
                //   "data"  => json_encode(["cmd"=>"pick","event"=>$it['event_id']], JSON_UNESCAPED_UNICODE),
                //   "displayText" => "修改預測"
                // ]
            ];
            $rows[] = ["type" => "separator", "margin" => "md"];
        }
        if (!empty($rows)) array_pop($rows);

        $count = count($combined);

        return [[
            "type" => "flex",
            "altText" => "我的今日預測",
            "contents" => [
                "type" => "bubble",
                "size" => "mega",
                "header" => [
                    "type" => "box",
                    "layout" => "vertical",
                    "contents" => [[
                        "type" => "text",
                        "text" => "📝 我的今日預測（{$count}）",
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
            ]
        ]];
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

        // 勝負 winner: 'home' | 'away' | ''
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
}
