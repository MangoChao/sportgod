<?php

namespace app\common\model;

use think\Model;

class Baccarat extends Model
{

    // 表名
    protected $name = 'baccarat';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1')];
    }

    
    public function order()
    {
        return $this->belongsTo('Baccaratorder', 'baccarat_order_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
}
