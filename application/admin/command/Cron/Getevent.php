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
use app\common\model\Eventparam;
use app\common\model\Eventcategory;

class Getevent extends Command
{
    protected $taskName = '抓取賽事';
    protected $site = [];
    protected $gameurl = "https://ag.bl568.net";

    protected function configure(){
        $this->setName('Getevent')->setDescription("抓取賽事");
    }

    protected function execute(Input $input, Output $output){
        Log::init(['type' => 'File', 'log_name' => 'cron_Getevent']);
        $this->site = Config::get("site");
        if(date('i') != "30" AND date('i') != "00"){
            $this->Getevent();
        }
    }
    
    public function Getevent()
    {
        try {
            $func_name = 'Getevent';
            Log::notice("[command][Cron][".$func_name."] 開始執行 ".date('Y-m-d H:i:s',time()));
            $modelEventcategory = new Eventcategory;

            $url = $this->gameurl."/login.php";
            $post = [
                'luserid' => $this->site['luserid'],
                'lpassword' => $this->site['lpassword'],
                'paction' => 'login-processing',
                'remember' => 1
            ];
            $cookie = './cookie.txt';

            Log::notice("[command][Cron][".$func_name."] 模擬登錄");
            //模擬登錄
            $this->login_post($url, $cookie, $post);

            $co = 0;
            $comax = $modelEventcategory->where("status = 1")->count();
            $next = false;
            do{
                //爬賽事
                $mEventcategory = $modelEventcategory->where("status = 1")->order(['lastcron'=>'ASC','id'=>'ASC'])->find();
                if($mEventcategory){
                    $mEventcategory->lastcron = time();
                    $mEventcategory->save();
                    Log::notice("[command][Cron][".$func_name."] 開始取得賽事 - 類別:".$mEventcategory->title);
                    $content = $this->get_content($this->gameurl.'/'.$mEventcategory->url, $cookie);
                    if(strpos($content, '目前無任何賽事') === false){
                        $content_arr = explode(PHP_EOL,$content);
                        $trstart = false; //行開始
                        $tdstart = false; //列開始
                        $tdrow = 0;
                        $stime = false; //時間
                        $gteam = false; //客
                        $mteam = false; //主
                        $grefund  = false; //客讓
                        $mrefund  = false; //主讓
                        $bigscore  = false; //大小
                        $eventdata = [];
                        if(sizeof($content_arr)>0){
                            // Log::notice($content_arr);
                            foreach($content_arr as $content_line){
                                $content_line = trim($content_line);
                                
                                if(strpos($content_line, '<tr class="event-tr') !== false){ //偵測行開始
                                    $trstart = true; 
                                    $eventdata = [
                                        'event_category_id' => $mEventcategory->id,
                                        'starttime' => '',
                                        'master' => '',
                                        'master_refund' => '',
                                        'guests' => '',
                                        'guests_refund' => '',
                                        'bigscore' => '',
                                    ];
                                }
                                if(strpos($content_line, '</tr>') !== false){ //偵測行結束
                                    $trstart = false; 
                                    $tdrow = 0;
                                    if(sizeof($eventdata) > 0){
                                        $this->upEvent($eventdata);
                                    }
                                    $eventdata = [];
                                }
                                if(strpos($content_line, '<td') !== false){ //偵測列開始
                                    $tdstart = true; 
                                    $tdrow++;
                                }elseif(strpos($content_line, '</td>') !== false){ //偵測列結束
                                    $tdstart = false; 
                                }

                                if($content_line != '' AND $trstart AND $tdstart){
                                    if($stime){ //時間
                                        $start_str = '<td>';
                                        $end_str = '</td>';
                                        $eventdata['starttime'] = $this->getValue($content_line, $start_str, $end_str);
                                        $stime = false;
                                        continue;
                                    }
                                    if($gteam){ //客場
                                        $start_str = '">';
                                        $end_str = '</div>';
                                        $eventdata['guests'] = $this->getValue($content_line, $start_str, $end_str);
                                        $gteam = false;
                                        $mteam = true; //主場
                                        continue;
                                    }
                                    if($mteam){ //主場
                                        $start_str = '">';
                                        $end_str = '<font class="master';
                                        $eventdata['master'] = $this->getValue($content_line, $start_str, $end_str);
                                        $mteam = false;
                                        continue;
                                    }
                                    if($grefund){ //客場讓分
                                        $start_str = '<div>';
                                        $end_str = '</div>';
                                        $eventdata['guests_refund'] = $this->getValue($content_line, $start_str, $end_str);
                                        $grefund = false;
                                        $mrefund = true; //主場讓分
                                        continue;
                                    }
                                    if($mrefund){ //主場讓分
                                        $start_str = '<div>';
                                        $end_str = '</div>';
                                        $eventdata['master_refund'] = $this->getValue($content_line, $start_str, $end_str);
                                        $mrefund = false;
                                        continue;
                                    }
                                    if($bigscore){ //大小
                                        $start_str = '<div>';
                                        $end_str = '</div>';
                                        $eventdata['bigscore'] = $this->getValue($content_line, $start_str, $end_str);
                                        $bigscore = false;
                                        continue;
                                    }
                                }
                                
                                if($trstart = true){
                                    if($content_line == '<tr class="event-tr ">'){
                                        $stime = true; //比賽時間
                                    }
                                    if($content_line == '<td class="ranks-td">'){
                                        $gteam = true; //客場隊伍
                                    }
                                    if($content_line == '<div class="hds-div">' AND $tdrow == 3){ //第三列=全場讓分
                                        $grefund   = true; //客場讓分
                                    }
                                    if($content_line == '<div class="hds-div">' AND $tdrow == 4){ //第四列=全場大小
                                        $bigscore   = true; //客場大小
                                    }
                                }
                            }
                        }
                        $next = false;
                        Log::notice("[command][Cron][".$func_name."] 已同步賽事");
                    }else{
                        $next = true;
                        Log::notice("[command][Cron][".$func_name."] 無賽事");
                    }
                    Log::notice("[command][Cron][".$func_name."] 結束取得賽事");
                }else{
                    $next = false;
                    Log::notice("[command][Cron][".$func_name."] DB無菜單");
                }
                $co++;
            }while($next && $co < $comax);

            Log::notice("[command][Cron][".$func_name."] 完整結束 ".date('Y-m-d H:i:s',time()));
        } catch (ValidateException $e) {
            Log::notice("[command][Cron][".$func_name."] ValidateException :".$e->getMessage());
        } catch (PDOException $e) {
            Log::notice("[command][Cron][".$func_name."] PDOException :".$e->getMessage());
        } catch (Exception $e) {
            Log::notice("[command][Cron][".$func_name."] Exception :".$e->getMessage());
        }
    }

    private function upEvent($data) {
        // Log::notice($data);
        if(isset($data['starttime']) AND !empty($data['starttime'])){
            $data['starttime'] = strtotime($data['starttime']);
        }else{
            $data['starttime'] = null;
        }
        if($data['starttime'] !== null){
            $modelEvent = new Event;
            $modelEventparam = new Eventparam;
            $mEvent = $modelEvent->where("event_category_id = '".$data['event_category_id']."' AND master = '".$data['master']."' AND guests = '".$data['guests']."' AND starttime = '".$data['starttime']."' ")->find();
            if($mEvent){
                if(($mEvent->master_refund != $data['master_refund'] AND $data['master_refund'] != '0') OR 
                ($mEvent->guests_refund != $data['guests_refund'] AND $data['master_refund'] != '0') OR 
                ($mEvent->bigscore != $data['bigscore'] AND $data['bigscore'] != '0')){
                    // Log::notice($mEvent->id);
                    // Log::notice($data['master_refund']);
                    // Log::notice($data['guests_refund']);
                    // Log::notice($data['bigscore']);
                    // Log::notice($mEvent->master_refund);
                    // Log::notice($mEvent->guests_refund);
                    // Log::notice($mEvent->bigscore);

                    $master_refund = $mEvent->master_refund;
                    $guests_refund = $mEvent->guests_refund;
                    $bigscore = $mEvent->bigscore;
                    
                    if($data['master_refund'] != '0' AND $data['guests_refund'] != '0'){
                        $mEvent->master_refund = $data['master_refund'];
                        $mEvent->guests_refund = $data['guests_refund'];
                    }

                    if($data['bigscore'] != '0'){
                        $mEvent->bigscore = $data['bigscore'];
                    }

                    $mEvent->save();

                    $params = [
                        'event_id' => $mEvent->id,
                        'master_refund' => $master_refund,
                        'guests_refund' => $guests_refund,
                        'bigscore' => $bigscore,
                    ];
                    $modelEventparam::create($params);
                }
                
            }else{
                $params = [
                    'event_category_id' => $data['event_category_id'],
                    'starttime' => $data['starttime'],
                    'master' => $data['master'],
                    'master_refund' => $data['master_refund'],
                    'guests' => $data['guests'],
                    'guests_refund' => $data['guests_refund'],
                    'bigscore' => $data['bigscore'],
                ];
                $modelEvent::create($params);
            }
        }
    }

    private function getValue($content, $start_str, $end_str) {
        $content = str_replace('&nbsp;','',str_replace('<br>',' ',str_replace('<br/>',' ',trim($content))));
        $start_index = strpos($content, $start_str);
        $end_index = strpos($content, $end_str);
        if($end_index !== false AND $start_index !== false){
            $start_index = $start_index + mb_strlen($start_str);
            return substr($content, $start_index, $end_index-$start_index);
        }else{
            return '';
        }
    }

    private function login_post($url, $cookie, $post) {
        $curl = curl_init();//初始化curl模塊
        curl_setopt($curl, CURLOPT_URL, $url);//登錄提交的地址
        curl_setopt($curl, CURLOPT_HEADER, 0);//是否显示头信息
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0);//是否自動顯示返回的信息
        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie); //設置Cookie信息保存在指定的文件中
        curl_setopt($curl, CURLOPT_POST, 1);//post方式提交
        
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));//要提交的信息
        curl_exec($curl);//執行cURL
        curl_close($curl);//關閉cURL資源，並且釋放系統資源
    }
        
    private function get_content($url, $cookie) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);//是否显示头信息
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie); //讀取cookie
        
        $rs = curl_exec($ch); //執行cURL抓取頁面內容
        curl_close($ch);
        return $rs;
    }


}