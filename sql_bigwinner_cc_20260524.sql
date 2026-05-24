-- phpMyAdmin SQL Dump
-- version 4.9.5
-- https://www.phpmyadmin.net/
--
-- 主機： localhost
-- 產生時間： 2026 年 05 月 24 日 14:28
-- 伺服器版本： 5.7.43-log
-- PHP 版本： 7.3.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `sql_bigwinner_cc`
--

-- --------------------------------------------------------

--
-- 資料表結構 `activity`
--

CREATE TABLE `activity` (
  `id` int(11) NOT NULL,
  `title` varchar(20) NOT NULL,
  `content` text NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `admin`
--

CREATE TABLE `admin` (
  `id` int(10) UNSIGNED NOT NULL COMMENT 'ID',
  `username` varchar(20) DEFAULT '' COMMENT '用户名',
  `nickname` varchar(50) DEFAULT '' COMMENT '昵称',
  `password` varchar(32) DEFAULT '' COMMENT '密码',
  `salt` varchar(30) DEFAULT '' COMMENT '密码盐',
  `avatar` varchar(255) DEFAULT '' COMMENT '头像',
  `email` varchar(100) DEFAULT '' COMMENT '电子邮箱',
  `loginfailure` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '失败次数',
  `logintime` int(10) DEFAULT NULL COMMENT '登录时间',
  `loginip` varchar(50) DEFAULT NULL COMMENT '登录IP',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `token` varchar(59) DEFAULT '' COMMENT 'Session标识',
  `status` varchar(30) NOT NULL DEFAULT 'normal' COMMENT '状态'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='管理员表';

-- --------------------------------------------------------

--
-- 資料表結構 `admin_log`
--

CREATE TABLE `admin_log` (
  `id` int(10) UNSIGNED NOT NULL COMMENT 'ID',
  `admin_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `username` varchar(30) DEFAULT '' COMMENT '管理员名字',
  `url` varchar(1500) DEFAULT '' COMMENT '操作页面',
  `title` varchar(100) DEFAULT '' COMMENT '日志标题',
  `content` text NOT NULL COMMENT '内容',
  `ip` varchar(50) DEFAULT '' COMMENT 'IP',
  `useragent` varchar(255) DEFAULT '' COMMENT 'User-Agent',
  `createtime` int(10) DEFAULT NULL COMMENT '操作时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='管理员日志表';

-- --------------------------------------------------------

--
-- 資料表結構 `ad_banner`
--

CREATE TABLE `ad_banner` (
  `id` int(11) NOT NULL,
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '類型 0電腦 1手機',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '連結',
  `img` varchar(255) NOT NULL DEFAULT '' COMMENT '圖'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='廣告';

-- --------------------------------------------------------

--
-- 資料表結構 `analyst`
--

CREATE TABLE `analyst` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_free` int(11) DEFAULT NULL,
  `analyst_name` varchar(20) NOT NULL,
  `name_edit` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:可改名 0:不可改名',
  `avatar` text NOT NULL COMMENT '頭照',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '狀態 0:停用 1:啟用',
  `admin_id` int(11) DEFAULT NULL,
  `autopred` int(11) NOT NULL DEFAULT '1' COMMENT '自動預測',
  `free` int(11) NOT NULL DEFAULT '0' COMMENT '免費 0:否 1:是',
  `autopred_count` int(11) NOT NULL DEFAULT '5' COMMENT '自動預測次數',
  `autopred_today` int(11) NOT NULL DEFAULT '0' COMMENT '今日已預測',
  `seepred` int(11) NOT NULL DEFAULT '0' COMMENT '是否開通看預測',
  `seepred_count` int(11) NOT NULL DEFAULT '15' COMMENT '每日可看預測次數',
  `seepred_today` int(11) NOT NULL DEFAULT '0' COMMENT '今日已看預測次數',
  `lastpredtime` int(11) DEFAULT NULL COMMENT '上次補預測時間',
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `analyst_title`
--

CREATE TABLE `analyst_title` (
  `id` int(11) NOT NULL,
  `ecid` int(11) NOT NULL,
  `title` varchar(20) NOT NULL,
  `type` int(11) NOT NULL,
  `analyst_id` int(11) NOT NULL,
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `analyst_to_event_category`
--

CREATE TABLE `analyst_to_event_category` (
  `id` int(11) NOT NULL,
  `analyst_id` int(11) NOT NULL,
  `event_category_id` int(11) NOT NULL,
  `autopred_count` int(11) NOT NULL DEFAULT '5' COMMENT '自動預測數量',
  `autopred_today` int(11) NOT NULL DEFAULT '0' COMMENT '本日已預測量',
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `analyst_to_titletype`
--

CREATE TABLE `analyst_to_titletype` (
  `id` int(11) NOT NULL,
  `analyst_id` int(11) NOT NULL,
  `ecid` int(11) NOT NULL,
  `titletype` int(11) NOT NULL,
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `area`
--

CREATE TABLE `area` (
  `id` int(10) NOT NULL COMMENT 'ID',
  `pid` int(10) DEFAULT NULL COMMENT '父id',
  `shortname` varchar(100) DEFAULT NULL COMMENT '简称',
  `name` varchar(100) DEFAULT NULL COMMENT '名称',
  `mergename` varchar(255) DEFAULT NULL COMMENT '全称',
  `level` tinyint(4) DEFAULT NULL COMMENT '层级 0 1 2 省市区县',
  `pinyin` varchar(100) DEFAULT NULL COMMENT '拼音',
  `code` varchar(100) DEFAULT NULL COMMENT '长途区号',
  `zip` varchar(100) DEFAULT NULL COMMENT '邮编',
  `first` varchar(50) DEFAULT NULL COMMENT '首字母',
  `lng` varchar(100) DEFAULT NULL COMMENT '经度',
  `lat` varchar(100) DEFAULT NULL COMMENT '纬度'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='地区表';

-- --------------------------------------------------------

--
-- 資料表結構 `article`
--

CREATE TABLE `article` (
  `id` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `img` varchar(255) DEFAULT NULL COMMENT '照片',
  `content` mediumtext NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `cat_id` int(11) NOT NULL DEFAULT '0',
  `fav` int(11) NOT NULL DEFAULT '0' COMMENT '收藏人數',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '0:隱藏 1:正常 2:刪除',
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `article_cat`
--

CREATE TABLE `article_cat` (
  `id` int(11) NOT NULL,
  `cat_name` varchar(20) NOT NULL,
  `weigh` int(11) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '1' COMMENT '0後台 1通用',
  `status` int(11) NOT NULL DEFAULT '1',
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `article_fav`
--

CREATE TABLE `article_fav` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` int(11) NOT NULL DEFAULT '1' COMMENT '1一般 2專欄',
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `article_msg`
--

CREATE TABLE `article_msg` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `msg` text NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `article_read`
--

CREATE TABLE `article_read` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `attachment`
--

CREATE TABLE `attachment` (
  `id` int(20) UNSIGNED NOT NULL COMMENT 'ID',
  `admin_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '会员ID',
  `url` varchar(255) DEFAULT '' COMMENT '物理路径',
  `imagewidth` varchar(30) DEFAULT '' COMMENT '宽度',
  `imageheight` varchar(30) DEFAULT '' COMMENT '高度',
  `imagetype` varchar(30) DEFAULT '' COMMENT '图片类型',
  `imageframes` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '图片帧数',
  `filename` varchar(100) DEFAULT '' COMMENT '文件名称',
  `filesize` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '文件大小',
  `mimetype` varchar(100) DEFAULT '' COMMENT 'mime类型',
  `extparam` varchar(255) DEFAULT '' COMMENT '透传数据',
  `createtime` int(10) DEFAULT NULL COMMENT '创建日期',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `uploadtime` int(10) DEFAULT NULL COMMENT '上传时间',
  `storage` varchar(100) NOT NULL DEFAULT 'local' COMMENT '存储位置',
  `sha1` varchar(40) DEFAULT '' COMMENT '文件 sha1编码'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='附件表';

-- --------------------------------------------------------

--
-- 資料表結構 `auth_group`
--

CREATE TABLE `auth_group` (
  `id` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '父组别',
  `name` varchar(100) DEFAULT '' COMMENT '组名',
  `rules` text NOT NULL COMMENT '规则ID',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `status` varchar(30) DEFAULT '' COMMENT '状态'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='分组表';

-- --------------------------------------------------------

--
-- 資料表結構 `auth_group_access`
--

CREATE TABLE `auth_group_access` (
  `uid` int(10) UNSIGNED NOT NULL COMMENT '会员ID',
  `group_id` int(10) UNSIGNED NOT NULL COMMENT '级别ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='权限分组表';

-- --------------------------------------------------------

--
-- 資料表結構 `auth_rule`
--

CREATE TABLE `auth_rule` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` enum('menu','file') NOT NULL DEFAULT 'file' COMMENT 'menu为菜单,file为权限节点',
  `pid` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '父ID',
  `name` varchar(100) DEFAULT '' COMMENT '规则名称',
  `title` varchar(50) DEFAULT '' COMMENT '规则名称',
  `icon` varchar(50) DEFAULT '' COMMENT '图标',
  `condition` varchar(255) DEFAULT '' COMMENT '条件',
  `remark` varchar(255) DEFAULT '' COMMENT '备注',
  `ismenu` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '是否为菜单',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重',
  `status` varchar(30) DEFAULT '' COMMENT '状态'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='节点表';

-- --------------------------------------------------------

--
-- 資料表結構 `baccarat`
--

CREATE TABLE `baccarat` (
  `id` int(11) NOT NULL,
  `code` varchar(100) NOT NULL,
  `remark` varchar(255) NOT NULL COMMENT '備註',
  `debt` int(11) NOT NULL DEFAULT '0' COMMENT '欠債',
  `repay` int(11) NOT NULL DEFAULT '0' COMMENT '累積償還',
  `baccarat_order_id` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '0停用 1正常',
  `order_status` int(11) NOT NULL DEFAULT '1' COMMENT '0:未結清 1:已結清',
  `act` int(11) NOT NULL DEFAULT '0',
  `last_act_date` int(11) DEFAULT '0',
  `uid` varchar(255) DEFAULT NULL,
  `locked` int(11) NOT NULL DEFAULT '0',
  `img` text,
  `phone` varchar(20) DEFAULT NULL,
  `confirm` int(11) NOT NULL DEFAULT '0' COMMENT '0:未認證 1:待認證 2:已認證 3:認證失敗',
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `baccarat_order`
--

CREATE TABLE `baccarat_order` (
  `id` int(11) NOT NULL,
  `baccarat_id` int(11) NOT NULL,
  `request` text COMMENT '回調參數',
  `result` text NOT NULL COMMENT '建單回應',
  `order_no` varchar(255) NOT NULL,
  `trans_order_no` varchar(255) NOT NULL,
  `amount` int(11) NOT NULL,
  `msg` text NOT NULL,
  `create_time` varchar(50) NOT NULL,
  `end_time` varchar(50) NOT NULL,
  `create_time_strtotime` int(11) NOT NULL,
  `end_time_strtotime` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `bank_card_number` varchar(100) NOT NULL,
  `bank_name` varchar(50) NOT NULL,
  `bank_zhihang` varchar(50) NOT NULL,
  `checkout_url` text NOT NULL,
  `trade_type` int(11) NOT NULL DEFAULT '1' COMMENT '1:金流1 2:藍新 3:金流3',
  `notify_msg` text NOT NULL,
  `payment_type` varchar(255) NOT NULL,
  `pay_time` int(11) NOT NULL,
  `pay_bank_code` varchar(255) NOT NULL,
  `payer_account_5_code` varchar(255) NOT NULL,
  `code_no` varchar(255) NOT NULL,
  `store_type` varchar(255) NOT NULL,
  `store_ID` varchar(255) NOT NULL,
  `barcode_1` varchar(255) NOT NULL,
  `barcode_2` varchar(255) NOT NULL,
  `barcode_3` varchar(255) NOT NULL,
  `pay_store` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '0:未付款 1:已付款 2:建單失敗 3:超時或取消',
  `ip` varchar(100) NOT NULL,
  `createtime` int(11) NOT NULL COMMENT '創建時間',
  `updatetime` int(11) NOT NULL COMMENT '修改日期'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='推播紀錄';

-- --------------------------------------------------------

--
-- 資料表結構 `category`
--

CREATE TABLE `category` (
  `id` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '父ID',
  `type` varchar(30) DEFAULT '' COMMENT '栏目类型',
  `name` varchar(30) DEFAULT '',
  `nickname` varchar(50) DEFAULT '',
  `flag` set('hot','index','recommend') DEFAULT '',
  `image` varchar(100) DEFAULT '' COMMENT '图片',
  `keywords` varchar(255) DEFAULT '' COMMENT '关键字',
  `description` varchar(255) DEFAULT '' COMMENT '描述',
  `diyname` varchar(30) DEFAULT '' COMMENT '自定义名称',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重',
  `status` varchar(30) DEFAULT '' COMMENT '状态'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='分类表';

-- --------------------------------------------------------

--
-- 資料表結構 `config`
--

CREATE TABLE `config` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(30) DEFAULT '' COMMENT '变量名',
  `group` varchar(30) DEFAULT '' COMMENT '分组',
  `title` varchar(100) DEFAULT '' COMMENT '变量标题',
  `tip` varchar(100) DEFAULT '' COMMENT '变量描述',
  `type` varchar(30) DEFAULT '' COMMENT '类型:string,text,int,bool,array,datetime,date,file',
  `value` text COMMENT '变量值',
  `content` text COMMENT '变量字典数据',
  `rule` varchar(100) DEFAULT '' COMMENT '验证规则',
  `extend` varchar(255) DEFAULT '' COMMENT '扩展属性',
  `setting` varchar(255) DEFAULT '' COMMENT '配置'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统配置';

-- --------------------------------------------------------

--
-- 資料表結構 `dotnet`
--

CREATE TABLE `dotnet` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `godarticle_id` int(11) NOT NULL,
  `godarticle_user_id` int(11) NOT NULL,
  `point` int(11) NOT NULL,
  `real_point` int(11) NOT NULL,
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `ems`
--

CREATE TABLE `ems` (
  `id` int(10) UNSIGNED NOT NULL COMMENT 'ID',
  `event` varchar(30) DEFAULT '' COMMENT '事件',
  `email` varchar(100) DEFAULT '' COMMENT '邮箱',
  `code` varchar(10) DEFAULT '' COMMENT '验证码',
  `times` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '验证次数',
  `ip` varchar(30) DEFAULT '' COMMENT 'IP',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='邮箱验证码表';

-- --------------------------------------------------------

--
-- 資料表結構 `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `event_category_id` int(11) NOT NULL,
  `starttime` int(11) DEFAULT NULL COMMENT '開賽時間',
  `master` varchar(20) DEFAULT NULL COMMENT '主場',
  `master_score` varchar(10) DEFAULT NULL,
  `master_refund` varchar(20) DEFAULT '' COMMENT '主場讓分',
  `guests` varchar(20) DEFAULT NULL COMMENT '客場',
  `guests_score` varchar(10) DEFAULT NULL,
  `guests_refund` varchar(20) DEFAULT '' COMMENT '客場讓分',
  `bigscore` varchar(20) NOT NULL COMMENT '大小分',
  `pred` int(11) NOT NULL DEFAULT '0' COMMENT '預測數量',
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '完賽檢查 0:未確認 1:已確認',
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `event_category`
--

CREATE TABLE `event_category` (
  `id` int(11) NOT NULL,
  `url` varchar(100) NOT NULL,
  `title` varchar(50) NOT NULL,
  `game_category` varchar(10) DEFAULT NULL,
  `analyst` int(11) NOT NULL DEFAULT '0' COMMENT '分析師數量',
  `rankrule` int(11) NOT NULL DEFAULT '0' COMMENT '排行最小預測數量',
  `showhome1` int(11) DEFAULT NULL COMMENT '開始顯示時間',
  `showhome2` int(11) DEFAULT NULL COMMENT '結束顯示時間',
  `status` int(11) NOT NULL DEFAULT '1',
  `lastcron` int(11) NOT NULL DEFAULT '0',
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `event_param`
--

CREATE TABLE `event_param` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `master_refund` varchar(20) NOT NULL,
  `guests_refund` varchar(20) NOT NULL,
  `bigscore` varchar(20) NOT NULL,
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `godarticle`
--

CREATE TABLE `godarticle` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `cover_img` varchar(255) DEFAULT NULL COMMENT '封面',
  `video_url` varchar(255) DEFAULT NULL,
  `content` mediumtext NOT NULL,
  `cat_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `fav` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '0:待審核 1:審核通過 2:拒絕刊登 3:刪除',
  `reason` text,
  `views` int(11) NOT NULL DEFAULT '0',
  `lastviews` int(11) NOT NULL DEFAULT '0',
  `god_type` int(11) NOT NULL DEFAULT '1' COMMENT '0神人專欄 1根據godtype表',
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `god_type`
--

CREATE TABLE `god_type` (
  `id` int(11) NOT NULL,
  `type_name` varchar(20) NOT NULL,
  `weigh` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- 資料表結構 `line_pred_code`
--

CREATE TABLE `line_pred_code` (
  `id` int(11) NOT NULL,
  `code` varchar(100) NOT NULL COMMENT '開通代碼',
  `analyst_id` int(11) DEFAULT NULL COMMENT '開通者',
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '0未使用 1已使用',
  `remark` varchar(50) NOT NULL DEFAULT '' COMMENT '備註'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='開通看預測代碼';

-- --------------------------------------------------------

--
-- 資料表結構 `line_rich_menus`
--

CREATE TABLE `line_rich_menus` (
  `id` int(11) NOT NULL,
  `rich_menu_id` varchar(500) NOT NULL COMMENT 'richMenuId',
  `rich_menu_name` varchar(50) NOT NULL,
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `line_webhook_log`
--

CREATE TABLE `line_webhook_log` (
  `id` int(11) NOT NULL,
  `request_to_json` mediumtext,
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `lottery`
--

CREATE TABLE `lottery` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `content` text NOT NULL,
  `igurl` text,
  `igcode` varchar(20) DEFAULT NULL,
  `item` varchar(100) NOT NULL,
  `win` int(11) NOT NULL,
  `tag` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '-1' COMMENT '-1:未審核 0:未抽獎 1:已抽獎 2:取消 3:刪除',
  `open` int(11) NOT NULL DEFAULT '0' COMMENT '0:未開獎 1:計算中 2:已扣款 3:已開獎 4:點數不足或是開獎失敗',
  `stop_res` text NOT NULL COMMENT '中止原因',
  `cost_point` int(11) NOT NULL DEFAULT '0' COMMENT '0:未扣點 1:已扣點',
  `msg` int(11) DEFAULT NULL,
  `starttime` int(11) NOT NULL COMMENT '開始抽的時間(time)',
  `needtime` int(11) NOT NULL COMMENT '預計所需時間(秒)',
  `winstr` longtext,
  `winimg` longtext NOT NULL,
  `wintime` int(11) DEFAULT NULL,
  `mwinstr` longtext,
  `mwinimg` longtext,
  `mwinimg_loc` longtext NOT NULL,
  `user_id` int(11) NOT NULL,
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `lottery_img`
--

CREATE TABLE `lottery_img` (
  `id` int(11) NOT NULL,
  `img` text NOT NULL,
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `lucky_share`
--

CREATE TABLE `lucky_share` (
  `id` int(11) NOT NULL,
  `img` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `hide` int(11) NOT NULL DEFAULT '0' COMMENT '暱稱 0隱藏 1顯示',
  `user_id` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '狀態 0隱藏 1顯示',
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `order_point`
--

CREATE TABLE `order_point` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request` text COMMENT '回調參數',
  `result` text NOT NULL COMMENT '建單回應',
  `order_no` varchar(255) NOT NULL,
  `trans_order_no` varchar(255) NOT NULL,
  `amount` int(11) NOT NULL,
  `point` int(11) NOT NULL,
  `msg` text NOT NULL,
  `create_time` varchar(50) NOT NULL,
  `end_time` varchar(50) NOT NULL,
  `create_time_strtotime` int(11) NOT NULL,
  `end_time_strtotime` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `bank_card_number` varchar(100) NOT NULL,
  `bank_name` varchar(50) NOT NULL,
  `bank_zhihang` varchar(50) NOT NULL,
  `checkout_url` text NOT NULL,
  `trade_type` int(11) NOT NULL DEFAULT '1' COMMENT '1:金流1 2:藍新 3:金流3',
  `notify_msg` text NOT NULL,
  `payment_type` varchar(255) NOT NULL,
  `pay_time` int(11) NOT NULL,
  `pay_bank_code` varchar(255) NOT NULL,
  `payer_account_5_code` varchar(255) NOT NULL,
  `code_no` varchar(255) NOT NULL,
  `store_type` varchar(255) NOT NULL,
  `store_ID` varchar(255) NOT NULL,
  `barcode_1` varchar(255) NOT NULL,
  `barcode_2` varchar(255) NOT NULL,
  `barcode_3` varchar(255) NOT NULL,
  `pay_store` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '0:未付款 1:已付款 2:建單失敗 3:超時或取消',
  `ip` varchar(100) NOT NULL,
  `createtime` int(11) NOT NULL COMMENT '創建時間',
  `updatetime` int(11) NOT NULL COMMENT '修改日期'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='推播紀錄';

-- --------------------------------------------------------

--
-- 資料表結構 `placard`
--

CREATE TABLE `placard` (
  `id` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `point_item`
--

CREATE TABLE `point_item` (
  `id` int(11) NOT NULL,
  `point` int(11) NOT NULL,
  `price` int(11) NOT NULL,
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `point_log`
--

CREATE TABLE `point_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL COMMENT '更變數量',
  `before` int(11) NOT NULL COMMENT '更變前數量',
  `after` int(11) NOT NULL COMMENT '更變後數量',
  `memo` text NOT NULL COMMENT '備註',
  `createtime` int(11) NOT NULL COMMENT '建立時間',
  `updatetime` int(11) NOT NULL COMMENT '更新時間'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `pred`
--

CREATE TABLE `pred` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `analyst_id` int(11) NOT NULL,
  `winteam` int(11) DEFAULT NULL COMMENT '1:主場 0:客場',
  `master_refund` varchar(20) DEFAULT '',
  `guests_refund` varchar(20) DEFAULT '',
  `bigsmall` int(11) DEFAULT NULL COMMENT '1:大 0:小',
  `bigscore` varchar(20) DEFAULT NULL,
  `isauto` int(11) NOT NULL DEFAULT '0' COMMENT '是否是自動預測產生',
  `isreadwin` int(11) NOT NULL DEFAULT '0' COMMENT '已被看過預測',
  `isreadbig` int(11) NOT NULL DEFAULT '0' COMMENT '已被看過大小',
  `isread` int(11) NOT NULL DEFAULT '0' COMMENT '0未被查看 1被看過',
  `pred_type` int(11) NOT NULL DEFAULT '1' COMMENT '1讓分 2大小',
  `master_score` varchar(10) DEFAULT NULL,
  `guests_score` varchar(10) DEFAULT NULL,
  `comply` int(11) NOT NULL DEFAULT '0' COMMENT '預測結果 0:未確認 1:贏 2:輸 3:和 -1:無效賽事',
  `result_ratio` int(11) NOT NULL DEFAULT '0' COMMENT '輸贏比例(%)',
  `admin_id` int(11) DEFAULT NULL,
  `predtime` int(11) DEFAULT NULL COMMENT '預測時間',
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `rank`
--

CREATE TABLE `rank` (
  `id` int(11) NOT NULL,
  `event_category_id` int(11) NOT NULL DEFAULT '0' COMMENT '排行類型 0=總排行',
  `rtime1` int(11) NOT NULL COMMENT '排行起始時間',
  `rtime2` int(11) NOT NULL COMMENT '排行結束時間',
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `rank_content`
--

CREATE TABLE `rank_content` (
  `id` int(11) NOT NULL,
  `rank_id` int(11) NOT NULL,
  `analyst_id` int(11) NOT NULL,
  `winrate` decimal(10,2) NOT NULL COMMENT '勝率',
  `win` int(11) NOT NULL DEFAULT '0' COMMENT '勝',
  `lose` int(11) NOT NULL DEFAULT '0' COMMENT '敗',
  `rank` int(11) NOT NULL COMMENT '名次',
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `request_log`
--

CREATE TABLE `request_log` (
  `id` int(11) NOT NULL,
  `request` text,
  `ip` varchar(100) NOT NULL,
  `createtime` int(11) NOT NULL COMMENT '創建時間',
  `updatetime` int(11) NOT NULL COMMENT '修改日期'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='推播紀錄';

-- --------------------------------------------------------

--
-- 資料表結構 `service`
--

CREATE TABLE `service` (
  `id` int(11) NOT NULL,
  `nickname` varchar(10) NOT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `lastget` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '1',
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `sms`
--

CREATE TABLE `sms` (
  `id` int(10) UNSIGNED NOT NULL COMMENT 'ID',
  `event` varchar(30) DEFAULT '' COMMENT '事件',
  `mobile` varchar(20) DEFAULT '' COMMENT '手机号',
  `code` varchar(10) DEFAULT '' COMMENT '验证码',
  `times` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '验证次数',
  `ip` varchar(30) DEFAULT '' COMMENT 'IP',
  `createtime` int(10) UNSIGNED DEFAULT '0' COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='短信验证码表';

-- --------------------------------------------------------

--
-- 資料表結構 `test`
--

CREATE TABLE `test` (
  `id` int(10) UNSIGNED NOT NULL COMMENT 'ID',
  `admin_id` int(10) NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `category_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '分类ID(单选)',
  `category_ids` varchar(100) NOT NULL COMMENT '分类ID(多选)',
  `week` enum('monday','tuesday','wednesday') NOT NULL COMMENT '星期(单选):monday=星期一,tuesday=星期二,wednesday=星期三',
  `flag` set('hot','index','recommend') DEFAULT '' COMMENT '标志(多选):hot=热门,index=首页,recommend=推荐',
  `genderdata` enum('male','female') NOT NULL DEFAULT 'male' COMMENT '性别(单选):male=男,female=女',
  `hobbydata` set('music','reading','swimming') NOT NULL COMMENT '爱好(多选):music=音乐,reading=读书,swimming=游泳',
  `title` varchar(50) DEFAULT '' COMMENT '标题',
  `content` text NOT NULL COMMENT '内容',
  `image` varchar(100) DEFAULT '' COMMENT '图片',
  `images` varchar(1500) DEFAULT '' COMMENT '图片组',
  `attachfile` varchar(100) DEFAULT '' COMMENT '附件',
  `keywords` varchar(100) DEFAULT '' COMMENT '关键字',
  `description` varchar(255) DEFAULT '' COMMENT '描述',
  `city` varchar(100) DEFAULT '' COMMENT '省市',
  `json` varchar(255) DEFAULT NULL COMMENT '配置:key=名称,value=值',
  `price` float(10,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '价格',
  `views` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '点击',
  `startdate` date DEFAULT NULL COMMENT '开始日期',
  `activitytime` datetime DEFAULT NULL COMMENT '活动时间(datetime)',
  `year` year(4) DEFAULT NULL COMMENT '年',
  `times` time DEFAULT NULL COMMENT '时间',
  `refreshtime` int(10) DEFAULT NULL COMMENT '刷新时间(int)',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `deletetime` int(10) DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重',
  `switch` tinyint(1) NOT NULL DEFAULT '0' COMMENT '开关',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态',
  `state` enum('0','1','2') NOT NULL DEFAULT '1' COMMENT '状态值:0=禁用,1=正常,2=推荐'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='测试表';

-- --------------------------------------------------------

--
-- 資料表結構 `user`
--

CREATE TABLE `user` (
  `id` int(10) UNSIGNED NOT NULL COMMENT 'ID',
  `group_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '组别ID',
  `isgod` int(11) NOT NULL DEFAULT '0' COMMENT '是否神人',
  `code` varchar(10) DEFAULT NULL,
  `bid` varchar(20) DEFAULT NULL,
  `line_user_id` varchar(500) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `get_pred_time` int(11) DEFAULT NULL COMMENT '最後使用免費次數的時間',
  `pred` int(11) NOT NULL DEFAULT '1' COMMENT '預測限額',
  `pred2` int(11) NOT NULL DEFAULT '0' COMMENT '剩餘次數',
  `ptime1` int(11) DEFAULT NULL COMMENT '限額區間起始',
  `ptime2` int(11) DEFAULT NULL COMMENT '限額區間結束',
  `rich_menu_id` int(11) DEFAULT '1',
  `username` varchar(32) DEFAULT '' COMMENT '用户名',
  `nickname` varchar(50) DEFAULT '' COMMENT '昵称',
  `password` varchar(32) DEFAULT '' COMMENT '密码',
  `salt` varchar(30) DEFAULT '' COMMENT '密码盐',
  `email` varchar(100) DEFAULT '' COMMENT '电子邮箱',
  `mobile` varchar(11) DEFAULT '' COMMENT '手机号',
  `avatar` varchar(255) DEFAULT '' COMMENT '头像',
  `level` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '等级',
  `gender` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '性别',
  `birthday` date DEFAULT NULL COMMENT '生日',
  `bio` varchar(100) DEFAULT '' COMMENT '格言',
  `point` int(11) NOT NULL DEFAULT '0' COMMENT '點數',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '余额',
  `score` int(10) NOT NULL DEFAULT '0' COMMENT '积分',
  `successions` int(10) UNSIGNED NOT NULL DEFAULT '1' COMMENT '连续登录天数',
  `maxsuccessions` int(10) UNSIGNED NOT NULL DEFAULT '1' COMMENT '最大连续登录天数',
  `prevtime` int(10) DEFAULT NULL COMMENT '上次登录时间',
  `logintime` int(10) DEFAULT NULL COMMENT '登录时间',
  `loginip` varchar(50) DEFAULT '' COMMENT '登录IP',
  `loginfailure` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '失败次数',
  `joinip` varchar(50) DEFAULT '' COMMENT '加入IP',
  `jointime` int(10) DEFAULT NULL COMMENT '加入时间',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `token` varchar(50) DEFAULT '' COMMENT 'Token',
  `status` varchar(30) DEFAULT '' COMMENT '状态',
  `admin_id` int(11) DEFAULT NULL,
  `verification` varchar(255) DEFAULT '' COMMENT '验证'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='会员表';

-- --------------------------------------------------------

--
-- 資料表結構 `user_free`
--

CREATE TABLE `user_free` (
  `id` int(11) NOT NULL,
  `line_user_id` varchar(50) NOT NULL,
  `service_id` int(11) NOT NULL DEFAULT '0',
  `get_pred_time` int(11) DEFAULT NULL,
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `user_group`
--

CREATE TABLE `user_group` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) DEFAULT '' COMMENT '组名',
  `rules` text COMMENT '权限节点',
  `createtime` int(10) DEFAULT NULL COMMENT '添加时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `status` enum('normal','hidden') DEFAULT NULL COMMENT '状态'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='会员组表';

-- --------------------------------------------------------

--
-- 資料表結構 `user_money_log`
--

CREATE TABLE `user_money_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '会员ID',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变更余额',
  `before` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变更前余额',
  `after` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变更后余额',
  `memo` varchar(255) DEFAULT '' COMMENT '备注',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='会员余额变动表';

-- --------------------------------------------------------

--
-- 資料表結構 `user_notify`
--

CREATE TABLE `user_notify` (
  `id` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `url` varchar(255) NOT NULL,
  `read` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL,
  `placard_id` int(11) NOT NULL DEFAULT '0',
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `user_rule`
--

CREATE TABLE `user_rule` (
  `id` int(10) UNSIGNED NOT NULL,
  `pid` int(10) DEFAULT NULL COMMENT '父ID',
  `name` varchar(50) DEFAULT NULL COMMENT '名称',
  `title` varchar(50) DEFAULT '' COMMENT '标题',
  `remark` varchar(100) DEFAULT NULL COMMENT '备注',
  `ismenu` tinyint(1) DEFAULT NULL COMMENT '是否菜单',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `weigh` int(10) DEFAULT '0' COMMENT '权重',
  `status` enum('normal','hidden') DEFAULT NULL COMMENT '状态'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='会员规则表';

-- --------------------------------------------------------

--
-- 資料表結構 `user_score_log`
--

CREATE TABLE `user_score_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '会员ID',
  `score` int(10) NOT NULL DEFAULT '0' COMMENT '变更积分',
  `before` int(10) NOT NULL DEFAULT '0' COMMENT '变更前积分',
  `after` int(10) NOT NULL DEFAULT '0' COMMENT '变更后积分',
  `memo` varchar(255) DEFAULT '' COMMENT '备注',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='会员积分变动表';

-- --------------------------------------------------------

--
-- 資料表結構 `user_token`
--

CREATE TABLE `user_token` (
  `token` varchar(50) NOT NULL COMMENT 'Token',
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '会员ID',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `expiretime` int(10) DEFAULT NULL COMMENT '过期时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='会员Token表';

-- --------------------------------------------------------

--
-- 資料表結構 `user_to_analyst`
--

CREATE TABLE `user_to_analyst` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT '會員',
  `analyst_id` int(11) NOT NULL COMMENT '分析師',
  `cat_id` int(11) NOT NULL COMMENT '類別',
  `point` int(11) NOT NULL COMMENT '花費點數',
  `buydate` int(11) NOT NULL COMMENT '購買的日期',
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='會員購買分析師';

-- --------------------------------------------------------

--
-- 資料表結構 `user_to_pred`
--

CREATE TABLE `user_to_pred` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `userfree_id` int(11) DEFAULT NULL,
  `pred_id` int(11) NOT NULL,
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `version`
--

CREATE TABLE `version` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `oldversion` varchar(30) DEFAULT '' COMMENT '旧版本号',
  `newversion` varchar(30) DEFAULT '' COMMENT '新版本号',
  `packagesize` varchar(30) DEFAULT '' COMMENT '包大小',
  `content` varchar(500) DEFAULT '' COMMENT '升级内容',
  `downloadurl` varchar(255) DEFAULT '' COMMENT '下载地址',
  `enforce` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '强制更新',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重',
  `status` varchar(30) DEFAULT '' COMMENT '状态'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='版本表';

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `activity`
--
ALTER TABLE `activity`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`) USING BTREE;

--
-- 資料表索引 `admin_log`
--
ALTER TABLE `admin_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`username`);

--
-- 資料表索引 `ad_banner`
--
ALTER TABLE `ad_banner`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `analyst`
--
ALTER TABLE `analyst`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_analyst_status_autopred` (`status`,`autopred`),
  ADD KEY `idx_analyst_userfree` (`user_free`);

--
-- 資料表索引 `analyst_title`
--
ALTER TABLE `analyst_title`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `analyst_to_event_category`
--
ALTER TABLE `analyst_to_event_category`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_atc_eventcat_analyst` (`event_category_id`,`analyst_id`);

--
-- 資料表索引 `analyst_to_titletype`
--
ALTER TABLE `analyst_to_titletype`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `area`
--
ALTER TABLE `area`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pid` (`pid`);

--
-- 資料表索引 `article`
--
ALTER TABLE `article`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `article_cat`
--
ALTER TABLE `article_cat`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `article_fav`
--
ALTER TABLE `article_fav`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `article_msg`
--
ALTER TABLE `article_msg`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `article_read`
--
ALTER TABLE `article_read`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `attachment`
--
ALTER TABLE `attachment`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `auth_group`
--
ALTER TABLE `auth_group`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `auth_group_access`
--
ALTER TABLE `auth_group_access`
  ADD UNIQUE KEY `uid_group_id` (`uid`,`group_id`),
  ADD KEY `uid` (`uid`),
  ADD KEY `group_id` (`group_id`);

--
-- 資料表索引 `auth_rule`
--
ALTER TABLE `auth_rule`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`) USING BTREE,
  ADD KEY `pid` (`pid`),
  ADD KEY `weigh` (`weigh`);

--
-- 資料表索引 `baccarat`
--
ALTER TABLE `baccarat`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `baccarat_order`
--
ALTER TABLE `baccarat_order`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`),
  ADD KEY `weigh` (`weigh`,`id`),
  ADD KEY `pid` (`pid`);

--
-- 資料表索引 `config`
--
ALTER TABLE `config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- 資料表索引 `dotnet`
--
ALTER TABLE `dotnet`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `ems`
--
ALTER TABLE `ems`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- 資料表索引 `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_cat_start_spread` (`event_category_id`,`starttime`,`master_refund`,`guests_refund`),
  ADD KEY `idx_event_cat_start_total` (`event_category_id`,`starttime`,`bigscore`),
  ADD KEY `idx_event_starttime` (`starttime`),
  ADD KEY `idx_event_cat_start` (`event_category_id`,`starttime`);

--
-- 資料表索引 `event_category`
--
ALTER TABLE `event_category`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_eventcategory_status` (`status`);

--
-- 資料表索引 `event_param`
--
ALTER TABLE `event_param`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `godarticle`
--
ALTER TABLE `godarticle`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `god_type`
--
ALTER TABLE `god_type`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- 資料表索引 `line_pred_code`
--
ALTER TABLE `line_pred_code`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- 資料表索引 `line_rich_menus`
--
ALTER TABLE `line_rich_menus`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `line_webhook_log`
--
ALTER TABLE `line_webhook_log`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `lottery`
--
ALTER TABLE `lottery`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `lottery_img`
--
ALTER TABLE `lottery_img`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `lucky_share`
--
ALTER TABLE `lucky_share`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `order_point`
--
ALTER TABLE `order_point`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `placard`
--
ALTER TABLE `placard`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `point_item`
--
ALTER TABLE `point_item`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `point_log`
--
ALTER TABLE `point_log`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `pred`
--
ALTER TABLE `pred`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pred_event_type_analyst` (`event_id`,`pred_type`,`analyst_id`),
  ADD KEY `idx_pred_analyst_comply_event` (`analyst_id`,`comply`,`event_id`),
  ADD KEY `idx_pred_analyst_event` (`analyst_id`,`event_id`);

--
-- 資料表索引 `rank`
--
ALTER TABLE `rank`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `rank_content`
--
ALTER TABLE `rank_content`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `request_log`
--
ALTER TABLE `request_log`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `service`
--
ALTER TABLE `service`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `sms`
--
ALTER TABLE `sms`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `test`
--
ALTER TABLE `test`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`),
  ADD KEY `idx_user_line_status` (`line_user_id`,`status`);

--
-- 資料表索引 `user_free`
--
ALTER TABLE `user_free`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_userfree_line` (`line_user_id`);

--
-- 資料表索引 `user_group`
--
ALTER TABLE `user_group`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `user_money_log`
--
ALTER TABLE `user_money_log`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `user_notify`
--
ALTER TABLE `user_notify`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `user_rule`
--
ALTER TABLE `user_rule`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `user_score_log`
--
ALTER TABLE `user_score_log`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `user_token`
--
ALTER TABLE `user_token`
  ADD PRIMARY KEY (`token`);

--
-- 資料表索引 `user_to_analyst`
--
ALTER TABLE `user_to_analyst`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `user_to_pred`
--
ALTER TABLE `user_to_pred`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `version`
--
ALTER TABLE `version`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `activity`
--
ALTER TABLE `activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `admin_log`
--
ALTER TABLE `admin_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `ad_banner`
--
ALTER TABLE `ad_banner`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `analyst`
--
ALTER TABLE `analyst`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `analyst_title`
--
ALTER TABLE `analyst_title`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `analyst_to_event_category`
--
ALTER TABLE `analyst_to_event_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `analyst_to_titletype`
--
ALTER TABLE `analyst_to_titletype`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `area`
--
ALTER TABLE `area`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `article`
--
ALTER TABLE `article`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `article_cat`
--
ALTER TABLE `article_cat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `article_fav`
--
ALTER TABLE `article_fav`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `article_msg`
--
ALTER TABLE `article_msg`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `article_read`
--
ALTER TABLE `article_read`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `attachment`
--
ALTER TABLE `attachment`
  MODIFY `id` int(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `auth_group`
--
ALTER TABLE `auth_group`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `auth_rule`
--
ALTER TABLE `auth_rule`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `baccarat`
--
ALTER TABLE `baccarat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `baccarat_order`
--
ALTER TABLE `baccarat_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `category`
--
ALTER TABLE `category`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `config`
--
ALTER TABLE `config`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `dotnet`
--
ALTER TABLE `dotnet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `ems`
--
ALTER TABLE `ems`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `event_category`
--
ALTER TABLE `event_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `event_param`
--
ALTER TABLE `event_param`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `godarticle`
--
ALTER TABLE `godarticle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `god_type`
--
ALTER TABLE `god_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `line_pred_code`
--
ALTER TABLE `line_pred_code`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `line_rich_menus`
--
ALTER TABLE `line_rich_menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `line_webhook_log`
--
ALTER TABLE `line_webhook_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `lottery`
--
ALTER TABLE `lottery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `lottery_img`
--
ALTER TABLE `lottery_img`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `lucky_share`
--
ALTER TABLE `lucky_share`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `order_point`
--
ALTER TABLE `order_point`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `placard`
--
ALTER TABLE `placard`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `point_item`
--
ALTER TABLE `point_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `point_log`
--
ALTER TABLE `point_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `pred`
--
ALTER TABLE `pred`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `rank`
--
ALTER TABLE `rank`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `rank_content`
--
ALTER TABLE `rank_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `request_log`
--
ALTER TABLE `request_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `service`
--
ALTER TABLE `service`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `sms`
--
ALTER TABLE `sms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `test`
--
ALTER TABLE `test`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user`
--
ALTER TABLE `user`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user_free`
--
ALTER TABLE `user_free`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user_group`
--
ALTER TABLE `user_group`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user_money_log`
--
ALTER TABLE `user_money_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user_notify`
--
ALTER TABLE `user_notify`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user_rule`
--
ALTER TABLE `user_rule`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user_score_log`
--
ALTER TABLE `user_score_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user_to_analyst`
--
ALTER TABLE `user_to_analyst`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user_to_pred`
--
ALTER TABLE `user_to_pred`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `version`
--
ALTER TABLE `version`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
