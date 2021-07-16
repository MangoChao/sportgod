<?php

namespace app\admin\controller\frontend;

use app\common\controller\Backend;
use app\common\library\Auth;
use think\Log;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use Exception;
use think\Config;

class Siteconfig extends Backend
{

    protected $relationSearch = true;
    protected $searchFields = '';
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Siteconfig');
    }

    /**
     * 查看
     */
    public function index()
    {

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                foreach($params as $k=>$v){
                    $m = $this->model->where("`key` ='".$k."' ")->find();
                    if($m){
                        $m->value = $v;
                        $m->save();
                    }
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $mSiteconfig = $this->model->all();
        $this->view->assign("mSiteconfig", $mSiteconfig);
        return $this->view->fetch();
    }


}
