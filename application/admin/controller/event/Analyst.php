<?php

namespace app\admin\controller\event;

use app\common\controller\Backend;
use app\common\library\Auth;
use think\Log;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use Exception;

class Analyst extends Backend
{

    protected $dataLimit = true;
    protected $relationSearch = true;
    protected $searchFields = '';
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('analyst');
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
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            foreach($list as $k=>$v){
                $pred_cat = [];
                
                $matc = model('Analysttoeventcategory')->alias('atc')
                ->join("event_category gc","atc.event_category_id = gc.id")
                ->field("gc.title")
                ->where("atc.analyst_id = ".$v->id)->select();
                if($matc){
                    foreach($matc as $av){
                        $pred_cat[] = $av->title;
                    }
                }
                $v->pred_cat = implode(',',$pred_cat);
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
            $this->token();
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                
                $mAnalyst = $this->model->get(['analyst_name' => $params['analyst_name'], 'status' => ['<>',3]]);
                if($mAnalyst){
                    $this->error(__('分析師已存在, 請用其他名稱'));
                }

                $eventcategory_id = $params['eventcategory_id'];
                unset($params['eventcategory_id']);
                
                if($params['autopred'] == 1){
                    $params['free'] = 0;
                }else{
                    $params['free'] = 1;
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
                    
                    if($eventcategory_id != ''){
                        foreach(explode(',', $eventcategory_id) as $gcid){
                            $gc_params = [
                                'analyst_id' => $this->model->id,
                                'event_category_id' => $gcid
                            ];
                            model('Analysttoeventcategory')::create($gc_params);
                            
                            $mEventcategory = model('Eventcategory')->where('id = '.$gcid)->find();
                            if($mEventcategory){
                                $mEventcategory->analyst = model('Analysttoeventcategory')->where('event_category_id = '.$gcid)->count();
                                $mEventcategory->save();
                            }
                        }
                    }
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

                $mAnalyst = $this->model->get(['id' => ['<>',$ids], 'analyst_name' => $params['analyst_name'], 'status' => ['<>',3]]);
                if($mAnalyst){
                    $this->error(__('分析師已存在, 請用其他名稱'));
                }

                $eventcategory_id = $params['eventcategory_id'];
                unset($params['eventcategory_id']);

                if($params['autopred'] == 1){
                    $params['free'] = 0;
                }else{
                    $params['free'] = 1;
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
                    
                    model('Analysttoeventcategory')->where('analyst_id = '.$ids)->delete();

                    if($eventcategory_id != ''){
                        foreach(explode(',', $eventcategory_id) as $gcid){
                            $gc_params = [
                                'analyst_id' => $ids,
                                'event_category_id' => $gcid
                            ];
                            model('Analysttoeventcategory')::create($gc_params);

                            $mEventcategory = model('Eventcategory')->where('id = '.$gcid)->find();
                            if($mEventcategory){
                                $mEventcategory->analyst = model('Analysttoeventcategory')->where('event_category_id = '.$gcid)->count();
                                $mEventcategory->save();
                            }
                        }
                    }

                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $eventcategory_id_selected = [];
        
        $matc = model('Analysttoeventcategory')->alias('atc')
        ->field("atc.event_category_id")
        ->where("atc.analyst_id = ".$ids)->select();
        if($matc){
            foreach($matc as $av){
                $eventcategory_id_selected[] = $av->event_category_id;
            }
        }

        $this->view->assign("eventcategory_id_selected", implode(',',$eventcategory_id_selected));
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    

}
