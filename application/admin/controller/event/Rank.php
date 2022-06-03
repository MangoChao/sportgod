<?php

namespace app\admin\controller\event;

use app\common\controller\Backend;
use app\common\library\Auth;
use think\Log;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use Exception;

class Rank extends Backend
{

    protected $relationSearch = true;
    protected $searchFields = '';
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('rank');
        $mFields = $this->model->getQuery()->getTableInfo('', 'fields');
        $this->searchFields = implode(',',$mFields);
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
            // ->with(['mevent','analyst'])
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
            // ->with(['mevent','analyst'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    
    public function content($ids = null)
    {
        $mRankcontent = model('Rankcontent')->alias('rc')
        ->join("analyst a","a.id = rc.analyst_id")
        ->field("rc.*, a.analyst_name")
        ->where('rc.rank_id = '.$ids)->order('rc.rank','asc')->select();
        if(!$mRankcontent){
            $this->error(__('查無資料'));
        }
        $this->view->assign("mRankcontent", $mRankcontent);
        return $this->view->fetch();
    }

    

}
