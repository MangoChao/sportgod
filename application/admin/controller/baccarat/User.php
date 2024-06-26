<?php

namespace app\admin\controller\baccarat;

use app\common\controller\Backend;
use app\common\library\Auth;
use think\Log;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use Exception;
use fast\Random;


class User extends Backend
{

    protected $relationSearch = true;
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Baccarat');
        $this->view->assign("statusList", $this->model->getStatusList());
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
            ->with(['order'])
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
            ->with(['order'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
                
            foreach ($list as $k => $v) {
                $v->checkout_link = $this->site_url['furl']."/index/baccarat/checkout/code/".$v->code;
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        
        return $this->view->fetch();
    }

    
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                
                if($params['code'] != ""){
                    $mUser = $this->model->get(['code' => $params['code']]);
                    if($mUser){
                        $this->error(__('代碼已存在'));
                    }
                }

                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }

                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        do{
            $code = strtoupper(Random::alnum(6));
            $check = $this->model->where("code ='".$code."'")->find();
        }while($check === false);
        $this->view->assign("code", $code);
        return $this->view->fetch();
    }
    
    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                
                if($params['code'] != ""){
                    $mUser = $this->model->get(['id' => ['<>',$ids], 'code' => $params['code']]);
                    if($mUser){
                        $this->error(__('代碼已存在'));
                    }
                }

                $result = false;
                Db::startTrans();
                try {

                    if($row->locked == 1 AND $params['locked'] == 0) $params['act'] = 0;
                    
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    

}
