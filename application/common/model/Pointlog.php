<?php

namespace app\common\model;

use think\Model;

/**
 * 配置模型
 */
class Pointlog extends Model
{

    // 表名,不含前缀
    protected $name = 'point_log';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
}
