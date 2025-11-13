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

    protected $mAnalyst = null;
    
    protected $sendTag = false; //預防送兩次訊息, 提前終止用

    public function _initialize()
    {
        parent::_initialize();
        Log::init(['type' => 'File', 'log_name' => 'line_bot']);

        $channelAccessToken = Config::get("site.line_channel_access_token");
        $this->LineBot = new LineBot($channelAccessToken);
    }

    public function index()
    {
        $this->success('入口異常');
    }

    public function createRichmenu()
    {
        $imagePath = ROOT_PATH . "public_html/assets/img/linebot/menu.jpg";
        $lineMenuMoreLink = Config::get("site.line_menu_more_link");
        $richmenu = [
            "size" => ["width" => 2500, "height" => 1686],
            "selected" => true,
            "name" => "MainMenu",
            "chatBarText" => "主選單",
            "areas" => [
                [
                    "bounds" => ["x" => 0, "y" => 0, "width" => 833, "height" => 843],
                    "action" => [
                        "type" => "postback",
                        "label" => "勝率排行榜",
                        "data" => json_encode(["cmd" => "menu", "action" => "winrate"], JSON_UNESCAPED_UNICODE),
                        "displayText" => "勝率排行榜",
                    ],
                ],
                [
                    "bounds" => ["x" => 833, "y" => 0, "width" => 834, "height" => 843],
                    "action" => [
                        "type" => "postback",
                        "label" => "獲利排行榜",
                        "data" => json_encode(["cmd" => "menu", "action" => "profit"], JSON_UNESCAPED_UNICODE),
                        "displayText" => "獲利排行榜",
                    ],
                ],
                [
                    "bounds" => ["x" => 1667, "y" => 0, "width" => 833, "height" => 843],
                    "action" => [
                        "type" => "postback",
                        "label" => "我的預測",
                        "data" => json_encode(["cmd" => "menu", "action" => "mine"], JSON_UNESCAPED_UNICODE),
                        "displayText" => "我的預測",
                    ],
                ],
                [
                    "bounds" => ["x" => 0, "y" => 843, "width" => 833, "height" => 843],
                    "action" => [
                        "type" => "postback",
                        "label" => "預測結果",
                        "data" => json_encode(["cmd" => "menu", "action" => "results"], JSON_UNESCAPED_UNICODE),
                        "displayText" => "預測結果",
                    ],
                ],
                [
                    "bounds" => ["x" => 833, "y" => 843, "width" => 834, "height" => 843],
                    "action" => [
                        "type" => "uri",
                        "label" => "註冊看更多預測",
                        "uri" => $lineMenuMoreLink,
                    ],
                ],
                [
                    "bounds" => ["x" => 1667, "y" => 843, "width" => 833, "height" => 843],
                    "action" => [
                        "type" => "postback",
                        "label" => "活動圖",
                        "data" => json_encode(["cmd" => "menu", "action" => "heatmap"], JSON_UNESCAPED_UNICODE),
                        "displayText" => "活動圖",
                    ],
                ],
            ],
        ];

        $lineBotResponse = $this->LineBot->createRichMenu($richmenu);
        if ($lineBotResponse['success'] and isset($lineBotResponse['data']['richMenuId'])) {
            $richMenuId = $lineBotResponse['data']['richMenuId'];
            Log::notice('richMenuId: ' . $richMenuId);
            Log::notice('img:' . $imagePath);
            $lineBotResponse = $this->LineBot->uploadRichMenuImage($richMenuId, $imagePath);
            if ($lineBotResponse['success']) {
                Log::notice('完整建立成功');
                Log::notice('設為預設');
                $lineBotResponse = $this->LineBot->setDefaultRichMenu($richMenuId);
                if (!$lineBotResponse['success']) {
                    Log::notice('設為預設失敗 ' . $richMenuId);
                    $this->error('設為預設失敗');
                }
            } else {
                Log::notice('上傳圖片失敗, 刪除菜單' . $richMenuId);
                $lineBotResponse = $this->LineBot->deleteRichMenu($richMenuId);
                if (!$lineBotResponse['success']) {
                    Log::notice('刪除菜單失敗 ' . $richMenuId);
                    $this->error('刪除菜單失敗');
                }
                $this->error('上傳圖片失敗');
            }
        } else {
            Log::notice('取得richMenuId失敗');
            $this->error('取得richMenuId失敗');
        }

        $this->success();
    }

    public function webhook()
    {
        // 1) 讀原始 JSON
        $raw = $this->request->getInput();
        $post = json_decode($raw, true);
        // Log::info('--- webhook ---');
        // Log::info($raw);
        // Log::info('---------------');

        // 2) 驗簽（LINE: X-Line-Signature）
        $channelSecret = Config::get("site.line_channel_secret");
        $signature = $this->request->header('X-Line-Signature');
        $hash = base64_encode(hash_hmac('sha256', $raw, $channelSecret, true));
        if (!$signature || !hash_equals($hash, $signature)) {
            Log::info('LINE signature verify failed');
            return $this->error('Forbidden', null, 403);
        }

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
                    if (!$this->webhook_userId) {
                        //不處裡非用戶發的webhook
                        return $this->error('Forbidden', null, 403);
                    }
                        
                    $this->mAnalyst = $this->getAnalyst($this->webhook_userId);
                    if(!$this->mAnalyst){
                        //異常
                        return $this->error('Forbidden', null, 403);
                    }

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
        $messageLower = trim(strtolower($message));
        Log::notice("message:[" . $message . "]");
        
        switch ($messageLower) {
            default:
                //開通代碼
                if ($this->mAnalyst->seepred == 0 && preg_match('/^##/', $messageLower)) {
                    $this->activateAnalyst($messageLower);
                }
                if (preg_match('/^改暱稱[:：](.*)$/u', $message, $matches)) {
                    if ($this->mAnalyst->name_edit == 1){
                        if (preg_match('/^改暱稱[:：](.*)$/u', $message, $matches)) {
                            $nickname = trim($matches[1]); // 去掉前後空白
                            if (!preg_match('/^[\p{Han}a-zA-Z0-9]+$/u', $nickname)) {
                                $this->sendReplyMessage("暱稱只能包含中文、英文或數字");
                                break;
                            }

                            $len = 0;
                            $chars = preg_split('//u', $nickname, -1, PREG_SPLIT_NO_EMPTY);
                            foreach ($chars as $ch) {
                                // 中文算2，其他算1
                                $len += preg_match('/\p{Han}/u', $ch) ? 2 : 1;
                            }
                            
                            if ($len > 20) {
                                $this->sendReplyMessage("暱稱長度超過限制");
                                break;
                            }

                            $this->mAnalyst->name_edit = 0;
                            $this->mAnalyst->analyst_name = $nickname;
                            $this->mAnalyst->save();
                            $this->sendReplyMessage("暱稱更改成功");
                            break;
                        }
                    }else{
                        $this->sendReplyMessage("已改過暱稱");
                        break;
                    }
                }
                break;
            case "menu":
                $messagesObj = $this->buildMainMenuFlex();
                $this->sendReplyMessageCus($messagesObj);
                break;
        }
    }

    public function webhook_postback_event()
    {
        $data = $this->webhook_postback_data ?? '';
        Log::notice("postback:[" . $data . "]");
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
                $catId  = isset($p['cat']) ? (int)$p['cat'] : -1;
                $periodParam = strtolower($p['period'] ?? '');
                $period = in_array($periodParam, ['week', 'month'], true) ? $periodParam : '';

                switch ($action) {
                    case 'winrate':
                        if ($catId < 0) { // 先挑類型
                            $this->sendReplyMessageCus($this->buildCategoryPickerGrid($action));
                            break;
                        }
                        if ($period === '') {
                            $this->sendReplyMessageCus($this->buildPeriodPicker($action, $catId));
                            break;
                        }
                        $this->sendAnalystRanking($action, $catId, $period);
                        break;

                    case 'profit':
                        if ($catId < 0) {
                            $this->sendReplyMessageCus($this->buildCategoryPickerGrid($action));
                            break;
                        }
                        if ($period === '') {
                            $this->sendReplyMessageCus($this->buildPeriodPicker($action, $catId));
                            break;
                        }
                        $this->sendAnalystRanking($action, $catId, $period);
                        break;

                    case 'mine':
                        if ($catId < 0) {
                            $this->sendReplyMessageCus($this->buildCategoryPickerGrid('mine'));
                            break;
                        }
                        $this->sendTodayEventsList($catId); // 帶入類型 id
                        break;

                    case 'results':
                        if ($catId < 0) {
                            $this->sendReplyMessageCus($this->buildCategoryPickerGrid('results'));
                            break;
                        }
                        $this->sendMyPredResultsPage($catId);
                        break;

                    case 'heatmap':
                        $this->sendHeatmap();
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
                if (($state[$type] ?? '') === $value) $value = '';
                $state[$type] = $value;
                $this->setPredState($userId, $eventId, $state);

                $msg = $this->buildPreviewText($eventId, $state['winner'] ?? '', $state['total'] ?? '');
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

                $res = $this->createPred($this->mAnalyst->id, $paramsRefund, $paramsBigs);
                $replyMessage = $res ? "✅ 已送出你的預測！" : "預測失敗, 請聯絡客服";

                $this->clearPredState($userId, $eventId);
                $this->sendReplyMessage($replyMessage);
                break;

            case 'analyst_result':
                $analystId = (int)($p['analyst'] ?? 0);
                if ($analystId > 0) {
                    $this->sendReplyMessageCus($this->buildPredResultsPageMessages($analystId));
                } else {
                    Log::info($p);
                    $this->sendReplyMessage("分析師參數錯誤");
                }
                break;
            case 'reward':
                $action = $p['action'] ?? '';
                if ($action === 'first_prize') {
                    $this->getFirstPrize();
                } else {
                    $this->sendReplyMessage("尚未支援的操作。");
                }
                break;
            default:
                $this->sendReplyMessage("尚未支援的操作。");
                break;
        }
    }
    
    private function getFirstPrize()
    {

        $this->sendReplyMessage("您不符合活動獎金資格。");
    }
    
    private function activateAnalyst($code): void
    {
        $mCode = model("LinePredCode")->get(['code' => $code, 'status' => 0]);
        if($mCode){
            $mCode->analyst_id = $this->mAnalyst->id;
            $mCode->status = 1;
            $mCode->save();
            $this->mAnalyst->seepred = 1;
            $this->mAnalyst->save();
            $this->sendReplyMessage("開通成功");
        }
    }

    private function sendHeatmap(): void
    {
        $img1 = $this->site_url['furl'].'/assets/img/linebot/heatmap/heatmap1.jpg';
        $text1 = "1️⃣ 連輸七天
 當周歷史總帳結果，連續七天皆負。
2️⃣ 每日單場下注金額需達 $1,000 以上
 僅限體育賽事盤口。
3️⃣ 需提供截圖
 提交「分析師推薦場次截圖」＋「BC博球娛樂城注單截圖」。

📌 全部條件達成後即可申請活動獎金，隔周二可申請 $7,000！
※ 活動最終解釋權歸 賽事俱樂部 所有。";
        $img2 = $this->site_url['furl'].'/assets/img/linebot/heatmap/heatmap2.jpg';
        $text2 = "每日參加賽事預測，展現你的眼光與實力！
每週勝率達80%並且獲利最高者即獲【預測王3萬獎金】💰
越準越強，榮耀與獎金等你拿！
👉 每天都能預測、每週都有贏家！

※ 活動最終解釋權歸 賽事俱樂部 所有。";
        $img3 = $this->site_url['furl'].'/assets/img/linebot/heatmap/heatmap3.jpg';
        $text3 = "改暱稱（僅限中、英、數字，最長 10 個中文字）。
對話框輸入 
改暱稱:(你的暱稱)
送出即更改完成
為維護預測公平性，暱稱僅能修改一次，送出後無法更改。";
        $flex = [
            'type' => 'carousel',
            'contents' => [
                $this->sendHeatmapBubble($img1, $text1),
                $this->sendHeatmapBubble($img2, $text2, ['cmd' => 'reward', 'action' => 'first_prize', 'displayText' => '檢查資格..']),
                $this->sendHeatmapBubble($img3, $text3)
            ]
        ];

        $message = [
            'type' => 'flex',
            'altText' => '活動圖',
            'contents' => $flex
        ];

        $this->sendReplyMessageCus([$message]);
    }

    private function sendHeatmapBubble($img, $text, $actionData = null)
    {
        $bubble = [
            'type' => 'bubble',
            'hero' => [
                'type' => 'image',
                'url' => $img,
                'size' => 'full',
                'aspectRatio' => '1:1',
                'aspectMode' => 'cover',
            ]
        ];
        
        // 如果有指定 postback 行為
        if ($actionData) {
            $bubble['hero']['action'] = [
                'type' => 'postback',
                'data' => json_encode($actionData, JSON_UNESCAPED_UNICODE),
                'displayText' => $actionData['displayText']
            ];
        }

        if($text){
            $bubble['body'] = [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => $text,
                        'size' => 'sm',
                        'color' => '#000000',
                        'wrap' => true,
                        'weight' => 'regular',
                        'margin' => 'none'
                    ]
                ]
            ];
        }
        return $bubble;
    }

    private function sendTodayEventsList(?int $categoryId = null): void
    {
        // 1) 賽事清單（原本就有）
        $tableDataList = $this->eventlist($categoryId);
        $flexMessages = $this->tableDataListToFlexMessages($tableDataList, 8); // 可能多則

        // 2) 我的「今天起到未來」的預測（未結算、含未來）
        $analystId = $this->getAnalystIdByLineUserId($this->webhook_userId);
        $myPredMsgOne = []; // 最終只放一則（carousel或空）
        if ($analystId) {
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            // 抓到未來 180 天；需要更長就調整這個天數即可
            $futureEnd  = strtotime(date('Y-m-d 00:00:00', strtotime('+180 days')));

            // 仍沿用既有查詢函式，時間範圍：今天起 ~ 未來
            
            $todayAndFuture = $this->fetchPredsCombined($analystId, $categoryId, $todayStart, $futureEnd, 'pending');
            // 用共用清單：標題改為「我的預測」，不顯示結果，並用 carousel（同一則可左右滑）
            $tmp = $this->buildPredListBubbles($todayAndFuture, "📝 我的預測", 8, false, true);
            if (!empty($tmp)) {
                $myPredMsgOne[] = $tmp[0]; // 只取一則，避免超過 5 則上限
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
        $messagesObj = [[
            "type" => "text",
            "text" => $text,
            "quickReply" => $this->quickReplyForPrediction($eventId)
        ]];
        $this->sendReplyMessageCus($messagesObj);
    }

    public function getAnalyst($line_user_id)
    {
        Log::notice("line_user_id:".$line_user_id);
        $mUser = model('User')->get(['line_user_id' => $line_user_id, 'status' => 1]);
        if ($mUser) {
            Log::notice("is user.");
            $mAnalyst = model('Analyst')->where('user_id = '.$mUser->id)->find();
            if(!$mAnalyst){
                Log::notice("new Analyst. create.");
                $params = [
                    'user_id' => $mUser->id,
                    'analyst_name' => $mUser->nickname,
                    'avatar' => $mUser->avatar,
                    'status' => 1,
                    'admin_id' => 0,
                    'autopred' => 0,
                    'free' => 1,
                ];
                $mAnalyst = model('Analyst')::create($params);
            }
        } else {
            $mUser = model('Userfree')->get(['line_user_id' => $line_user_id]);
            if (!$mUser) {
                Log::notice("new free user. create.");
                $params = [
                    'line_user_id' => $line_user_id,
                ];
                $mUser = model('Userfree')::create($params);
            } else {
                Log::notice("is free user.");
            }
            $mAnalyst = model('Analyst')->where('user_free = '.$mUser->id)->find();
            if(!$mAnalyst){
                Log::notice("new Analyst. create.");
                $params = [
                    'user_free' => $mUser->id,
                    'analyst_name' => "Line用戶[".$mUser->id."]",
                    'avatar' => '',
                    'status' => 1,
                    'admin_id' => 0,
                    'autopred' => 0,
                    'free' => 1,
                ];
                $mAnalyst = model('Analyst')::create($params);
            }
        }
        Log::notice("get Analyst. [".$mAnalyst->analyst_name."][".$mAnalyst->analyst_name."]");
        return $mAnalyst;
    }

    private function sendReplyMessage($reText)
    {
        $messagesObj = [
            [
                'type' => 'text',
                'text' => $reText,
            ]
        ];
        $this->sendReplyMessageCus($messagesObj);
    }

    private function sendReplyMessageCus($messagesObj)
    {
        //若送過訊息, 則忽略
        if($this->sendTag){
            Log::notice("重複發送, 忽略");
            return false;
        }
        $this->sendTag = true;
        if(Config::get("app_debug")){
            Log::notice("回覆訊息:".json_encode($messagesObj, JSON_UNESCAPED_UNICODE));
        }
        $lineBotResponse = $this->LineBot->sendReplyMessage($this->webhook_replyToken, $messagesObj);
        if ($lineBotResponse['success']) {
            return true;
        }
        return false;
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
    public function eventlist(?int $categoryId = 0, $days = 5)
    {
        $table_data_list = [];

        // 從今天 00:00 開始
        $currentTs  = time();
        $dayIndex   = 0;

        do {
            $dayStart = $currentTs;
            $dayEnd   = strtotime(date('Y-m-d 00:00:00', $dayStart) . ' +1 day');
            $dateKey  = date('Y-m-d', $dayStart);

            // 組 where 條件（ThinkPHP 5 相容）
            $query = model('Event')
                ->where('starttime', '>=', $dayStart)
                ->where('starttime', '<',  $dayEnd);

            if ($categoryId > 0) {
                $query->where('event_category_id', '=', $categoryId);
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

    function tableDataListToFlexMessages(array $tableDataList, int $rowsPerBubble = 8): array
    {
        // 1) 先組出所有日期的 bubbles（不含頁碼）
        $allBubbles = [];
        foreach ($tableDataList as $date => $events) {
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

    private function createPred($analystId, $paramsRefund, $paramsBigs)
    {
        try {
            $this->predByAnalystId($analystId, $paramsRefund, $paramsBigs);
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

    // 取代原本的「預測結果」（自己）
    private function sendMyPredResultsPage(?int $categoryId = null): void
    {
        $analystId = $this->getAnalystIdByLineUserId($this->webhook_userId);
        if (!$analystId) {
            $this->sendReplyMessageCus([["type" => "text", "text" => "尚未綁定帳號。"]]);
            return;
        }
        $this->sendReplyMessageCus($this->buildPredResultsPageMessages($analystId, $categoryId));
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
    private function fetchPredsCombined(int $analystId, ?int $categoryId = 0, ?int $start, ?int $end, string $status = 'all'): array
    {
        $query = model('Pred')
            ->alias('p')
            ->join('event e', 'e.id = p.event_id')
            ->field('p.*, e.guests, e.master, e.starttime, p.guests_refund, p.master_refund, p.bigscore, p.guests_score, p.master_score')
            ->where('p.analyst_id', $analystId);

        if ($start !== null) $query->where('e.starttime', '>=', $start);
        if ($end   !== null) $query->where('e.starttime', '<',  $end);

        if ($status === 'pending') {
            $query->where('p.comply', '=', 0);
        } elseif ($status === 'settled') {
            $query->where('p.comply', '>', 0);
        }

        if ($categoryId > 0) {
            $query->where('e.event_category_id', '=', $categoryId);
        }

        // Log::notice('fetchPredsCombined SQL: ' . $query->fetchSql(true)->select());

        $rows = $query->order('e.starttime desc')->select();

        $merged = [];
        foreach ($rows as $r) {
            if($r->isread == 0){
                $r->isread = 1;
                $r->save();
            }
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

        $findPredCount = sizeof($merged);
        //只要不是看已結算, 或是自己, 都要判斷次數
        if ($findPredCount > 0 && $status !== 'settled' && $analystId != $this->mAnalyst->id) {
            // $seepredCount = $this->mAnalyst->seepred_count;
            $seepredCount = Config::get("site.seepred_count");
            //未開通
            if($this->mAnalyst->seepred == 0 && $this->mAnalyst->seepred_today > 0){
                //沒有額度
                $this->sendReplyMessage("請向客服索取代碼，並輸入代碼");
                return [];
            }elseif($this->mAnalyst->seepred == 1){ //有開通
                $lastCount = $seepredCount - $this->mAnalyst->seepred_today; //今日剩餘次數
                if($lastCount <= 0){
                    //沒有額度
                    $this->sendReplyMessage("今日觀看場數已上限");
                    return [];
                }
            }
            $this->mAnalyst->seepred_today += $findPredCount;
            $this->mAnalyst->save();
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
        if ($c === 3) return "⚪"; // 平手
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

    /**
     * 依「勝率」排行（上週／上月 + 類型過濾）
     * 勝率 = wins / (wins + loses)，僅計入 comply IN (1,2)
     *
     * @param int        $limit
     * @param int|null   $categoryId   e.event_category_id
     * @param string     $period       'week' | 'month'
     * @param bool       $printSql     是否輸出完整 SQL（預設 false）
     * @return array
     */
    private function fetchTopAnalystsByWinrate(int $limit, ?int $categoryId, string $period): array
    {
        $printSql = false;
        //最小預測數
        $minTotalCount = $period === 'week' ? 10 : 10;

        // 共用查詢基底
        $base = $this->buildAnalystRankingBase($categoryId, $period);

        // 統計欄位
        $winExpr   = "SUM(CASE WHEN p.comply = 1 THEN 1 ELSE 0 END)";
        $loseExpr  = "SUM(CASE WHEN p.comply = 2 THEN 1 ELSE 0 END)";
        $totalExpr = "({$winExpr} + {$loseExpr})";
        $rateExpr  = "CASE WHEN {$totalExpr} = 0 THEN 0 ELSE {$winExpr} / {$totalExpr} END";

        // 最終查詢（含欄位/群組/排序/限制）
        $final = clone $base;
        $final->field("a.*, {$winExpr} AS win_count, {$loseExpr} AS lose_count, {$totalExpr} AS total_count, {$rateExpr} AS winrate")
            ->group('a.id')
            ->having('total_count >= '.$minTotalCount)
            ->order('winrate DESC, total_count DESC, a.id ASC')
            ->limit($limit);

        if ($printSql) {
            $sql = (clone $final)->fetchSql(true)->select();
            Log::notice("[SQL][fetchTopAnalystsByWinrate] {$sql}");
        }

        // 執行查詢
        $rows = $final->select();
        return $rows ?: [];
    }

    /**
     * 依「盈虧」排行（上週／上月 + 類型過濾）
     * 盈虧 = SUM(result_ratio) * stake / 100
     * 僅計入 result_ratio != 0 的預測
     *
     * @param int        $limit
     * @param int|null   $categoryId
     * @param string     $period       'week' | 'month'
     * @param int        $stake        每筆下注金額
     * @param bool       $printSql     是否輸出 SQL
     * @return array
     */
    private function fetchTopAnalystsByProfit(int $limit, ?int $categoryId, string $period, int $stake = 10000): array
    {
        $printSql = false;
        //最小預測數
        $minTotalCount = $period === 'week' ? 10 : 40;
        
        // 共用查詢基底
        $base = $this->buildAnalystRankingBase($categoryId, $period);

        // 聚合欄位
        $profitExpr     = "SUM(p.result_ratio) * {$stake} / 100";
        $winExpr        = "SUM(CASE WHEN p.result_ratio > 0 THEN 1 ELSE 0 END)";
        $loseExpr       = "SUM(CASE WHEN p.result_ratio < 0 THEN 1 ELSE 0 END)";
        $totalExpr      = "COUNT(*)";

        // 組合最終查詢
        $final = clone $base;
        $final->field("
            a.*,
            {$profitExpr} AS profit,
            {$winExpr} AS win_count,
            {$loseExpr} AS lose_count,
            {$totalExpr} AS total_count
        ")
        ->group('a.id')
        // ->having('total_count >= '.$minTotalCount)
        ->order('profit DESC, total_count DESC, a.id ASC')
        ->limit($limit);

        if ($printSql) {
            $sql = (clone $final)->fetchSql(true)->select();
            Log::notice("[SQL][fetchTopAnalystsByProfit] {$sql}");
        }

        return $final->select() ?: [];
    }


    /**
     * 取得期間範圍（週或月）
     *
     * @param string $period 'week' | 'month'
     * @return array [startTs, endTs]
     */
    private function getPeriodRange(string $period): array
    {
        return $period === 'week'
            ? $this->getLastWeekRange()
            : $this->getLastMonthRange();
    }

    private function buildAnalystRankingBase(?int $categoryId, string $period)
    {
        [$startTs, $endTs] = $this->getPeriodRange($period);

        $base = model('Analyst')->alias('a')
            ->join('pred p', 'p.analyst_id = a.id')
            ->join('event e', 'e.id = p.event_id')
            ->where('e.starttime', '>=', $startTs)
            ->where('e.starttime', '<', $endTs);

        if ($categoryId > 0) {
            $base->where('e.event_category_id', '=', $categoryId);
        }

        return $base;
    }

    private function buildAnalystRow($a): array
    {
        if ($a instanceof \think\Model) {
            $a = $a->toArray();
        }

        $name = (string)($a['analyst_name'] ?? $a['name'] ?? '分析師');
        $id   = (int)($a['id'] ?? 0);

        $wins   = (int)($a['win_count']  ?? 0);
        $loses  = (int)($a['lose_count'] ?? 0);
        $total  = (int)($a['total_count'] ?? ($wins + $loses));

        // 統一第二行格式
        if (isset($a['profit'])) {
            $profit = (float)$a['profit'];
            $profitFormatted = (floor($profit) == $profit) ? number_format((int)$profit) : number_format($profit, 2);
            $profitText = ($profit >= 0 ? '+' : '') . $profitFormatted;
            $metricsText = "輸贏 {$profitText}｜{$total} 場（W {$wins} / L {$loses}）";
        } else {
            $rate  = isset($a['winrate']) ? (float)$a['winrate'] : ($total > 0 ? ($wins / max(1, $total)) : 0.0);
            $ratePct = number_format($rate * 100, 1);
            $metricsText = "勝率 {$ratePct}%｜{$total} 場（W {$wins} / L {$loses}）";
        }

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
                ["type" => "text", "text" => $name, "size" => "sm", "weight" => "bold", "wrap" => false, "maxLines" => 1],
                ["type" => "text", "text" => $metricsText, "size" => "xs", "color" => "#666666", "wrap" => false, "maxLines" => 1],
            ]
        ];
    }

    private function sendAnalystRanking(string $mode, ?int $categoryId = 0, string $period = 'week')
    {
        if ($mode === 'profit') {
            $periodLabel = ($period === 'month') ? '（上月）' : '（上週）';
            $title = "💰 獲利排行榜{$periodLabel}";
            $analysts = $this->fetchTopAnalystsByProfit(10, $categoryId, $period);
        } elseif ($mode === 'winrate') {
            $periodLabel = ($period === 'month') ? '（上月）' : '（上週）';
            $title = "🏆 勝率排行榜{$periodLabel}";
            $analysts = $this->fetchTopAnalystsByWinrate(10, $categoryId, $period);
        } else {
            $title = "排行榜";
            $analysts = [];
        }

        if (empty($analysts)) {
            $this->sendReplyMessageCus([["type" => "text", "text" => "目前沒有分析師預測資料"]]);
            return;
        }

        // ——以下維持你原本的分頁 / bubble 組裝——
        $chunks  = array_chunk($analysts, 8);
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
                    "contents" => [["type" => "text", "text" => $footer, "size" => "xxs", "color" => "#888888"]]
                ]
            ];
        }

        $msg = [
            "type" => "flex",
            "altText" => $title,
            "contents" => ["type" => "carousel", "contents" => $bubbles]
        ];
        // Log::info($msg);
        $this->sendReplyMessageCus([$msg]);
    }

    /**
     * 建構「預測結果」頁面 messages（共用：自己 / 指定分析師）
     * - 未結算：沿用你現有的規則（若你先前已調整成「不設日期」就用 pending 全部；否則就用近14天）
     * - 已結算：近 7 天
     * - 勝率：placeholder
     * - 每一段使用 carousel（同一則訊息左右滑），最後再統一切到最多 5 則
     */
    private function buildPredResultsPageMessages(int $analystId, ?int $categoryId = null): array
    {
        // === 時間範圍 ===
        $fourteenDaysAgo = strtotime(date('Y-m-d 00:00:00', strtotime('-30 days')));
        $tomorrowStart   = strtotime(date('Y-m-d 00:00:00', strtotime('+5 day')));

        // === 未結算 ===
        // 若你要「未結算不設日期」，改成：$pending = $this->fetchPredsCombined($analystId, null, null, 'pending');
        $pending = $this->fetchPredsCombined($analystId, $categoryId, $fourteenDaysAgo, $tomorrowStart, 'pending');

        // === 已結算（近14天）===
        $settled = $this->fetchPredsCombined($analystId, $categoryId, $fourteenDaysAgo, $tomorrowStart, 'settled');

        $messages = [];

        // 1) 未結算（carousel: 同一則裡可左右滑）
        $messages = array_merge(
            $messages,
            $this->buildPredListBubbles($pending, "⏳ 未結算預測", 8, false, true)
        );

        // 2) 已結算（carousel）
        $messages = array_merge(
            $messages,
            $this->buildPredListBubbles($settled, "📊 已結算預測", 8, true, true)
        );

        // 3) 勝率（用上面新函式，與 pending/settled 同期間）
        $stat = $this->computeWinrateForAnalyst($analystId, $categoryId, $fourteenDaysAgo, $tomorrowStart);

        $rateTitle = "📈 勝率（近 30 天內賽事區間）";
        $lines = [
            "總計：{$stat['win']} 勝 / {$stat['lose']} 負（{$stat['rate_str']}）",
            "讓分：{$stat['spread']['win']} 勝 / {$stat['spread']['lose']} 負（{$stat['spread']['rate_str']}）",
            "大小：{$stat['totalm']['win']} 勝 / {$stat['totalm']['lose']} 負（{$stat['totalm']['rate_str']}）",
        ];

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
                        "text" => $rateTitle,
                        "weight" => "bold",
                        "size" => "md",
                        "wrap" => true
                    ]]
                ],
                "body" => [
                    "type" => "box",
                    "layout" => "vertical",
                    "spacing" => "sm",
                    "contents" => array_map(function ($t) {
                        return ["type" => "text", "text" => $t, "size" => "sm", "wrap" => true];
                    }, $lines)
                ]
            ]
        ];

        return array_slice($messages, 0, 5); // 與「預測結果」相同：最多 5 則
    }

    /**
     * 計算單一分析師在指定期間（以 event.starttime 為準）的勝率
     * - 僅計入 p.comply ∈ {1=贏,2=輸}
     * - 同一場若有讓分/大小兩筆，兩筆都會計入（整體勝率看「預測筆數」而非「場次」）
     * - 另提供讓分/大小各自的勝率明細
     *
     * @return array {
     *   win: int, lose: int, total: int, rate: float, rate_str: string,
     *   spread: {win:int, lose:int, total:int, rate:float, rate_str:string},
     *   totalm: {win:int, lose:int, total:int, rate:float, rate_str:string}
     * }
     */
    private function computeWinrateForAnalyst(int $analystId, ?int $categoryId, ?int $start, ?int $end): array
    {
        // 基底查詢
        $q = model('Pred')->alias('p')
            ->join('event e', 'e.id = p.event_id')
            ->where('p.analyst_id', '=', $analystId)
            ->where('p.comply', 'in', [1, 2]);

        if ($start !== null) $q->where('e.starttime', '>=', $start);
        if ($end   !== null) $q->where('e.starttime',  '<',  $end);
        if ($categoryId > 0) $q->where('e.event_category_id', '=', $categoryId);

        // 聚合表達式
        $winExpr      = "SUM(CASE WHEN p.comply = 1 THEN 1 ELSE 0 END)";
        $loseExpr     = "SUM(CASE WHEN p.comply = 2 THEN 1 ELSE 0 END)";
        $totalExpr    = "({$winExpr} + {$loseExpr})";
        $rateExpr     = "CASE WHEN {$totalExpr} = 0 THEN 0 ELSE {$winExpr} / {$totalExpr} END";

        // 分玩法（pred_type: 1=讓分, 2=大小）
        $sWinExpr     = "SUM(CASE WHEN p.pred_type = 1 AND p.comply = 1 THEN 1 ELSE 0 END)";
        $sLoseExpr    = "SUM(CASE WHEN p.pred_type = 1 AND p.comply = 2 THEN 1 ELSE 0 END)";
        $sTotalExpr   = "({$sWinExpr} + {$sLoseExpr})";
        $sRateExpr    = "CASE WHEN {$sTotalExpr} = 0 THEN 0 ELSE {$sWinExpr} / {$sTotalExpr} END";

        $tWinExpr     = "SUM(CASE WHEN p.pred_type = 2 AND p.comply = 1 THEN 1 ELSE 0 END)";
        $tLoseExpr    = "SUM(CASE WHEN p.pred_type = 2 AND p.comply = 2 THEN 1 ELSE 0 END)";
        $tTotalExpr   = "({$tWinExpr} + {$tLoseExpr})";
        $tRateExpr    = "CASE WHEN {$tTotalExpr} = 0 THEN 0 ELSE {$tWinExpr} / {$tTotalExpr} END";

        // 取單列聚合
        $row = $q->field([
            "{$winExpr}  AS win_count",
            "{$loseExpr} AS lose_count",
            "{$totalExpr} AS total_count",
            "{$rateExpr}  AS winrate",
            "{$sWinExpr}  AS s_win",
            "{$sLoseExpr} AS s_lose",
            "{$sTotalExpr} AS s_total",
            "{$sRateExpr}  AS s_rate",
            "{$tWinExpr}  AS t_win",
            "{$tLoseExpr} AS t_lose",
            "{$tTotalExpr} AS t_total",
            "{$tRateExpr}  AS t_rate",
        ])
            ->find();

        // （可選）印出 SQL
        // Log::notice('[SQL][computeWinrate] ' . $q->fetchSql(true)->field("...同上...")->find());

        $win   = (int)($row['win_count']   ?? 0);
        $lose  = (int)($row['lose_count']  ?? 0);
        $total = (int)($row['total_count'] ?? 0);
        $rate  = (float)($row['winrate']   ?? 0);

        $sWin   = (int)($row['s_win']   ?? 0);
        $sLose  = (int)($row['s_lose']  ?? 0);
        $sTotal = (int)($row['s_total'] ?? 0);
        $sRate  = (float)($row['s_rate'] ?? 0);

        $tWin   = (int)($row['t_win']   ?? 0);
        $tLose  = (int)($row['t_lose']  ?? 0);
        $tTotal = (int)($row['t_total'] ?? 0);
        $tRate  = (float)($row['t_rate'] ?? 0);

        $fmt = function (float $x): string {
            // 以百分比顯示到一位小數
            return number_format($x * 100, 1) . '%';
        };

        return [
            'win'   => $win,
            'lose'  => $lose,
            'total' => $total,
            'rate'  => $rate,
            'rate_str' => $fmt($rate),
            'spread' => [
                'win' => $sWin,
                'lose' => $sLose,
                'total' => $sTotal,
                'rate' => $sRate,
                'rate_str' => $fmt($sRate),
            ],
            'totalm' => [
                'win' => $tWin,
                'lose' => $tLose,
                'total' => $tTotal,
                'rate' => $tRate,
                'rate_str' => $fmt($tRate),
            ],
        ];
    }

    // Flex 雙欄網格：自動分頁，每頁最多 10 個（5 行 x 2 欄）
    private function buildCategoryPickerGrid(string $nextAction): array
    {
        $rows = model('Eventcategory')->where('status', 1)->order('id asc')->select();
        if (!$rows || count($rows) === 0) {
            return [["type" => "text", "text" => "目前沒有可選的體育類型"]];
        }
        $cats = [];
        $cats[] = ["id" => 0, "title" => "全部"];
        foreach ($rows as $c) {
            $cats[] = ["id" => (int)$c->id, "title" => (string)$c->title];
        }

        // 2 欄卡片
        $tiles = array_map(function ($c) use ($nextAction) {
            return [
                "type" => "box",
                "layout" => "vertical",
                "paddingAll" => "10px",
                "cornerRadius" => "8px",
                "backgroundColor" => "#F5F7FA",
                "action" => [
                    "type" => "postback",
                    "label" => $c['title'],
                    "data"  => json_encode(["cmd" => "menu", "action" => $nextAction, "cat" => $c['id']], JSON_UNESCAPED_UNICODE),
                    "displayText" => $c['title']
                ],
                "contents" => [[
                    "type" => "text",
                    "text" => mb_strimwidth($c['title'], 0, 20, '…', 'UTF-8'),
                    "size" => "sm",
                    "align" => "center",
                    "wrap" => true,
                    "color" => "#1F2937"
                ]]
            ];
        }, $cats);

        // 兩兩一行
        $rows2col = [];
        for ($i = 0; $i < count($tiles); $i += 2) {
            $rowContents = [$tiles[$i]];
            if (isset($tiles[$i + 1])) $rowContents[] = $tiles[$i + 1];
            $rows2col[] = [
                "type" => "box",
                "layout" => "horizontal",
                "spacing" => "md",
                "contents" => $rowContents
            ];
        }

        // 每頁 5 行（= 10 類）
        $pages = array_chunk($rows2col, 5);
        $bubbles = [];
        foreach ($pages as $pageIdx => $pageRows) {
            $bubbles[] = [
                "type" => "bubble",
                "body" => [
                    "type" => "box",
                    "layout" => "vertical",
                    "spacing" => "md",
                    "contents" => array_merge(
                        [[
                            "type" => "text",
                            "text" => "請選擇體育類型",
                            "weight" => "bold",
                            "size" => "md"
                        ]],
                        $pageRows
                    )
                ],
                "footer" => [
                    "type" => "box",
                    "layout" => "vertical",
                    "contents" => [[
                        "type" => "text",
                        "text" => "第 " . ($pageIdx + 1) . " 頁 / 共 " . count($pages) . " 頁",
                        "size" => "xxs",
                        "color" => "#888888",
                        "align" => "center"
                    ]]
                ]
            ];
        }

        return [[
            "type" => "flex",
            "altText" => "請選擇體育類型",
            "contents" => ["type" => "carousel", "contents" => $bubbles]
        ]];
    }

    private function getLastWeekRange(): array
    {
        $tz   = new \DateTimeZone('Asia/Taipei');
        $now  = new \DateTime('now', $tz);
        $today0 = (clone $now)->setTime(0, 0, 0);

        // 一週以週一為首：本週一
        $w = (int)$today0->format('N'); // 1..7 (Mon..Sun)
        $startOfThisWeek = (clone $today0)->modify('-' . ($w - 1) . ' days'); // 週一 00:00
        $start = (clone $startOfThisWeek)->modify('-7 days');                 // 上週一 00:00
        $end   = (clone $startOfThisWeek)->modify('-1 second');               // 上週日 23:59:59

        return [$start->getTimestamp(), $end->getTimestamp()];
    }

    private function getLastMonthRange(): array
    {
        $tz   = new \DateTimeZone('Asia/Taipei');
        $now  = new \DateTime('now', $tz);
        $firstDayThisMonth = (clone $now)->setTime(0, 0, 0)->modify('first day of this month');

        $start = (clone $firstDayThisMonth)->modify('first day of last month'); // 上月1日 00:00
        $end   = (clone $firstDayThisMonth)->modify('-1 second');               // 上月底 23:59:59

        return [$start->getTimestamp(), $end->getTimestamp()];
    }

    private function buildPeriodPicker(string $nextAction, int $catId): array
    {
        return [[
            "type" => "flex",
            "altText" => "請選擇統計期間",
            "contents" => [
                "type" => "bubble",
                "body" => [
                    "type" => "box",
                    "layout" => "vertical",
                    "spacing" => "md",
                    "contents" => [
                        ["type" => "text", "text" => "請選擇統計期間", "weight" => "bold", "size" => "lg"],
                        ["type" => "separator", "margin" => "sm"],
                        [
                            "type" => "button",
                            "style" => "primary",
                            "action" => [
                                "type" => "postback",
                                "label" => "上週",
                                "data"  => json_encode([
                                    "cmd" => "menu",
                                    "action" => $nextAction,
                                    "cat" => $catId,
                                    "period" => "week"
                                ], JSON_UNESCAPED_UNICODE),
                                "displayText" => "上週"
                            ]
                        ],
                        [
                            "type" => "button",
                            "style" => "secondary",
                            "action" => [
                                "type" => "postback",
                                "label" => "上月",
                                "data"  => json_encode([
                                    "cmd" => "menu",
                                    "action" => $nextAction,
                                    "cat" => $catId,
                                    "period" => "month"
                                ], JSON_UNESCAPED_UNICODE),
                                "displayText" => "上月"
                            ]
                        ]
                    ]
                ]
            ]
        ]];
    }
}
