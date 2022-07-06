<?php

namespace app\common\model;

use think\Model;

/**
 * 配置模型
 */
class Dotnet extends Model
{

    // 表名,不含前缀
    protected $name = 'dotnet';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
    public function goduser()
    {
        return $this->belongsTo('User', 'godarticle_user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
}
