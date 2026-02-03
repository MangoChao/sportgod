<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\controller\LineBot;
use think\Log;
use fast\Http;
use think\Config;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

class Event extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];


    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 1. 取得體育分類列表
     */
    public function getCategoryList()
    {
        $list = model('Eventcategory')
            ->where('status', 1)
            ->field('id, title')
            ->order('id asc')
            ->select();

        $this->success('success', $list);
    }

    /**
     * 2. 取得賽事列表 (直接輸出原始資料)
     */
    public function getEventList()
    {
        $catId = $this->request->request('cat_id/d', 0);
        $page  = $this->request->request('page/d', 1);
        $limit = $this->request->request('limit/d', 20);

        // 限制區間：現在 ~ 未來 30 天
        $startTime = time();
        $endTime   = strtotime('+30 days');

        $query = model('Event')
            ->where('starttime', '>=', $startTime)
            ->where('starttime', '<', $endTime);

        if ($catId > 0) {
            $query->where('event_category_id', $catId);
        }

        // 分頁與排序
        $total = (clone $query)->count();
        $list = $query->page($page, $limit)
            ->order('starttime asc')
            // 直接輸出原始盤口與比分欄位
            ->field('id, event_category_id, starttime, guests, master, guests_refund, master_refund, bigscore, guests_score, master_score')
            ->select();

        $this->success('success', [
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
            'items' => $list
        ]);
    }
}
