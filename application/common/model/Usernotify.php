<?php

namespace app\common\model;

use think\Model;

class Usernotify extends Model
{

    // 表名
    protected $name = 'user_notify';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
}
