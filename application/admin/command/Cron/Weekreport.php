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

use app\common\model\Analyst;
use app\common\model\Pred;
use app\common\model\Event;
use app\common\model\Eventparam;
use app\common\model\Eventcategory;
use app\common\model\Rank;
use app\common\model\Rankcontent;

class Weekreport extends Command
{
    protected $taskName = '周結算';
    protected $site = [];
    protected $gameurl = "https://ag.bl568.net";

    protected function configure(){
        $this->setName('Weekreport')->setDescription("周結算");
    }

    protected function execute(Input $input, Output $output){
        Log::init(['type' => 'File', 'log_name' => 'cron_Weekreport']);
        $this->site = Config::get("site");
        $this->Weekreport();
    }
    
    public function Weekreport()
    {
        try {
            $func_name = 'Weekreport';
            Log::notice("[command][Cron][".$func_name."] 開始執行 ".date('Y-m-d H:i:s',time()));
            
            $todayTime = strtotime(date("Y-m-d"));
            // $todayTime = strtotime(date("Y-m-d")." +2 week");
            // $weekTime = strtotime(date("Y-m-d")." -1 week");
            $weekTime = strtotime(date("Y-m-d")." -2 week");
            $modelAnalyst = new Analyst;
            $mAnalyst = $modelAnalyst->alias('a')
            ->join("pred p","a.id = p.analyst_id")
            ->join("event e","e.id = p.event_id")
            ->field("a.id, count(case when p.comply = 1 then 0 end)/count(p.id)*100 as winrate")
            ->where("p.comply <> 0 AND e.starttime > ".$weekTime." AND e.starttime < ".$todayTime)->group("a.id")->order("winrate","desc")->limit(20)->select();
            if($mAnalyst){
                $param = [
                    'rtime1' => $weekTime,
                    'rtime2' => $todayTime,
                ];
                $modelRank = new Rank;
                $mRank = $modelRank::create($param);
                if($mRank){
                    foreach($mAnalyst as $k=>$v){
                        $param = [
                            'rank_id' => $mRank->id,
                            'analyst_id' => $v->id,
                            'winrate' => $v->winrate,
                            'rank' => $k+1,
                        ];
                        $modelRankcontent = new Rankcontent;
                        $modelRankcontent::create($param);
                    }
                }else{
                    Log::notice("[command][Cron][".$func_name."] 建立排行失敗");
                }
            }else{
                Log::notice("[command][Cron][".$func_name."] 無預測");
            }

            Log::notice("[command][Cron][".$func_name."] 完整結束 ".date('Y-m-d H:i:s',time()));
        } catch (ValidateException $e) {
            Log::notice("[command][Cron][".$func_name."] ValidateException :".$e->getMessage());
        } catch (PDOException $e) {
            Log::notice("[command][Cron][".$func_name."] PDOException :".$e->getMessage());
        } catch (Exception $e) {
            if($v){
                Log::notice("v data:");
                Log::notice($v);
            }
            if($mEvent){
                Log::notice("mEvent data:");
                Log::notice($mEvent);
            }
            Log::notice("[command][Cron][".$func_name."] Exception :".$e->getMessage());
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