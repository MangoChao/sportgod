<?php

namespace app\common\model;

use think\Model;

class Usertopred extends Model
{

    // 表名
    protected $name = 'user_to_pred';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function pred()
    {
        return $this->belongsTo('Pred', 'pred_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
