<?php

namespace app\admin\controller\baccarat;

use app\common\controller\Backend;
use app\common\library\Auth;
use think\Log;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use Exception;
use think\Config;

class Set extends Backend
{

    protected $dataLimit = true;
    protected $relationSearch = true;
    protected $searchFields = '';
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Config');
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $mConfig = $this->model->where("`name` IN ('baccarat_url') ")->select();
            if($mConfig){
                foreach($mConfig as $v){
                    $v->value = $params[$v->name];
                    $v->save();
                }
                
                try {
                    $this->refreshFile();
                } catch (Exception $e) {
                    $this->error($e->getMessage());
                }

                $this->success();
            }else{
                $this->error('發生錯誤');
            }
        }
        $mConfig = $this->model->where("`name` IN ('baccarat_url') ")->select();
        $this->view->assign("mConfig", $mConfig);
        return $this->view->fetch();
    }
    
    protected function refreshFile()
    {
        $config = [];
        foreach ($this->model->all() as $k => $v) {
            $value = $v->toArray();
            if (in_array($value['type'], ['selects', 'checkbox', 'images', 'files'])) {
                $value['value'] = explode(',', $value['value']);
            }
            if ($value['type'] == 'array') {
                $value['value'] = (array)json_decode($value['value'], true);
            }
            $config[$value['name']] = $value['value'];
        }
        file_put_contents(
            CONF_PATH . 'extra' . DS . 'site.php',
            '<?php' . "\n\nreturn " . var_export_short($config) . ";\n"
        );
    }

}
