<?php

namespace app\common\model;

use think\Model;

class Event extends Model
{

    // 表名
    protected $name = 'event';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    public function getEventcatlist()
    {
        $eventcatlist = [];
        $mEventcategory = model('Eventcategory')->all();
        if($mEventcategory){
            foreach($mEventcategory as $v){
                $eventcatlist[$v->id] = $v->title;
            }
        }
        return $eventcatlist;
    }

    public function eventcat()
    {
        return $this->belongsTo('Eventcategory', 'event_category_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
}
