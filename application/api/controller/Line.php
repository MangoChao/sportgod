<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\controller\LineBot;
use think\Log;
use fast\Http;
use think\Exception;
use think\Config;

class Line extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    protected $chatBarText = '預測家 選單';
    protected $richMenu = [
        'richmenu_idm_1' => '',
        'richmenu_idm_2' => '',
    ];

    protected $webhook_replyToken = null;
    protected $webhook_userId = null;
    protected $webhook_postback_data = null;

    public function _initialize()
    {
        parent::_initialize();
        Log::init(['type' => 'File', 'log_name' => 'line_bot']);
        // $this->getRichMenu();
    }
    
    public function index()
    {
        $this->success('請求成功');
    }
    
    public function webhook()
    {
        exit;
        $post = $this->request->post();
        Log::info('------------------webhook------------------');
        Log::info($post);
        Log::info('-------------------------------------------');
        // $params = [
        //     'request_to_json' => json_encode($post),
        // ];
        // model('Linewebhooklog')::create($params);
        //紀錄事件

        $events = $post['events'] ?? null;
        if(is_array($events) AND sizeof($events)>0){
            foreach($events as $e){
                $events_type = $e['type'] ?? null;
                $source = $e['source'] ?? null;
                $this->webhook_replyToken = $e['replyToken'] ?? null;
                $postback = $e['postback'] ?? null;
                if($postback)
                    $this->webhook_postback_data = $postback['data'] ?? null; 
                if($source){
                    $this->webhook_userId = $source['userId'] ?? null;
                    $source_type = $source['type'] ?? null;
                    $source_groupId = $source['groupId'] ?? null; 
                    if($this->webhook_userId) $this->checkUser($this->webhook_userId);
    
                    if($events_type AND $source_type AND $this->webhook_userId){
                        switch($events_type){
                            case 'message':
                                // $message_type = $e['message']['type'] ?? null;
                                $message_text = $e['message']['text'] ?? null;
                                // if($message_type AND $message_type == 'text')
                                // $message_text = trim($message_text);
                                // $tag = mb_substr($message_text,0,4);
                                // if($tag == '9453'){
                                //     $this->loginUser(mb_substr($message_text,4));
                                // }
                                Log::notice($message_text);
                                if($message_text == "del"){
                                    $response_getRichMenuList = $this->LineBot->getRichMenuList();
                                    if($response_getRichMenuList AND isset($response_getRichMenuList['richmenus'])){
                                        if(sizeof($response_getRichMenuList['richmenus'])>0){
                                            foreach($response_getRichMenuList['richmenus'] as $richmenus){
                                                if(isset($richmenus['name']) AND isset($richmenus['richMenuId'])){
                                                    if(isset($this->richMenu[$richmenus['name']])){
                                                        //刪除bot的舊菜單
                                                        Log::notice('刪除bot的舊菜單 richMenuId:'.$richmenus['richMenuId']);
                                                        $response_deleteRichMenu = $this->LineBot->deleteRichMenu($richmenus['richMenuId']);
                                                        Log::notice('response_deleteRichMenu:');
                                                        Log::notice($response_deleteRichMenu);
                                                        Log::notice('-------------------------------------------');
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
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
    
    // private function loginUser($message_text)
    // {
    //     Log::notice('代碼驗證 message_text:'.$message_text);
    //     $mUser = model('User')->get(['line_user_id' => $this->webhook_userId, 'status' => 1]);
    //     if(!$mUser AND trim($message_text) != ''){
    //         $mUser = model('User')->get(['code' => trim($message_text), 'status' => 1]);
    //         if($mUser AND $this->webhook_userId){
    //             if($mUser->line_user_id){
    //                 //清除菜單指定
    //                 $response_unlinkRichMenuFromUser = $this->LineBot->unlinkRichMenuFromUser($mUser->line_user_id);
    //                 Log::notice('response_unlinkRichMenuFromUser:');
    //                 Log::notice($response_unlinkRichMenuFromUser);
    //                 Log::notice('-------------------------------------------');
    //             }
    //             $mUser->line_user_id = $this->webhook_userId;
    //             $mUser->save();
    //             $this->checkRichMenu($this->webhook_userId);
    //             $messages = [
    //                 [
    //                     'type' => 'text',
    //                     'text' => '登入成功',
    //                 ]
    //             ];
    //             $response_sendReplyMessage = $this->LineBot->sendReplyMessage($this->webhook_replyToken, $messages);
    //             Log::notice('response_sendReplyMessage:');
    //             Log::notice($response_sendReplyMessage);
    //             Log::notice('-------------------------------------------');
    //             if(is_array($response_sendReplyMessage) AND sizeof($response_sendReplyMessage) == 0){
    //                 Log::notice('回應[代碼驗證]成功');
    //             }else{
    //                 Log::notice('回應[代碼驗證]失敗');
    //             }
    //         }else{
    //             $messages = [
    //                 [
    //                     'type' => 'text',
    //                     'text' => '代碼錯誤, 請聯絡客服',
    //                 ]
    //             ];
    //             $response_sendReplyMessage = $this->LineBot->sendReplyMessage($this->webhook_replyToken, $messages);
    //             Log::notice('response_sendReplyMessage:');
    //             Log::notice($response_sendReplyMessage);
    //             Log::notice('-------------------------------------------');
    //             if(is_array($response_sendReplyMessage) AND sizeof($response_sendReplyMessage) == 0){
    //                 Log::notice('回應[代碼驗證]成功');
    //             }else{
    //                 Log::notice('回應[代碼驗證]失敗');
    //             }
    //         }
    //     }
    // }
    
    private function webhook_postback_event()
    {
        if($this->webhook_postback_data){
            $postback_data = [];
            parse_str($this->webhook_postback_data, $postback_data);
            Log::notice($postback_data);
            if(isset($postback_data['action'])){
                switch($postback_data['action']){
                    case 'getActivity':
                        if(!$this->webhook_userId) break;
                        if(!$this->webhook_replyToken) break;
                        $mActivity = model('Activity')->get(['status'=> 1]);
                        if($mActivity){
                            $messages = [
                                [
                                    'type' => 'text',
                                    'text' => $mActivity->content,
                                ]
                            ];
                        }else{
                            $messages = [
                                [
                                    'type' => 'text',
                                    'text' => '尚無活動',
                                ]
                            ];
                        }
                        $response_sendReplyMessage = $this->LineBot->sendReplyMessage($this->webhook_replyToken, $messages);
                        Log::notice('response_sendReplyMessage:');
                        Log::notice($response_sendReplyMessage);
                        Log::notice('-------------------------------------------');
                        if(is_array($response_sendReplyMessage) AND sizeof($response_sendReplyMessage) == 0){
                            Log::notice('回應[活動]成功');
                        }else{
                            Log::notice('回應[活動]失敗');
                        }
                        break;
                    case 'getService':
                        if(!$this->webhook_userId) break;
                        if(!$this->webhook_replyToken) break;
                        
                        $isnew = false;
                        $mUser = model('User')->where("line_user_id = '".$this->webhook_userId."' ")->find();
                        if(!$mUser){
                            $mUser = model('Userfree')->where("line_user_id = '".$this->webhook_userId."' ")->find();
                        }
                        if($mUser){
                            if($mUser->service_id AND $mUser->service_id != 0){
                                $mService = model('Service')->where("status = 1 AND id = ".$mUser->service_id)->find();
                                if(!$mService){
                                    $mService = model('Service')->where("status = 1")->order(['lastget'=>'ASC','id'=>'ASC'])->find();
                                    $isnew = true;
                                }
                            }else{
                                $mService = model('Service')->where("status = 1")->order(['lastget'=>'ASC','id'=>'ASC'])->find();
                                $isnew = true;
                            }
                            if($mService){
                                if($isnew){
                                    $mService->lastget = time();
                                    $mService->save();
                                }
                                $mUser->service_id = $mService->id;
                                $mUser->save();
                                $messages = [
                                    [
                                        'type' => 'text',
                                        'text' => $mService->contact,
                                    ]
                                ];
                            }else{
                                $messages = [
                                    [
                                        'type' => 'text',
                                        'text' => '客服忙線中',
                                    ]
                                ];
                            }
                            $response_sendReplyMessage = $this->LineBot->sendReplyMessage($this->webhook_replyToken, $messages);
                            Log::notice('response_sendReplyMessage:');
                            Log::notice($response_sendReplyMessage);
                            Log::notice('-------------------------------------------');
                            if(is_array($response_sendReplyMessage) AND sizeof($response_sendReplyMessage) == 0){
                                Log::notice('回應[活動]成功');
                            }else{
                                Log::notice('回應[活動]失敗');
                            }
                        }else{
                            
                            Log::notice('發生錯誤');
                        }
                        break;
                    case 'registercustomer':
                        // if(!$this->webhook_userId) break;
                        // if(!$this->webhook_replyToken) break;
                        // $mUser = model('User')->get(['line_user_id' => $this->webhook_userId, 'status' => ['<',2]]);
                        // if(!$mUser){
                        //     $messages = [
                        //         [
                        //             'type' => 'text',
                        //             'text' => '尚未註冊, 請完成註冊',
                        //         ]
                        //     ];
                        //     $response_sendReplyMessage = $this->LineBot->sendReplyMessage($this->webhook_replyToken, $messages);
                        //     Log::notice('response_sendReplyMessage:');
                        //     Log::notice($response_sendReplyMessage);
                        //     Log::notice('-------------------------------------------');
                        //     if(is_array($response_sendReplyMessage) AND sizeof($response_sendReplyMessage) == 0){
                        //         Log::notice('回應[訂閱]成功');
                        //     }else{
                        //         Log::notice('回應[訂閱]失敗');
                        //     }
                        //     break;
                        // }
                        // $qr_url = 'https://liff.line.me/'.$this->site['liffid'].'/rc?id='.$mUser->id;
                        // $back_qr_url = 'https://liff.line.me/'.$this->site['liffid'].'/rc?id='.$mUser->id.'&back=1';
                        // $messages = [
                        //     [
                        //         'type' => 'text',
                        //         'text' => $qr_url,
                        //     ],
                        //     [
                        //         'type' => 'image',
                        //         'originalContentUrl' => $this->site_url['furl'].'/qrcode/?code='.urlencode($qr_url),
                        //         'previewImageUrl' => $this->site_url['furl'].'/qrcode/?code='.urlencode($qr_url),
                        //     ],
                        //     [
                        //         'type' => 'text',
                        //         'text' => $back_qr_url,
                        //     ],
                        // ];

                        // $response_sendReplyMessage = $this->LineBot->sendReplyMessage($this->webhook_replyToken, $messages);
                        // Log::notice('response_sendReplyMessage:');
                        // Log::notice($response_sendReplyMessage);
                        // Log::notice('-------------------------------------------');
                        // if(is_array($response_sendReplyMessage) AND sizeof($response_sendReplyMessage) == 0){
                        //     Log::notice('回應[訂閱]成功');
                        // }else{
                        //     Log::notice('回應[訂閱]失敗');
                        // }
                        break;
                }
            }
        }
    }
    
    public function checkUser($line_user_id)
    {
        $mUser = model('User')->get(['line_user_id' => $line_user_id, 'status' => 1]);
        if($mUser){

        }else{
            $mUser = model('Userfree')->get(['line_user_id' => $line_user_id]);
            if(!$mUser){
                $params = [
                    'line_user_id' => $line_user_id,
                ];
                model('Userfree')::create($params);
                return 0;
            }else{
                return 1;
            }
        }
    }
    
    public function checkRichMenu($line_user_id)
    {
        //查詢目前綁的菜單
        // $response_getRichMenuIdOfUser = $this->LineBot->getRichMenuIdOfUser($line_user_id);
        // Log::notice('response_getRichMenuIdOfUser:');
        // Log::notice($response_getRichMenuIdOfUser);
        // Log::notice('-------------------------------------------');
        // if($response_getRichMenuIdOfUser AND isset($response_getRichMenuIdOfUser['richMenuId'])){
        //     $richMenuId = $response_getRichMenuIdOfUser['richMenuId'];
        // }else{
        //     $richMenuId = null;
        // }

        //查詢用戶
        $mUser = model('User')->get(['line_user_id' => $line_user_id, 'status' => 1]);
        if($mUser){
            // $mLinerichmenus = model('Linerichmenus')->get(['rich_menu_name' => 'richmenu_idm_1']);
            // if(!$mLinerichmenus){
            //     Log::notice('嚴重錯誤, DB菜單不完整');
            //     return 0;
            // }

            // if($mLinerichmenus->rich_menu_id != $richMenuId){
            //     //菜單重新指定
            //     $response_linkRichMenuToUser = $this->LineBot->linkRichMenuToUser($line_user_id, $mLinerichmenus->rich_menu_id);
            //     Log::notice('response_linkRichMenuToUser:');
            //     Log::notice($response_linkRichMenuToUser);
            //     Log::notice('-------------------------------------------');
            // }

            // if($mUser->rich_menu_id != $mLinerichmenus->id){
            //     //DB菜單有誤 更新
            //     model('User')->update(['rich_menu_id' => $mLinerichmenus->id],['id' => $mUser->id]);
            // }

            return 1;
        }else{
            $mUser = model('Userfree')->get(['line_user_id' => $line_user_id]);
            if(!$mUser){
                $params = [
                    'line_user_id' => $line_user_id,
                ];
                model('Userfree')::create($params);

                //用戶資料不在DB裡
                //清除菜單指定
                // $response_unlinkRichMenuFromUser = $this->LineBot->unlinkRichMenuFromUser($line_user_id);
                // Log::notice('response_unlinkRichMenuFromUser:');
                // Log::notice($response_unlinkRichMenuFromUser);
                // Log::notice('-------------------------------------------');
                return 0;
            }else{
                return 1;
            }
        }
    }


    ///以下都是初始化程式

    private function getRichMenu()
    {
        $result = true;
        $checkRichMenu = 0;
        $mLinerichmenus = model('Linerichmenus')->all();
        if($mLinerichmenus){
            $tallyid = [];
            // Log::notice('DB有菜單紀錄 取出並檢查');
            foreach($mLinerichmenus as $linerichmenus){
                if(isset($this->richMenu[$linerichmenus->rich_menu_name])){
                    if($this->richMenu[$linerichmenus->rich_menu_name] == '') {
                        $this->richMenu[$linerichmenus->rich_menu_name] = $linerichmenus->rich_menu_id;
                    }
                    $tallyid[] = $linerichmenus->id;
                    $checkRichMenu++;
                }
            }
            if($checkRichMenu != sizeof($this->richMenu)){
                Log::notice('DB菜單數量有誤 重新產生');
                //菜單數量有誤, 重新產生
                $result = $this->createRichMenu();
                if($result){
                    $newRichMenu = [];
                    foreach($this->richMenu as $rich_menu_name => $rich_menu_id){
                        $params = [
                            'rich_menu_id' => $rich_menu_id,
                            'rich_menu_name' => $rich_menu_name,
                        ];
                        $newLinerichmenus = model('Linerichmenus')::create($params);
                        $newRichMenu[$rich_menu_name] = $newLinerichmenus->id;
                    }
                    foreach($tallyid as $tally_k => $tally_id){
                        //替換新的菜單id 替換後刪除菜單
                        $tallyLinerichmenus = model('Linerichmenus')->get($tally_id);
                        if($tallyLinerichmenus){
                            $newRichMenuid = $newRichMenu[$tallyLinerichmenus->rich_menu_name];
                            $mUser = model('User')->where(['rich_menu_id' => $tally_id])->select();
                            if($mUser){
                                $user_ids = [];
                                foreach($mUser as $uv){
                                    $uv->rich_menu_id = $newRichMenuid;
                                    $uv->save();
                                    $user_ids[] = $uv->line_user_id;
                                }
                                if(sizeof($user_ids)>0){
                                    //菜單重新指定
                                    $response_linkRichMenuToMultipleUsers = $this->LineBot->linkRichMenuToMultipleUsers($user_ids, $this->richMenu[$tallyLinerichmenus->rich_menu_name]);
                                    Log::notice('response_linkRichMenuToMultipleUsers:');
                                    Log::notice($response_linkRichMenuToMultipleUsers);
                                    Log::notice('-------------------------------------------');
                                }
                            }
                            $tallyLinerichmenus->delete();
                        }

                    }
                }
            }
        }else{
            Log::notice('DB沒有菜單紀錄 檢查bot上的菜單');
            //DB沒有菜單紀錄 檢查bot上的菜單
            $response_getRichMenuList = $this->LineBot->getRichMenuList();
            if($response_getRichMenuList AND isset($response_getRichMenuList['richmenus'])){
                if(sizeof($response_getRichMenuList['richmenus'])>0){
                    foreach($response_getRichMenuList['richmenus'] as $richmenus){
                        if(isset($richmenus['name']) AND isset($richmenus['richMenuId'])){
                            if(isset($this->richMenu[$richmenus['name']])){
                                if($this->richMenu[$richmenus['name']] == '') $this->richMenu[$richmenus['name']] = $richmenus['richMenuId'];
                                $checkRichMenu++;
                            }
                        }
                    }
                    
                    if($checkRichMenu != sizeof($this->richMenu)){
                        Log::notice('bot菜單數量有誤, 重新產生');
                        //菜單數量有誤, 重新產生
                        $result = $this->createRichMenu();
                        if($result){
                            $newRichMenu = [];
                            foreach($this->richMenu as $rich_menu_name => $rich_menu_id){
                                $params = [
                                    'rich_menu_id' => $rich_menu_id,
                                    'rich_menu_name' => $rich_menu_name,
                                ];
                                $newLinerichmenus = model('Linerichmenus')::create($params);
                                $newRichMenu[$rich_menu_name] = $newLinerichmenus->id;
                            }
                        }
                    }

                    Log::notice('菜單寫入DB');
                    //菜單寫入DB
                    $newRichMenu = [];
                    foreach($this->richMenu as $rich_menu_name => $rich_menu_id){
                        $params = [
                            'rich_menu_id' => $rich_menu_id,
                            'rich_menu_name' => $rich_menu_name,
                        ];
                        $newLinerichmenus = model('Linerichmenus')::create($params);
                        $newRichMenu[$rich_menu_name] = $newLinerichmenus->id;
                    }

                    Log::notice('菜單寫入使用者');
                    //菜單寫入使用者 全部重新檢查狀態
                    $mUser = model('User')->all();
                    if($mUser){
                        $richmenu = [];
                        $richMenuToMultiple = [];
                        $mLinerichmenus = model('Linerichmenus')->all();
                        if(!$mLinerichmenus){
                            //DB菜單有誤, 重建菜單
                            $this->error('嚴重錯誤, 請聯絡我們 code:1000');
                        }
                        foreach($mLinerichmenus as $linerichmenu){
                            $richmenu[$linerichmenu->rich_menu_name] = [
                                'id' => $linerichmenu->id,
                                'rich_menu_id' => $linerichmenu->rich_menu_id
                            ];
                            $richMenuToMultiple[$linerichmenu->rich_menu_id] = [];
                        }

                        foreach($mUser as $v){
                            if($v->line_user_id){
                                $richmenutype = $richmenu['richmenu_idm_1'];
                                $richMenuToMultiple[$richmenutype['rich_menu_id']][] = $v->line_user_id;
                                $v->rich_menu_id = $richmenutype['id'];

                                // if($v->status == 0){
                                //     $richmenutype = $richmenu['richmenu_idm_4'];
                                //     $richMenuToMultiple[$richmenutype['rich_menu_id']][] = $v->line_user_id;
                                //     $v->rich_menu_id = $richmenutype['id'];

                                // }elseif($v->status == 1){
                                //     if($v->notify_client_id == NULL OR $v->notify_client_secret == NULL ){
                                //         $richmenutype = $richmenu['richmenu_idm_2'];
                                //         $richMenuToMultiple[$richmenutype['rich_menu_id']][] = $v->line_user_id;
                                //         $v->rich_menu_id = $richmenutype['id'];
                                //     }else{
                                //         $richmenutype = $richmenu['richmenu_idm_3'];
                                //         $richMenuToMultiple[$richmenutype['rich_menu_id']][] = $v->line_user_id;
                                //         $v->rich_menu_id = $richmenutype['id'];
                                //     }
                                // }else{
                                //     $richmenutype = $richmenu['richmenu_idm_1'];
                                //     $richMenuToMultiple[$richmenutype['rich_menu_id']][] = $v->line_user_id;
                                //     $v->rich_menu_id = $richmenutype['id'];
                                // }
                                $v->save();
                            }
                        }

                        Log::notice($richMenuToMultiple);
                        if(sizeof($richMenuToMultiple)>0){
                            foreach($richMenuToMultiple as $rich_menu_id => $line_user_id){
                                if(sizeof($line_user_id)>0){
                                    //菜單重新指定
                                    $response_linkRichMenuToMultipleUsers = $this->LineBot->linkRichMenuToMultipleUsers($line_user_id, $rich_menu_id);
                                    Log::notice('response_linkRichMenuToMultipleUsers:');
                                    Log::notice($response_linkRichMenuToMultipleUsers);
                                    Log::notice('-------------------------------------------');
                                }
                            }
                        }
                    }
                }else{
                    Log::notice('bot無菜單資料, 產生菜單');
                    //bot無菜單資料, 產生
                    $result = $this->createRichMenu();
                    if($result){
                        foreach($this->richMenu as $rich_menu_name => $rich_menu_id){
                            $params = [
                                'rich_menu_id' => $rich_menu_id,
                                'rich_menu_name' => $rich_menu_name,
                            ];
                            $newLinerichmenus = model('Linerichmenus')::create($params);
                        }
                    }
                }
            }else{
                Log::notice('查詢菜單結果異常');
                Log::notice('response_getRichMenuList:');
                Log::notice($response_getRichMenuList);
                Log::notice('-------------------------------------------');
            }
        }
        if(!$result) $this->error('嚴重錯誤, 請聯絡我們 code:1001');
    }
    
    private function createRichMenu()
    {
        Log::notice('開始建立 RichMenu');
        $rich_menu_object = [];
        $rich_menu_img = [];

        //第一頁
//         $rich_menu_object[] = [
//             "size" => [
//                 "width" => 800,
//                 "height" => 270
//             ],
//             "selected" => true,
//             "name" => "richmenu_idm_1",
//             "chatBarText" => $this->chatBarText,
//             "areas" => [
//                 0 => [ 
//                     "bounds" => [
//                         "x" => 0,
//                         "y" => 0,
//                         "width" => 800,
//                         "height" => 270
//                     ],
//                     "action" => [
//                         "type" => "message",
//                         "text" => "請直接輸入代碼在此聊天室
// 格式: 9453+代碼
// 範例: 9453AB123456"
//                     ]
//                 ]
//             ]
//         ];
        $rich_menu_object[] = [
            "size" => [
                "width" => 800,
                "height" => 270
            ],
            "selected" => true,
            "name" => "richmenu_idm_1",
            "chatBarText" => $this->chatBarText,
            "areas" => [
                0 => [ 
                    "bounds" => [
                        "x" => 0,
                        "y" => 0,
                        "width" => 266,
                        "height" => 270
                    ],
                    "action" => [
                        "type" => "uri",
                        "uri" => "https://liff.line.me/".$this->site['liffid']."/pred"
                    ]
                ],
                1 => [ 
                    "bounds" => [
                        "x" => 266,
                        "y" => 0,
                        "width" => 267,
                        "height" => 270
                    ],
                    "action" => [
                        "type" => "postback",
                        "data" => "action=getActivity",
                        "displayText" => "最新活動"
                    ]
                ],
                2 => [ 
                    "bounds" => [
                        "x" => 533,
                        "y" => 0,
                        "width" => 267,
                        "height" => 270
                    ],
                    "action" => [
                        "type" => "postback",
                        "data" => "action=getService",
                        "displayText" => "聯繫客服"
                    ]
                ]
            ]
        ];
        $rich_menu_img[] = 'richmenu_idm_1';

        //第二頁
        $rich_menu_object[] = [
            "size" => [
                "width" => 800,
                "height" => 540
            ],
            "selected" => true,
            "name" => "richmenu_idm_2",
            "chatBarText" => $this->chatBarText,
            "areas" => [
                0 => [ //左上
                    "bounds" => [
                        "x" => 0,
                        "y" => 0,
                        "width" => 266,
                        "height" => 270
                    ],
                    "action" => [
                        "type" => "message",
                        "text" => "左上"
                    ]
                ],
                1 => [ //中上
                    "bounds" => [
                        "x" => 266,
                        "y" => 0,
                        "width" => 267,
                        "height" => 270
                    ],
                    "action" => [
                        "type" => "message",
                        "text" => "中上"
                    ]
                ],
                2 => [ //右上
                    "bounds" => [
                        "x" => 533,
                        "y" => 0,
                        "width" => 267,
                        "height" => 270
                    ],
                    "action" => [
                        "type" => "message",
                        "text" => "右上"
                    ]
                ],
                3 => [ //左下
                    "bounds" => [
                        "x" => 0,
                        "y" => 270,
                        "width" => 266,
                        "height" => 270
                    ],
                    "action" => [
                        "type" => "message",
                        "text" => "左下"
                    ]
                ],
                4 => [ //中下
                    "bounds" => [
                        "x" => 266,
                        "y" => 270,
                        "width" => 267,
                        "height" => 270
                    ],
                    "action" => [
                        "type" => "message",
                        "text" => "中下"
                    ]
                ],
                5 => [ //右下
                    "bounds" => [
                        "x" => 533,
                        "y" => 270,
                        "width" => 267,
                        "height" => 270
                    ],
                    "action" => [
                        "type" => "message",
                        "text" => "右下"
                    ]
                ]
            ]
        ];
        $rich_menu_img[] = 'richmenu_idm_2'; 

        $rich_menu_id = [];
        $result = true;
        
        Log::notice('取得bot現有菜單');
        //取得bot現有菜單
        $response_getRichMenuList = $this->LineBot->getRichMenuList();
        if($response_getRichMenuList AND isset($response_getRichMenuList['richmenus'])){
            if(sizeof($response_getRichMenuList['richmenus'])>0){
                foreach($response_getRichMenuList['richmenus'] as $richmenus){
                    if(isset($richmenus['name']) AND isset($richmenus['richMenuId'])){
                        if(isset($this->richMenu[$richmenus['name']])){
                            //刪除bot的舊菜單
                            Log::notice('刪除bot的舊菜單 richMenuId:'.$richmenus['richMenuId']);
                            $response_deleteRichMenu = $this->LineBot->deleteRichMenu($richmenus['richMenuId']);
                            Log::notice('response_deleteRichMenu:');
                            Log::notice($response_deleteRichMenu);
                            Log::notice('-------------------------------------------');
                        }
                    }
                }
            }
        }

        foreach($rich_menu_object as $key=>$object){
            Log::notice('第'.($key+1).'筆 開始');
            Log::notice($object);
            Log::notice('-------------------------------------------');
            $response_createRichMenu = $this->LineBot->createRichMenu($object);
            Log::notice('response_createRichMenu:');
            Log::notice($response_createRichMenu);
            Log::notice('-------------------------------------------');
            if($response_createRichMenu AND isset($response_createRichMenu['richMenuId'])){
                $richMenuId = $response_createRichMenu['richMenuId'];
                Log::notice('richMenuId: '.$richMenuId);
                Log::notice('img:'.$this->site_url['furl'].'/assets/img/'.$rich_menu_img[$key].'.png');
                $response_uploadRichMenuImage = $this->LineBot->uploadRichMenuImage($richMenuId, $this->site_url['furl'].'/assets/img/'.$rich_menu_img[$key].'.png');
                Log::notice('response_uploadRichMenuImage:');
                Log::notice($response_uploadRichMenuImage);
                Log::notice('-------------------------------------------');
                if(is_array($response_uploadRichMenuImage) AND sizeof($response_uploadRichMenuImage) == 0){
                    Log::notice('完整建立成功');
                    $rich_menu_id[] = $richMenuId;
                    if($key == 0){
                        Log::notice('第一個菜單, 設為預設');
                        $response_setDefaultRichMenu = $this->LineBot->setDefaultRichMenu($richMenuId);
                        Log::notice('response_setDefaultRichMenu:');
                        Log::notice($response_setDefaultRichMenu);
                        Log::notice('-------------------------------------------');
                    }
                }else{
                    Log::notice('上傳圖片失敗, 刪除'.$richMenuId);
                    $response_deleteRichMenu = $this->LineBot->deleteRichMenu($richMenuId);
                    Log::notice('response_deleteRichMenu:');
                    Log::notice($response_deleteRichMenu);
                    Log::notice('-------------------------------------------');
                    Log::notice('發生錯誤 程式中止');
                    $result = false;
                    break;
                }
            }else{
                Log::notice('發生錯誤 程式中止');
                $result = false;
                break;
            }
            Log::notice('第'.($key+1).'筆 結束');
        }

        if($result){
            $i = 0;
            foreach($this->richMenu as $key=>$val){
                $this->richMenu[$key] = $rich_menu_id[$i];
                $i++;
            }
        }

        Log::notice('建立完畢');
        return $result;
    }

}
