<?php

namespace app\common\model;

use think\Model;

/**
 * 配置模型
 */
class Article extends Model
{

    // 表名,不含前缀
    protected $name = 'article';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function cat()
    {
        return $this->belongsTo('Articlecat', 'cat_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
}
