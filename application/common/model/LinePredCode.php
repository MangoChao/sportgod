<?php

namespace app\common\model;

use think\Model;

class LinePredCode extends Model
{

    // 表名
    protected $name = 'line_pred_code';

    public function getStatusList()
    {
        return ['0' => __('line_pred_code status 0'), '1' => __('line_pred_code status 1')];
    }

    public function analyst()
    {
        return $this->belongsTo('Analyst', 'analyst_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
}
