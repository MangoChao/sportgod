<?php

namespace app\admin\controller\baccarat;

use app\common\controller\Backend;
use app\common\library\Auth;
use think\Log;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use Exception;


class Baccaratorder extends Backend
{

    protected $relationSearch = true;
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Baccaratorder');
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
            ->with(['baccarat'])
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
            ->with(['baccarat'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        
        return $this->view->fetch();
    }
}
