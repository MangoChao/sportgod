<?php

namespace app\admin\controller\event;

use app\common\controller\Backend;
use app\common\library\Auth;
use think\Log;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use Exception;

class Event extends Backend
{

    protected $relationSearch = true;
    protected $searchFields = '';
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('event');
        $mFields = $this->model->getQuery()->getTableInfo('', 'fields');
        $this->searchFields = implode(',',$mFields);
        $this->view->assign("eventcatlist", $this->model->getEventcatlist());
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        $getParam = $this->request->except('addtabs');
        if (!$getParam) {
            $starttime = date("Y-m-d") . ' 00:00:00 - ' . date("Y-m-d") . ' 23:59:59';
            $this->redirect('', ['starttime' => $starttime]);
        }
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
            ->with(['eventcat'])
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
            ->with(['eventcat'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            foreach ($list as $k => $v) {
                if(model('Eventparam')->where('event_id = '.$v->id)->find()){
                    $v->hasparam = 1;
                }else{
                    $v->hasparam = 0;
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    public function param($ids = null)
    {
        $mEventparam = model('Eventparam')->where('event_id = '.$ids)->order('createtime','asc')->select();
        if(!$mEventparam){
            $this->error(__('查無資料'));
        }
        $this->view->assign("mEventparam", $mEventparam);
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
                $newscore = true;
                if($params['master_score'] != "" && $params['guests_score'] != ""){
                    $newscore = true;
                }elseif($params['master_score'] == "" && $params['guests_score'] == ""){
                    $params['master_score'] = null;
                    $params['guests_score'] = null;
                    $newscore = false;
                }else{
                    $this->error(__('兩欄要同時填,或者都不填'));
                }

                $result = false;
                Db::startTrans();
                try {
                    $mPred = model('Pred')->where("event_id = '".$ids."'")->select();
                    if($mPred){
                        if($newscore){
                            //寫入預測結果
                            foreach($mPred as $v){
                                $v->master_score = $params['master_score'];
                                $v->guests_score = $params['guests_score'];
                                if($v->pred_type == 1){
                                    if($v->master_refund != null){
                                        // Log::notice($v->master_refund);
                                        $winscore = $v->master_score - $v->guests_score;
                                        $refund = $v->master_refund;
                                        $minus = false;
                                        $l = strpos($refund, '-');
                                        if($l === false){
                                            //+
                                            $l = strpos($refund, '+');
                                            if($l === false){
                                                $l = mb_strlen($refund);
                                            }
                                        }else{
                                            //-
                                            $minus = true;
                                        }
                                        $refund = substr($refund, 0, $l);
                                        if(!is_numeric($refund)){
                                            Log::notice('refund非數字');
                                            Log::notice($refund);
                                            continue;
                                        }
                                        if($minus){
                                            $refund = $refund+1;
                                        }
                                        if($winscore < $refund AND $v->winteam == 0){
                                            $v->comply = 1;
                                        }elseif($winscore >= $refund AND $v->winteam == 1){
                                            $v->comply = 1;
                                        }else{
                                            $v->comply = 2;
                                        }
        
                                    }elseif($v->guests_refund != null){
                                        // Log::notice($v->guests_refund);
                                        $winscore = $v->guests_score - $v->master_score;
                                        $refund = $v->guests_refund;
                                        $minus = false;
                                        $l = strpos($refund, '-');
                                        if($l === false){
                                            //+
                                            $l = strpos($refund, '+');
                                            if($l === false){
                                                $l = mb_strlen($refund);
                                            }
                                        }else{
                                            //-
                                            $minus = true;
                                        }
                                        $refund = substr($refund, 0, $l);
                                        if(!is_numeric($refund)){
                                            Log::notice('refund非數字');
                                            Log::notice($refund);
                                            continue;
                                        }
                                        if($minus){
                                            $refund = $refund+1;
                                        }
                                        if($winscore < $refund AND $v->winteam == 1){
                                            $v->comply = 1;
                                        }elseif($winscore >= $refund AND $v->winteam == 0){
                                            $v->comply = 1;
                                        }else{
                                            $v->comply = 2;
                                        }
                                    }else{
                                        Log::notice('讓分有誤, pred_id:'.$v->id);
                                        continue;
                                    }
                                }else{
                                    $totalscore = $v->master_score + $v->guests_score;
                                    $bigscore = $v->bigscore;
                                    $minus = false;
                                    $l = strpos($bigscore, '-');
                                    if($l === false){
                                        //+
                                        $l = strpos($bigscore, '+');
                                        if($l === false){
                                            $l = mb_strlen($bigscore);
                                        }
                                    }else{
                                        //-
                                        $minus = true;
                                    }
                                    $bigscore = substr($bigscore, 0, $l);
                                    if($minus){
                                        $bigscore = $bigscore+1;
                                    }
        
                                    if($totalscore < $bigscore AND $v->bigsmall == 0){
                                        $v->comply = 1;
                                    }elseif($totalscore >= $bigscore AND $v->bigsmall == 1){
                                        $v->comply = 1;
                                    }else{
                                        $v->comply = 2;
                                    }
                                }
                                $v->save();
                            }
                        }else{
                            //重置預測結果
                            foreach($mPred as $v){
                                $v->master_score = null;
                                $v->guests_score = null;
                                $v->comply = 0;
                                $v->save();
                            }
                        }
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
