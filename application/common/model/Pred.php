<?php

namespace app\common\model;

use think\Model;

class Pred extends Model
{

    // 表名
    protected $name = 'pred';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    public function mevent()
    {
        return $this->belongsTo('Event', 'event_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function analyst()
    {
        return $this->belongsTo('Analyst', 'analyst_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
}
