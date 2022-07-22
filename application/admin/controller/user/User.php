<?php

namespace app\admin\controller\user;

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

    protected $dataLimit = false;
    protected $relationSearch = true;
    protected $searchFields = '';

    /**
     * @var \app\admin\model\User
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('User');
        $mFields = $this->model->getQuery()->getTableInfo('', 'fields');
        $this->searchFields = implode(',',$mFields);
        $this->view->assign("statusList", $this->model->getStatusList());
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
            $list = $this->model
                ->with(['service'])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as $k => $v) {
                $v->avatar = $v->avatar ? cdnurl($v->avatar, true) : letter_avatar($v->nickname);
                $v->hidden(['password', 'salt']);
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

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
            $this->token();
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                if($params['code'] != ""){
                    $mUser = $this->model->get(['code' => $params['code'], 'status' => ['<>',3]]);
                    if($mUser){
                        $this->error(__('代碼已存在'));
                    }
                }

                if($params['bid'] != ""){
                    $mUser = $this->model->get(['bid' => $params['bid'], 'status' => ['<>',3]]);
                    if($mUser){
                        $this->error(__('球版ID已存在'));
                    }
                }
                if($params['nickname'] == '') $params['nickname'] = $params['bid'];

                if($params['ptime1'] == '' OR $params['ptime2'] == ''){
                    $params['ptime1'] = null;
                    $params['ptime2'] = null;
                }else{
                    $params['ptime1'] = strtotime($params['ptime1']);
                    $params['ptime2'] = strtotime($params['ptime2']);
                }

                $params['pred2'] = $params['pred'];

                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }

                    // $params['salt'] = Random::alnum();
                    // $params['password'] = $this->getEncryptPassword($params['password'], $params['salt']);


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
                $params = $this->preExcludeFields($params);

                if($params['code'] != ""){
                    $mUser = $this->model->get(['id' => ['<>',$ids], 'code' => $params['code'], 'status' => ['<>',3]]);
                    if($mUser){
                        $this->error(__('代碼已存在'));
                    }
                }
                if($params['bid'] != ""){
                    $mUser = $this->model->get(['id' => ['<>',$ids], 'bid' => $params['bid'], 'status' => ['<>',3]]);
                    if($mUser){
                        $this->error(__('球版ID已存在'));
                    }
                }
                if($params['nickname'] == '') $params['nickname'] = $params['bid'];

                if($params['ptime1'] == '' OR $params['ptime2'] == ''){
                    $params['ptime1'] = null;
                    $params['ptime2'] = null;
                }else{
                    $params['ptime1'] = strtotime($params['ptime1']);
                    $params['ptime2'] = strtotime($params['ptime2']);
                }
                
                if($params['pred'] != $row->pred){
                    $params['pred2'] = $params['pred'];
                }

                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
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
                    
                    $mAnalyst = model('Analyst')->where("user_id = ".$ids)->find();
                    if($mAnalyst){
                        $mAnalyst->avatar = $row->avatar;
                        $mAnalyst->analyst_name = $row->nickname;
                        $mAnalyst->save();
                    }

                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        if($row->ptime1) $row->ptime1 = date("Y-m-d", $row->ptime1);
        if($row->ptime2) $row->ptime2 = date("Y-m-d", $row->ptime2);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        $row = $this->model->get($ids);
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        Auth::instance()->delete($row['id']);
        $this->success();
    }

}
