<?php

namespace app\admin\controller\frontend;

use app\common\controller\Backend;
use app\common\library\Auth;
use think\Log;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use Exception;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;


class Godarticle extends Backend
{

    protected $noNeedRight = ['*'];
    // protected $dataLimit = true;
    protected $relationSearch = true;
    protected $searchFields = '';
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Godarticle');
        $mFields = $this->model->getQuery()->getTableInfo('', 'fields');
        $this->searchFields = implode(',',$mFields);
        $this->view->assign("statusList", $this->model->getStatusList());
    }
    
    public function getArticlecat()
    {
        $type_list = [];
        $type_list[0] = '請選擇專欄';
        $mArticlecat = model('GodType')->where('status = 1')->order('weigh','asc')->select();
        foreach ($mArticlecat as $k => $v) {
            $type_list[$v['id']] = $v['type_name'];
        }

        $this->view->assign('type_list', $type_list);
        
        $cat_list = [];
        $cat_list[0] = '請選擇分類';
        $mArticlecat = model('Articlecat')->where('status = 1')->select();
        foreach ($mArticlecat as $k => $v) {
            $cat_list[$v['id']] = $v['cat_name'];
        }

        $this->view->assign('cat_list', $cat_list);
        
        $this->view->assign('teach_cat_list', getTeachCatList());
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
                ->with(['user','cat','godtype'])
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with(['user','cat','godtype'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
                
            $teachCatList = getTeachCatList();
            foreach ($list as $k => $v) {
                if($v->god_type == 2){
                    $v->cat->cat_name = $teachCatList[$v->cat->id] ?? "未選擇分類";
                }
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
        if($this->request->isPost()) {
            $this->token();
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $params['video_url'] = getYoutubeEmbedUrl($params['video_url']);

                if($params['god_type'] == 0){
                    $this->error(__('請選擇專欄'));
                }

                if($params['god_type'] == 2){
                    if($params['teach_cat_id'] == 0){
                        $this->error(__('請選擇分類'));
                    }
                    $params['cat_id'] = $params['teach_cat_id'];
                }else{
                    if($params['cat_id'] == 0){
                        $this->error(__('請選擇分類'));
                    }
                }
                
                if($params['god_type'] == 2 || $params['god_type'] == 4){
                    if($params['video_url'] == ''){
                        $this->error(__('請填YT連結'));
                    }
                }else{
                    if($params['cover_img'] == '' && $params['video_url'] == ''){
                        $this->error(__('請上傳封面圖 或是 填入YT連結'));
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

                    $params['user_id'] = 0;

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
        $this->getArticlecat();
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
                $params['video_url'] = getYoutubeEmbedUrl($params['video_url']);
                
                if($params['god_type'] == 0){
                    $this->error(__('請選擇專欄'));
                }

                if($params['god_type'] == 2){
                    if($params['teach_cat_id'] == 0){
                        $this->error(__('請選擇分類'));
                    }
                    $params['cat_id'] = $params['teach_cat_id'];
                }else{
                    if($params['cat_id'] == 0){
                        $this->error(__('請選擇分類'));
                    }
                }
                
                if($params['god_type'] == 2 || $params['god_type'] == 4){
                    if($params['video_url'] == ''){
                        $this->error(__('請填YT連結'));
                    }
                }else{
                    if($params['cover_img'] == '' && $params['video_url'] == ''){
                        $this->error(__('請上傳封面圖 或是 填入YT連結'));
                    }
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

                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        
        $this->getArticlecat();
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

}
