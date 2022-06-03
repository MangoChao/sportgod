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

class Getvideocat extends Command
{
    protected $taskName = '抓取全部直播';
    protected $site = [];
    protected $gameurl = "https://www.letv8.cc/";
    protected $vDate = '';
    protected $videocatlist = [];
    protected $videotypelist = [];
    protected $videoData = false;
    protected $mBannerCode = '';

    protected function configure(){
        $this->setName('Getvideocat')->setDescription("抓取全部直播");
    }

    protected function execute(Input $input, Output $output){
        Log::init(['type' => 'File', 'log_name' => 'cron_Getvideocat']);
        $this->site = Config::get("site");

        $modelVideocat = new Videocat;
        $mcat = $modelVideocat->where('status = 1')->order(['lastcron'=>'ASC','id'=>'ASC'])->find();
        if($mcat){
            $mcat->lastcron = time();
            $mcat->save();
            $this->gameurl .= 'class/'.$mcat->code;
            $this->Getvideocat();
        }
    }
    
    public function Getvideocat()
    {
        try {
            $func_name = 'Getvideocat';
            Log::notice("[command][Cron][".$func_name."] 開始執行 ".date('Y-m-d H:i:s',time()));

            $modelVideo = new Video;
            $modelVideocat = new Videocat;
            $modelVideotype = new Videotype;
            
            // date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." +2 hours"))
            $mBanner = $modelVideo->where('isbanner = 1')->find();
            if($mBanner){
                $this->mBannerCode = md5($mBanner->vlink);
            }
            // $mV = $modelVideo->where('starttime','<',time())->select();
            // if($mV){
            //     foreach($mV as $v){
            //         $v->status = 0;
            //         $v->save();
            //     }
            // }

            $mVideocat = $modelVideocat->all();
            if($mVideocat){
                foreach($mVideocat as $v){
                    $this->videocatlist[$v->code] = [
                        'id' => $v->id,
                        'title' => $v->title,
                        'status' => $v->status,
                    ];
                }
            }
            $mVideotype = $modelVideotype->all();
            if($mVideotype){
                foreach($mVideotype as $v){
                    $this->videotypelist[$v->code] = [
                        'id' => $v->id,
                        'title' => $v->title,
                        'status' => $v->status,
                    ];
                }
            }

            $content = $this->get_content($this->gameurl);
            $content_arr = explode(PHP_EOL,$content);
            // Log::notice($content_arr);

            $trstart = false; //行開始
            $tdstart = false; //列開始
            $tdrow = 0;
            $uData = [];

            $getDate = false; 

            $stime = false; 
            $vcat = false; 
            $vtype = false; 
            $vtitle = false; 
            $vlink = false; 
            
            $getend = false; 

            if(sizeof($content_arr)>0){
                // Log::notice($content_arr);
                foreach($content_arr as $content_line){
                    $content_line = trim($content_line);
                    
                    if(strpos($content_line, '赛事直播</h2>') !== false){ //日期
                        // if($getend) break;
                        $getend = true;
                        $start_str = '<h2 class="widget-title">[';
                        $end_str = ']';
                        $this->vDate = $this->getValue($content_line, $start_str, $end_str);
                        
                        $yday = strtotime($this->vDate);
                        $eday = strtotime($this->vDate." +1 day");
                        $this->videoData = [];
                        $mVideo = $modelVideo->where("starttime >= ".$yday." AND starttime <".$eday)->select();
                        if($mVideo){
                            foreach($mVideo as $v){
                                $this->videoData[md5($v->vlink)] = [
                                    'title' => $v->title,
                                    'starttime' => $v->starttime,
                                    'status' => $v->status,
                                ];
                            }
                        }

                    }else{
                        if(strpos($content_line, '<li class=" stream-playing') !== false){ //偵測行開始
                            $trstart = true; 
                            $uData = [
                                'vcat' => '',
                                'vtype' => '',
                                'stime' => '',
                                'vtitle' => '',
                                'vlink' => '',
                                'vcattitle' => '',
                                'vtypetitle' => '',
                            ];
                        }
                        if(strpos($content_line, '</li>') !== false){ //偵測行結束
                            $trstart = false; 
                            $tdrow = 0;
                            if(sizeof($uData) > 0){
                                $this->upData($uData); //寫入資料
                            }
                            $uData = [];
                        }
    
                        if($trstart){
                            if(strpos($content_line, '<div class="datetime">') !== false){ //時間
                                $stime = true; 
                            }
                            
                            if(strpos($content_line, '<div class="category">') !== false){ //類別
                                $vcat = true; 
                            }
                            
                            if(strpos($content_line, '<div class="league">') !== false){ //子類別
                                $vtype = true; 
                            }
                            
                            if(strpos($content_line, '<div class="vs-info">') !== false){ //標題
                                $vtitle = true; 
                            }
                            
                            if(strpos($content_line, 'class="overlay-link"') !== false){ //連結
                                $vlink = true; 
                            }
                        }
    
                        if($content_line != '' AND $trstart){
                            if($stime){ 
                                $start_str = '<span class="time">';
                                $end_str = '</span>';
                                $uData['stime'] = $this->getValue($content_line, $start_str, $end_str);
                                $stime = false;
                                continue;
                            }
                            if($vcat){ 
                                $start_str = '<a href="/class/';
                                $end_str = '" target="_blank"';
                                $uData['vcat'] = $this->getValue($content_line, $start_str, $end_str);
                                $start_str = 'target="_blank">';
                                $end_str = '</a>';
                                $uData['vcattitle'] = $this->getValue($content_line, $start_str, $end_str);
                                $vcat = false;
                                continue;
                            }
                            if($vtype){ 
                                $start_str = '<a href="/type/';
                                $end_str = '.html" target="_blank"';
                                $uData['vtype'] = $this->getValue($content_line, $start_str, $end_str);
                                $start_str = 'target="_blank">';
                                $end_str = '</a>';
                                $uData['vtypetitle'] = $this->getValue($content_line, $start_str, $end_str);
                                $vtype = false;
                                continue;
                            }
                            if($vtitle){ 
                                $start_str = '<span class="name">';
                                $end_str = '</span>';
                                $uData['vtitle'] = $this->getValue($content_line, $start_str, $end_str);
                                $vtitle = false;
                                continue;
                            }
                            if($vlink){ 
                                $start_str = 'href="/live';
                                $end_str = '.html" target="_blank"';
                                $uData['vlink'] = $this->getValue($content_line, $start_str, $end_str);
                                $vlink = false;
                                continue;
                            }
                        }
                    }
                }
            }
            // $modelVideo->where('status','=',0)->delete();

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
            $data['stime'] = strtotime($this->vDate." ".$data['stime']);
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
        
        if($data['vcat'] != '' AND $data['vtype'] != '' AND $data['vlink'] != ''){
            $title = $data['vcattitle'];
            $code = $data['vcat'];
            if($title != ''){
                if(!isset($this->videocatlist[$code])){
                    $params = [
                        'title' => $title,
                        'code' => $code,
                    ];
                    $nv = $modelVideocat::create($params);
                    $this->videocatlist[$code] = [
                        'id' => $nv->id,
                        'title' => $title,
                        'status' => 1
                    ];
                }elseif($this->videocatlist[$code]['status'] == 0){
                    return false;
                }elseif($this->videocatlist[$code]['title'] != $title){
                    $mVc = $modelVideocat->where("code = '".$code."' ")->find();
                    if($mVc){
                        $mVc->title = $title;
                        $mVc->save();
                    }
                    $this->videocatlist[$code]['title'] = $title;
                }
            }
            
            $title = $data['vtypetitle'];
            $code = $data['vtype'];
            if($title != ''){
                if(!isset($this->videotypelist[$code])){
                    $params = [
                        'title' => $title,
                        'code' => $code,
                    ];
                    $nv = $modelVideotype::create($params);
                    $this->videotypelist[$code] = [
                        'id' => $nv->id,
                        'title' => $title,
                        'status' => 1
                    ];
                }elseif($this->videotypelist[$code]['status'] == 0){
                    return false;
                }elseif($this->videotypelist[$code]['title'] != $title){
                    $mVc = $modelVideotype->where("code = '".$code."' ")->find();
                    if($mVc){
                        $mVc->title = $title;
                        $mVc->save();
                    }
                    $this->videotypelist[$code]['title'] = $title;
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
                    'cat_id' => $this->videocatlist[$data['vcat']]['id'],
                    'type_id' => $this->videotypelist[$data['vtype']]['id'],
                ];
                $modelVideo::create($params);
            }elseif($this->videoData[md5($data['vlink'])]['title'] != $data['vtitle'] OR $this->videoData[md5($data['vlink'])]['starttime'] != $data['stime'] OR $this->videoData[md5($data['vlink'])]['status'] == 0){
                $mVc = $modelVideo->where("vlink = '".$data['vlink']."' ")->find();
                if($mVc){
                    $mVc->title = $data['vtitle'];
                    $mVc->team1 = $team1;
                    $mVc->team2 = $team2;
                    $mVc->starttime = $data['stime'];
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