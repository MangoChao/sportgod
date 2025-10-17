<?php

namespace app\admin\controller\event;

use app\common\controller\Backend;
use app\common\library\Auth;
use think\Log;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use Exception;
use fast\Random;
use think\Config;

class Linepredcode extends Backend
{

    protected $relationSearch = true;
    protected $searchFields = '';
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('LinePredCode');
        $mFields = $this->model->getQuery()->getTableInfo('', 'fields');
        $this->searchFields = implode(',',$mFields);
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $result = false;
                try {
                    if (isset($params['seepred_count'])) {
                        $mConfig = model('Config')->get(['name' => 'seepred_count']);
                        if($mConfig){
                            $mConfig->value = $params['seepred_count'];
                            $mConfig->save();
                            $result = true;
                        }
                    }
                } catch (ValidateException $e) {
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    try {
                        $this->refreshFile();
                    } catch (Exception $e) {
                        $this->error($e->getMessage());
                    }
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
        }
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
            ->with(['analyst'])
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
            ->with(['analyst'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $seepredCount = Config::get("site.seepred_count");
        $this->view->assign("seepred_count", $seepredCount);
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
        $max = 5;
        do{
            $code = "##".strtolower(Random::alnum(10));
            $max--;
        }while($this->model->get(['code' => $code]) && $max >= 0);
        $this->view->assign("code", $code);
        return $this->view->fetch();
    }

    /**
     * 刷新配置文件
     */
    protected function refreshFile()
    {
        $config = [];
        foreach (model("Config")->all() as $k => $v) {
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
