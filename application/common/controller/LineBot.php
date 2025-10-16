<?php

namespace app\common\controller;

use think\Log;
use fast\Http;
use think\Exception;
use think\Config;

class LineBot
{
    private $channel_access_token = null;
    private $http_options = null;
    // protected $channel_access_token = null;
    
    public function __construct($channel_access_token)
    {
        $this->channel_access_token = $channel_access_token;
        $this->http_options = [
            CURLOPT_HTTPHEADER  => [
                'Authorization:Bearer '.$this->channel_access_token,
                'Content-Type:application/json'
            ]
        ];
        // Log::notice($this->channel_access_token);
    }

    public function getRichMenuList(){
        $url = 'https://api.line.me/v2/bot/richmenu/list';
        $http_options = [
            CURLOPT_HTTPHEADER  => [
                'Authorization:Bearer '.$this->channel_access_token
            ]
        ];
        $params = [];
        $response = Http::get($url, $params, $http_options);
        return $this->chResponse($response);
    }
    
    public function createRichMenu($rich_menu_object){
        $url = 'https://api.line.me/v2/bot/richmenu';
        $params = $rich_menu_object;
        $response = Http::post($url, json_encode($params), $this->http_options);
        return $this->chResponse($response);
    }

    public function deleteRichMenu($rich_menu_id){
        $url = 'https://api.line.me/v2/bot/richmenu/'.$rich_menu_id;
        $params = [];
        $response = Http::delete($url, json_encode($params), $this->http_options);
        return $this->chResponse($response);
    }
    
    public function uploadRichMenuImage($rich_menu_id, $img_url){
        $url = 'https://api-data.line.me/v2/bot/richmenu/'.$rich_menu_id.'/content';
        
        $http_options = [
            CURLOPT_HTTPHEADER  => [
                'Authorization:Bearer '.$this->channel_access_token,
                'Content-Type:image/png'
            ],
        ];

        $params = file_get_contents($img_url);
        $response = Http::post($url, $params, $http_options);
        return $this->chResponse($response);
    }

    public function setDefaultRichMenu($rich_menu_id){
        $url = 'https://api.line.me/v2/bot/user/all/richmenu/'.$rich_menu_id;
        $params = [];
        $response = Http::post($url, json_encode($params), $this->http_options);
        return $this->chResponse($response);
    }
    
    public function getRichMenuIdOfUser($user_id){
        $url = 'https://api.line.me/v2/bot/user/'.$user_id.'/richmenu';
        $params = [];
        $response = Http::get($url, $params, $this->http_options);
        return $this->chResponse($response);
    }
    
    public function linkRichMenuToUser($user_id, $rich_menu_id){
        $url = 'https://api.line.me/v2/bot/user/'.$user_id.'/richmenu/'.$rich_menu_id;
        $params = [];
        $response = Http::post($url, json_encode($params), $this->http_options);
        return $this->chResponse($response);
    }
    
    public function linkRichMenuToMultipleUsers($user_ids, $rich_menu_id){
        $url = 'https://api.line.me/v2/bot/richmenu/bulk/link';
        $params = [
            'richMenuId' => $rich_menu_id,
            'userIds' => $user_ids,
        ];
        $response = Http::post($url, json_encode($params), $this->http_options);
        return $this->chResponse($response);
    }
    
    // public function unlinkRichMenuFromUser($user_id){
    //     $url = 'https://api.line.me/v2/bot/user/'.$user_id.'/richmenu';
    //     $params = [];
    //     $response = Http::delete($url, json_encode($params), $this->http_options);
    //     return $this->chResponse($response);
    // }
    
    public function sendReplyMessage($replyToken, $messages){
        $url = 'https://api.line.me/v2/bot/message/reply';
        $params = [
            'replyToken' => $replyToken,
            'messages' => $messages,
        ];
        $response = Http::post($url, json_encode($params), $this->http_options);
        return $this->chResponse($response);
    }


    private function chResponse($response)
    {
        $decoded = null;
        if ($response !== '') {
            $decoded = json_decode($response, true);
        }

        // 如果無法解析 JSON，記錄原始內容
        if ($decoded === null) {
            Log::notice('[LINEBOT] 非預期回應：' . $response);
            return [
                'success' => false,
                'error_type' => 'invalid_json',
                'raw_response' => $response
            ];
        }

        // 🔹 LINE 成功的情況通常會有 sentMessages / richMenuId / richmenus 等字段
        $isSuccess = (
            isset($decoded['sentMessages']) ||
            isset($decoded['richMenuId']) ||
            isset($decoded['richmenus']) ||
            empty($decoded) // 某些 DELETE 請求是空陣列回傳
        );

        if ($isSuccess) {
            return [
                'success' => true,
                'data' => $decoded
            ];
        }

        // 🔹 失敗情況
        // 常見格式：
        // {
        //   "message": "The request body has 1 error(s)",
        //   "details": [
        //     {
        //       "message": "May not be empty",
        //       "property": "messages[0].text"
        //     }
        //   ]
        // }
        $errorMessage = $decoded['message'] ?? 'Unknown error';
        $details = isset($decoded['details']) ? json_encode($decoded['details'], JSON_UNESCAPED_UNICODE) : null;
        $statusCode = $decoded['statusCode'] ?? null;
        $error = $decoded['error'] ?? null;

        $errorLog = "[LINEBOT] 請求失敗：{$errorMessage}";
        if ($statusCode) $errorLog .= " (HTTP {$statusCode})";
        if ($error) $errorLog .= " [{$error}]";
        if ($details) $errorLog .= " 詳細：{$details}";
        Log::notice($errorLog);

        return [
            'success' => false,
            'error_message' => $errorMessage,
            'details' => $decoded['details'] ?? [],
            'statusCode' => $statusCode,
            'error' => $error,
            'raw' => $decoded
        ];
    }

    
}
