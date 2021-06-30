<?php
namespace app\admin\command\cron;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Exception;
// use app\common\model\Notify;
use think\Log;
use app\common\library\Email;
use think\Config;
//v1
class SendNotify extends Command
{
    protected $taskName = '發送通知';
    protected $tmpDataJson = "";
    protected $site_url = [];

    protected function configure(){
        $this->setName('SendNotify')->setDescription("發送通知");
    }

    protected function execute(Input $input, Output $output){
        $output->writeln('[ '.date('Y-m-d H:i:s').' ] '.$this->taskName.' job start... ');
            $res = $this->SendNotify();
        $output->writeln('[ '.date('Y-m-d H:i:s').' ] '.$this->taskName.' job end... ');
    }
    
    public function SendNotify()
    {
       
    }


}