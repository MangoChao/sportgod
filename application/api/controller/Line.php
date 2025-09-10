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
                    if($this->webhook_userId) $this->checkUser($this->webhook_userId);

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
        $isSys = true;
        if ($isSys) {
            switch ($message_lower) {
                default:
                    $this->sendReplyMessage($message_lower);
                    break;
                case "#nuid":
                    break;
                case "#uid":
                    break;
            }
        } else {

        }
    }
    
    private function webhook_postback_event()
    {
        if($this->webhook_postback_data){
            $postback_data = [];
            parse_str($this->webhook_postback_data, $postback_data);
            Log::notice($postback_data);
            // if(isset($postback_data['action'])){
            //     switch($postback_data['action']){
            //     }
            // }
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
        if (is_array($response_sendReplyMessage) and sizeof($response_sendReplyMessage) == 0) {
            Log::notice('回應成功');
        } else {
            Log::notice('回應失敗');
        }
    }


    private function sendReplyMessageCus($messages_obj)
    {
        $response_sendReplyMessage = $this->LineBot->sendReplyMessage($this->webhook_replyToken, $messages_obj);
        Log::notice('response_sendReplyMessage:');
        Log::notice($response_sendReplyMessage);
        Log::notice('-------------------------------------------');
        if (is_array($response_sendReplyMessage) and sizeof($response_sendReplyMessage) == 0) {
            Log::notice('回應成功');
        } else {
            Log::notice('回應失敗');
        }
    }

}
