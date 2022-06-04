<?php

namespace app\common\model;

use think\Model;

class Eventcategory extends Model
{

    // 表名
    protected $name = 'event_category';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

}
