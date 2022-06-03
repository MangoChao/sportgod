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
    protected $gameurl = "https://ag.bl568.net";

    protected function configure(){
        $this->setName('Geteventhistory')->setDescription("抓取比分");
    }

    protected function execute(Input $input, Output $output){
        Log::init(['type' => 'File', 'log_name' => 'cron_Geteventhistory']);
        $this->site = Config::get("site");
        if(date('H:i') != "00:00"){
            $this->Geteventhistory();
        }
    }
    
    public function Geteventhistory()
    {
        try {
            $func_name = 'Geteventhistory';
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
                    Log::notice("[command][Cron][".$func_name."] 開始取得比分 - 類別:".$mEventcategory->title);
                    $content = $this->get_content($this->gameurl.'/history_events_show_list.php?game_category='.$mEventcategory->game_category, $cookie);
                    // $content = $this->get_content($this->gameurl.'/history_events_show_list.php?game_category=7', $cookie);
                    // Log::notice($content);
                    if(strpos($content, '目前無任何賽事') === false){
                        $content_arr = explode(PHP_EOL,$content);
                        $trstart = false; //行開始
                        $tdstart = false; //列開始
                        $tdrow = 0;
                        $htime = false; //帳務日期
                        $stime = false; //時間
                        $gscore = false;//客場比分
                        $mscore = false;//主場比分
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
                                        'gscore' => '',
                                        'mscore' => '',
                                        'starttime' => '',
                                        'master' => '',
                                        'guests' => '',
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

                                if($content_line != '' AND $trstart){
                                    if($htime){ //帳務日期
                                        // Log::notice($content_line);
                                        if(strpos($content_line, '</td>') !== false){ 
                                            $htime = false;
                                            $stime = true;
                                        }
                                        continue;
                                    }
                                    if($stime){ //時間
                                        // Log::notice($content_line);
                                        $start_str = '<td>';
                                        $end_str = '</td>';
                                        $eventdata['starttime'] = $this->getValue($content_line, $start_str, $end_str);
                                        $stime = false;
                                        continue;
                                    }
                                    if($gscore){ //客場比分
                                        $start_str = '<div>';
                                        $end_str = '</div>';
                                        $eventdata['gscore'] = $this->getValue($content_line, $start_str, $end_str);
                                        $gscore = false;
                                        $mscore = true; //主場比分
                                        continue;
                                    }
                                    if($mscore){ //主場比分
                                        $start_str = '<div>';
                                        $end_str = '</div>';
                                        $eventdata['mscore'] = $this->getValue($content_line, $start_str, $end_str);
                                        $mscore = false;
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
                                }
                                
                                if($trstart = true){
                                    if($content_line == '<tr class="event-tr close-status">'){
                                        $htime = true; //帳務日期
                                    }
                                    if($content_line == '<td class="rank_score">'){
                                        $gscore = true; //客場比分
                                    }
                                    if($content_line == '<td class="ranks-td">'){
                                        $gteam = true; //客場隊伍
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
            }while($co < $comax);
            // }while($next && $co < $comax);

            Log::notice("[command][Cron][".$func_name."] 完整結束 ".date('Y-m-d H:i:s',time()));
        } catch (ValidateException $e) {
            Log::notice("[command][Cron][".$func_name."] ValidateException :".$e->getMessage());
        } catch (PDOException $e) {
            Log::notice("[command][Cron][".$func_name."] PDOException :".$e->getMessage());
        } catch (Exception $e) {
            Log::notice("[command][Cron][".$func_name."] Exception :".$e->getMessage());
            Log::notice($content);
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
            $modelPred = new Pred;
            $mEvent = $modelEvent->where("status = 0 AND event_category_id = '".$data['event_category_id']."' AND master = '".$data['master']."' AND guests = '".$data['guests']."' AND starttime = '".$data['starttime']."' ")->find();
            if($mEvent){
                $mEvent->master_score = $data['mscore'];
                $mEvent->guests_score = $data['gscore'];
                $mEvent->status = 1;
                $mEvent->save();
                $mPred = $modelPred->where("event_id = '".$mEvent->id."' AND comply = 0 ")->select();
                if($mPred){
                    foreach($mPred as $v){
                        $v->master_score = $data['mscore'];
                        $v->guests_score = $data['gscore'];
                        if($v->pred_type == 1){
                            if($v->master_refund != null){
                                // Log::notice($v->master_refund);
                                $winscore = $mEvent->master_score - $mEvent->guests_score;
                                $refund = $v->master_refund;
                                $minus = false;
                                $l = strpos($refund, '-');
                                if($l === false){
                                    //+
                                    $l = strpos($refund, '+');
                                    if($l === false){
                                        $l = mb_strlen($refund);
                                    }
                                }else{
                                    //-
                                    $minus = true;
                                }
                                $refund = substr($refund, 0, $l);
                                if(!is_numeric($refund)){
                                    Log::notice('refund非數字');
                                    Log::notice($refund);
                                    continue;
                                }
                                if($minus){
                                    $refund = $refund+1;
                                }
                                if($winscore < $refund AND $v->winteam == 0){
                                    $v->comply = 1;
                                }elseif($winscore >= $refund AND $v->winteam == 1){
                                    $v->comply = 1;
                                }else{
                                    $v->comply = 2;
                                }

                            }elseif($v->guests_refund != null){
                                // Log::notice($v->guests_refund);
                                $winscore = $mEvent->guests_score - $mEvent->master_score;
                                $refund = $v->guests_refund;
                                $minus = false;
                                $l = strpos($refund, '-');
                                if($l === false){
                                    //+
                                    $l = strpos($refund, '+');
                                    if($l === false){
                                        $l = mb_strlen($refund);
                                    }
                                }else{
                                    //-
                                    $minus = true;
                                }
                                $refund = substr($refund, 0, $l);
                                if(!is_numeric($refund)){
                                    Log::notice('refund非數字');
                                    Log::notice($refund);
                                    continue;
                                }
                                if($minus){
                                    $refund = $refund+1;
                                }
                                if($winscore < $refund AND $v->winteam == 1){
                                    $v->comply = 1;
                                }elseif($winscore >= $refund AND $v->winteam == 0){
                                    $v->comply = 1;
                                }else{
                                    $v->comply = 2;
                                }
                            }else{
                                Log::notice('讓分有誤, pred_id:'.$v->id);
                                continue;
                            }
                        }else{
                            $totalscore = $mEvent->master_score + $mEvent->guests_score;
                            $bigscore = $v->bigscore;
                            $minus = false;
                            $l = strpos($bigscore, '-');
                            if($l === false){
                                //+
                                $l = strpos($bigscore, '+');
                                if($l === false){
                                    $l = mb_strlen($bigscore);
                                }
                            }else{
                                //-
                                $minus = true;
                            }
                            $bigscore = substr($bigscore, 0, $l);
                            if($minus){
                                $bigscore = $bigscore+1;
                            }

                            if($totalscore < $bigscore AND $v->bigsmall == 0){
                                $v->comply = 1;
                            }elseif($totalscore >= $bigscore AND $v->bigsmall == 1){
                                $v->comply = 1;
                            }else{
                                $v->comply = 2;
                            }
                        }
                        $v->save();
                    }
                }
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