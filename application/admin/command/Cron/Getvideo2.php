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
use fast\Random;

use app\common\model\Video;
use app\common\model\Videocat;
use app\common\model\Videotype;
use app\common\model\Translate;

class Getvideo2 extends Command
{
    protected $taskName = '抓取直播';
    protected $site = [];
    // protected $gameurl = "https://www.ballbar.cc/api/list_html.php";
    protected $gameurl = "https://data.sportlive.cc/data/event.php";
    // protected $gameurl = "https://data.sportlive.cc/data/api.php";
    
    protected $vDate = '';
    protected $videocatlist = [];
    protected $videotypelist = [];
    protected $videoData = false;
    protected $mBannerCode = '';

    protected function configure(){
        $this->setName('Getvideo2')->setDescription("抓取直播");
    }

    protected function execute(Input $input, Output $output){
        Log::init(['type' => 'File', 'log_name' => 'cron_Getvideo2']);
        $this->site = Config::get("site");
        $this->Getvideo();
    }
    
    public function Getvideo()
    {
        try {
            $func_name = 'Getvideo';
            Log::notice("[command][Cron][".$func_name."] 開始執行 ".date('Y-m-d H:i:s',time()));

            $modelVideo = new Video;
            $modelVideocat = new Videocat;
            $modelVideotype = new Videotype;
            
            // date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." +2 hours"))
            $mBanner = $modelVideo->where('isbanner = 1')->find();
            if($mBanner){
                $this->mBannerCode = md5($mBanner->vlink);
            }
            $this->videoData = [];
            $mV = $modelVideo->all();
            if($mV){
                foreach($mV as $v){
                    if($v->starttime < time()){
                        $v->status = 0;
                        $v->save();
                    }
                    $this->videoData[md5($v->vlink)] = [
                        'title' => $v->title,
                        'starttime' => $v->starttime,
                        'status' => $v->status,
                    ];
                }
            }

            $mVideocat = $modelVideocat->all();
            if($mVideocat){
                foreach($mVideocat as $v){
                    $this->videocatlist[$v->title2] = [
                        'id' => $v->id,
                        'title' => $v->title,
                        'title2' => $v->title2,
                        'status' => $v->status,
                    ];
                }
            }
            $mVideotype = $modelVideotype->all();
            if($mVideotype){
                foreach($mVideotype as $v){
                    $this->videotypelist[$v->title2] = [
                        'id' => $v->id,
                        'title' => $v->title,
                        'title2' => $v->title2,
                        'status' => $v->status,
                    ];
                }
            }

            $content = $this->get_content($this->gameurl);
            Log::notice($content);
            $content_arr = explode(PHP_EOL,$content);
            Log::notice($content_arr);
            exit;

            $trstart = false; //行開始
            $tdstart = false; //列開始
            $uData = [];

            $getDate = false; 

            $stime = false; 
            $vcat = false; 
            $vtype = false; 
            $vtitle = false; 
            $vhd = false; 
            $vlag = false; 
            $vlink = false; 
            
            $start = false; 
            $chth = false; 
            $tharr = [];
            $vtag = [];
            $throw = 0;

            if(sizeof($content_arr)>0){
                // Log::notice($content_arr);
                foreach($content_arr as $content_line){
                    $content_line = trim($content_line);
                    
                    if(strpos($content_line, '</table>') !== false){ //偵測結束
                        break;
                    }

                    if($chth){
                        if(strpos($content_line, '</tr>') !== false){ 
                            $chth = false;
                            $start = true;
                        }else{
                            $start_str = '<th>';
                            $end_str = '</th>';
                            $tharr[] = $this->getValue($content_line, $start_str, $end_str);
                            continue;
                        }
                    }

                    if(strpos($content_line, '<th>开始时间</th>') !== false){ //偵測開始
                        $chth = true;
                        $tharr[] = '开始时间';
                    }

                    if($start){
                        if(strpos($content_line, '<tr>') !== false){ //偵測行開始
                            // $trstart = true; 
                            $uData = [
                                'vcat' => 0,
                                'vtype' => 0,
                                'stime' => '',
                                'vtitle' => '',
                                'vlink' => '',
                                'vcattitle' => '',
                                'vtypetitle' => '',
                            ];
                            $vtag = [
                                '开始时间' => 'stime',
                                '类别' => 'vcattitle',
                                '项目' => 'vtypetitle',
                                '赛事' => 'vtitle',
                                '参数ID' => 'vlink',
                            ];
                            $throw = 0;

                            $start_str = '<th>';
                            $end_str = '</th>';
                            while(strpos($content_line, '<th>') !== false){
                                if(isset($tharr[$throw])){
                                    $tagkey = $tharr[$throw];
                                    if(isset($vtag[$tagkey])){
                                        $uDataKey = $vtag[$tagkey];
                                        $uData[$uDataKey] = $this->getValue($content_line, $start_str, $end_str);
                                    }
                                    $content_line = $this->cutValue($content_line, $start_str, $end_str);
                                    $throw++;
                                }
                            }
                            $this->upData($uData); //寫入資料
                            continue;
                        }
                    }
                }
            }
            $modelVideo->where('status','=',0)->delete();

            Log::notice("[command][Cron][".$func_name."] 完整結束 ".date('Y-m-d H:i:s',time()));
        } catch (ValidateException $e) {
            Log::notice("[command][Cron][".$func_name."] ValidateException :".$e->getMessage());
        } catch (PDOException $e) {
            Log::notice("[command][Cron][".$func_name."] PDOException :".$e->getMessage());
        } catch (Exception $e) {
            Log::notice("[command][Cron][".$func_name."] Exception :".$e->getMessage());
        }
    }

    private function upData($data) {

        // Log::notice($data);
        
        // $uData = [
        //     'vcat' => '',
        //     'vtype' => '',
        //     'stime' => '',
        //     'vtitle' => '',
        //     'vlink' => '',
        //     'vcattitle' => '',
        //     'vtypetitle' => '',
        // ];
        
        if(isset($data['stime']) AND !empty($data['stime'])){
            $data['stime'] = strtotime($data['stime']);
        }else{
            Log::notice("[command][Cron][upData] 無時間: ".json_encode($data, JSON_UNESCAPED_UNICODE));
            return false;
        }
        
        $data['vtitle'] = trim($data['vtitle']);
        $data['vcattitle'] = trim($data['vcattitle']);
        $data['vtypetitle'] = trim($data['vtypetitle']);

        $vtitle_arr = explode("VS", $data['vtitle']);
        if(sizeof($vtitle_arr)>1){
            $team1 = trim($vtitle_arr[0]);
            $team2 = trim($vtitle_arr[1]);

            $start_index = strpos($team1, '(');
            if($start_index !== false){
                $team1 = trim(substr($team1, 0, $start_index));
            }
            $start_index = strpos($team2, '(');
            if($start_index !== false){
                $team2 = trim(substr($team2, 0, $start_index));
            }
            $start_index = strpos($team1, '（');
            if($start_index !== false){
                $team1 = trim(substr($team1, 0, $start_index));
            }
            $start_index = strpos($team2, '（');
            if($start_index !== false){
                $team2 = trim(substr($team2, 0, $start_index));
            }
            $start_index = strpos($team1, '【');
            if($start_index !== false){
                $team1 = trim(substr($team1, 0, $start_index));
            }
            $start_index = strpos($team2, '【');
            if($start_index !== false){
                $team2 = trim(substr($team2, 0, $start_index));
            }
        }else{
            return false;
        }


        $modelVideo = new Video;
        $modelVideocat = new Videocat;
        $modelVideotype = new Videotype;
        $modelTranslate = new Translate;
        
        if($data['vcattitle'] != '' AND $data['vtypetitle'] != '' AND $data['vlink'] != ''){
            $title = $data['vcattitle'];
            $code = $data['vcat'];
            if($title != ''){
                if(!isset($this->videocatlist[$title])){
                    $params = [
                        'title' => $title,
                        'title2' => $title,
                        'code' => $code,
                    ];
                    $nv = $modelVideocat::create($params);
                    $this->videocatlist[$title] = [
                        'id' => $nv->id,
                        'title' => $title,
                        'title2' => $title,
                        'status' => 1
                    ];
                }elseif($this->videocatlist[$title]['status'] == 0){
                    return false;
                }elseif($this->videocatlist[$title]['title'] != $title){
                    // $mVc = $modelVideocat->where("code = '".$code."' ")->find();
                    // if($mVc){
                    //     $mVc->title = $title;
                    //     $mVc->save();
                    // }
                    // $this->videocatlist[$code]['title'] = $title;
                }
            }
            
            $title = $data['vtypetitle'];
            $code = $data['vtype'];
            if($title != ''){
                if(!isset($this->videotypelist[$title])){
                    $params = [
                        'title' => $title,
                        'title2' => $title,
                        'code' => $code,
                    ];
                    $nv = $modelVideotype::create($params);
                    $this->videotypelist[$title] = [
                        'id' => $nv->id,
                        'title' => $title,
                        'title2' => $title,
                        'status' => 1
                    ];
                }elseif($this->videotypelist[$title]['status'] == 0){
                    return false;
                }elseif($this->videotypelist[$title]['title'] != $title){
                    // $mVc = $modelVideotype->where("code = '".$code."' ")->find();
                    // if($mVc){
                    //     $mVc->title = $title;
                    //     $mVc->save();
                    // }
                    // $this->videotypelist[$code]['title'] = $title;
                }
            }

            
            $chtext = $team1;
            if($chtext != ''){
                $mT = $modelTranslate->where("ch = '".$chtext."'")->find();
                if(!$mT){
                    $params = [
                        'ch' => $chtext,
                        'tw' => $chtext,
                    ];
                    $modelTranslate::create($params);
                }
            }
            $chtext = $team2;
            if($chtext != ''){
                $mT = $modelTranslate->where("ch = '".$chtext."'")->find();
                if(!$mT){
                    $params = [
                        'ch' => $chtext,
                        'tw' => $chtext,
                    ];
                    $modelTranslate::create($params);
                }
            }

            $chtext = $data['vcattitle'];
            if($chtext != ''){
                $mT = $modelTranslate->where("ch = '".$chtext."'")->find();
                if(!$mT){
                    $params = [
                        'ch' => $chtext,
                        'tw' => $chtext,
                    ];
                    $modelTranslate::create($params);
                }
            }
            $chtext = $data['vtypetitle'];
            if($chtext != ''){
                $mT = $modelTranslate->where("ch = '".$chtext."'")->find();
                if(!$mT){
                    $params = [
                        'ch' => $chtext,
                        'tw' => $chtext,
                    ];
                    $modelTranslate::create($params);
                }
            }

            if(!isset($this->videoData[md5($data['vlink'])])){
                $modelRandom = new Random;
                $dopred = [
                    1 => 50,
                    2 => 50,
                ];
                if($this->mBannerCode == md5($data['vlink'])){
                    $isbanner = 1;
                }else{
                    $isbanner = 0;
                }
                $params = [
                    'starttime' => $data['stime'],
                    'vlink' => $data['vlink'],
                    'title' => $data['vtitle'],
                    'team1' => $team1,
                    'team2' => $team2,
                    'winteam' => $modelRandom::lottery($dopred),
                    'isbanner' => $isbanner,
                    'cat_id' => $this->videocatlist[$data['vcattitle']]['id'],
                    'type_id' => $this->videotypelist[$data['vtypetitle']]['id'],
                ];
                $modelVideo::create($params);
            }elseif($this->videoData[md5($data['vlink'])]['title'] != $data['vtitle'] OR $this->videoData[md5($data['vlink'])]['starttime'] != $data['stime'] OR $this->videoData[md5($data['vlink'])]['status'] == 0){
                $mVc = $modelVideo->where("vlink = '".$data['vlink']."' ")->find();
                if($mVc){
                    if($this->videoData[md5($data['vlink'])]['title'] != $data['vtitle']){
                        $mVc->title = $data['vtitle'];
                        $mVc->team1 = $team1;
                        $mVc->team2 = $team2;
                        $mVc->translate = 0;
                    }
                    if($this->videoData[md5($data['vlink'])]['starttime'] != $data['stime']){
                        $mVc->starttime = $data['stime'];
                    }
                    $mVc->status = 1;
                    $mVc->save();
                }
                $this->videoData[md5($data['vlink'])] = [
                    'title' => $data['vtitle'],
                    'starttime' => $data['stime']
                ];
            }
        }else{
            Log::notice("[command][Cron][upData] 缺少參數: ".json_encode($data, JSON_UNESCAPED_UNICODE));
        }
    }

    private function getValue($content, $start_str, $end_str) {
        $content = str_replace('&nbsp;','',str_replace('<br>',' ',str_replace('<br/>',' ',trim($content))));
        $start_index = strpos($content, $start_str) + mb_strlen($start_str);
        $end_index = strpos($content, $end_str);
        if($end_index !== false AND $start_index !== false){
            return substr($content, $start_index, $end_index-$start_index);
        }else{
            return '';
        }
    }
    
    private function cutValue($content, $start_str, $end_str) {
        // Log::notice($content);
        $content = str_replace('&nbsp;','',str_replace('<br>',' ',str_replace('<br/>',' ',trim($content))));
        $start_index = strpos($content, $start_str);
        $end_index = strpos($content, $end_str) + mb_strlen($end_str);
        if($end_index !== false AND $start_index !== false){
            $cut = substr($content, $start_index, $end_index-$start_index);
            $content = str_replace($cut, '', $content);
        }
        return $content;
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
        
    private function get_content($url, $cookie = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);//是否显示头信息
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if($cookie !== null) curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie); //讀取cookie
        
        $rs = curl_exec($ch); //執行cURL抓取頁面內容
        curl_close($ch);
        return $rs;
    }
    


}