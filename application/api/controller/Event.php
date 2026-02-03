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
     * 賽事列表 API
     * @param int $cid 分類ID
     * @param string $start 開始日期 (YYYY-MM-DD)
     * @param string $end 結束日期 (YYYY-MM-DD)
     */
    public function getEventList()
    {
        $cid = $this->request->get('cid/d', 0);
        $start_date = $this->request->get('start', '');
        $end_date = $this->request->get('end', '');

        // --- 時間判斷邏輯 ---
        $now = time();
        $max_limit = strtotime('+30 days'); // 最多查 30 天內

        // 設定起始時間：如果有傳參數就用參數，否則預設現在
        $start_ts = !empty($start_date) ? strtotime($start_date . " 00:00:00") : $now;
        
        // 設定結束時間：如果有傳參數就用參數，否則預設 $start_ts + 3 天
        if (!empty($end_date)) {
            $end_ts = strtotime($end_date . " 23:59:59");
        } else {
            $end_ts = strtotime(date('Y-m-d 23:59:59', $start_ts) . " +2 days");
        }

        // 強制約束：不能超過未來 30 天
        if ($end_ts > $max_limit) $end_ts = $max_limit;
        if ($start_ts < strtotime('-1 day')) $start_ts = strtotime(date('Y-m-d 00:00:00')); // 不給查太舊的

        // --- 構建查詢 ---
        $where = [];
        $where['starttime'] = ['between', [$start_ts, $end_ts]];
        
        if ($cid != 0) {
            $where['event_category_id'] = $cid;
        }

        // 使用 paginate，每頁 25 筆 (會自動抓取 url 裡的 page 參數)
        $mEvents = model('Event')
            ->where($where)
            ->field('id, event_category_id, starttime, guests, master, guests_refund, master_refund, bigscore, guests_score, master_score')
            ->order('starttime', 'asc')
            ->paginate(25);

        // 整理輸出資料
        $result = [
            'total'        => $mEvents->total(),
            'current_page' => $mEvents->currentPage(),
            'last_page'    => $mEvents->lastPage(),
            'per_page'     => $mEvents->listRows(),
            'search_range' => [
                'start' => date('Y-m-d H:i:s', $start_ts),
                'end'   => date('Y-m-d H:i:s', $end_ts)
            ],
            'items'        => $mEvents->items() // 這裡就是純資料陣列
        ];

        $this->success('success', $result);
    }
}
