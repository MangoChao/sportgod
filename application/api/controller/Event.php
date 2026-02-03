<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Log;
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
     * 賽事列表 API (Timestamp 版)
     */
    public function getEventList()
    {
        // 接收參數 (d 代表強制轉為整數)
        $cid = $this->request->get('cid/d', 0);
        $start_ts = $this->request->get('start/d', 0);
        $end_ts = $this->request->get('end/d', 0);
        $page = $this->request->get('page/d', 1);

        $now = time();
        $max_limit = strtotime('+30 days');

        // --- 時間邏輯處理 ---
        
        // 如果沒傳 start，預設為「今天凌晨 00:00:00」
        if ($start_ts <= 0) {
            $start_ts = strtotime(date('Y-m-d 00:00:00'));
        }

        // 如果沒傳 end，預設為「start 往後推 3 天的深夜」
        if ($end_ts <= 0) {
            $end_ts = strtotime(date('Y-m-d 23:59:59', $start_ts) . " +2 days");
        }

        // 安全機制：強制約束在未來 30 天內
        if ($end_ts > $max_limit) $end_ts = $max_limit;

        // --- 構建查詢 ---
        $where = [];
        $where['starttime'] = ['between', [$start_ts, $end_ts]];
        
        if ($cid != 0) {
            $where['event_category_id'] = $cid;
        }

        $mEvents = model('Event')
            ->where($where)
            ->field('id, event_category_id, starttime, guests, master, guests_refund, master_refund, bigscore, guests_score, master_score')
            ->order('starttime', 'asc')
            ->paginate(25, false, ['page' => $page]);

        // 整理輸出資料
        $result = [
            'total'        => $mEvents->total(),
            'current_page' => $mEvents->currentPage(),
            'last_page'    => $mEvents->lastPage(),
            'has_more'     => $mEvents->currentPage() < $mEvents->lastPage(),
            'time_range'   => [
                'start' => $start_ts . ' (' . date('Y-m-d H:i:s', $start_ts) . ')',
                'end'   => $end_ts . ' (' . date('Y-m-d H:i:s', $end_ts) . ')'
            ],
            'items'        => $mEvents->items()
        ];

        $this->success('success', $result);
    }
}
