<?php

namespace app\common\model;

use think\Model;

/**
 * 配置模型
 */
class Baccaratorder extends Model
{

    // 表名,不含前缀
    protected $name = 'baccarat_order';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    public function baccarat()
    {
        return $this->belongsTo('Baccarat', 'baccarat_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
