-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- 主機： localhost
-- 產生時間： 2021 年 08 月 16 日 03:00
-- 伺服器版本： 5.7.34-log-cll-lve
-- PHP 版本： 7.4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `sportgod`
--

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

--
-- 傾印資料表的資料 `admin`
--

INSERT INTO `admin` (`id`, `username`, `nickname`, `password`, `salt`, `avatar`, `email`, `loginfailure`, `logintime`, `loginip`, `createtime`, `updatetime`, `token`, `status`) VALUES
(1, 'sysadmin', 'RD', '739a805f8299be8535e07272b55c06bf', 'd2f9c8', '/assets/img/avatar.png', 'sysadmin@admin.com', 0, 1628847570, '111.254.221.106', 1492186163, 1628847570, 'ccd1bb8f-09f1-47a2-86c1-481f078858ea', 'normal');

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

--
-- 傾印資料表的資料 `admin_log`
--

INSERT INTO `admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES
(1, 0, 'Unknown', '/HbAcUdwhrj.php/index/login?url=%2FHbAcUdwhrj.php', '', '{\"url\":\"\\/HbAcUdwhrj.php\",\"__token__\":\"***\",\"username\":\"\",\"password\":\"***\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625214561),
(2, 0, 'Unknown', '/HbAcUdwhrj.php/index/login?url=%2FHbAcUdwhrj.php', '登入', '{\"url\":\"\\/HbAcUdwhrj.php\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625214575),
(3, 1, 'sysadmin', '/HbAcUdwhrj.php/index/login?url=%2FHbAcUdwhrj.php', '登入', '{\"url\":\"\\/HbAcUdwhrj.php\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\",\"keeplogin\":\"1\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625214580),
(4, 1, 'sysadmin', '/HbAcUdwhrj.php/index/login?url=%2FHbAcUdwhrj.php', '登入', '{\"url\":\"\\/HbAcUdwhrj.php\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625214854),
(5, 0, 'Unknown', '/HbAcUdwhrj.php/index/login', '', '{\"__token__\":\"***\",\"username\":\"\",\"password\":\"***\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625215523),
(6, 1, 'sysadmin', '/HbAcUdwhrj.php/index/login?url=%2FHbAcUdwhrj.php%2Findex%2Findex', '登入', '{\"url\":\"\\/HbAcUdwhrj.php\\/index\\/index\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625215533),
(7, 1, 'sysadmin', '/HbAcUdwhrj.php/index/login?url=%2FHbAcUdwhrj.php', '登入', '{\"url\":\"\\/HbAcUdwhrj.php\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625221220),
(8, 1, 'sysadmin', '/HbAcUdwhrj.php/index/login', '登入', '{\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625226339),
(9, 1, 'sysadmin', '/HbAcUdwhrj.php/index/login?url=%2FHbAcUdwhrj.php%2Fuser%2Fuser%3Fref%3Daddtabs', '登入', '{\"url\":\"\\/HbAcUdwhrj.php\\/user\\/user?ref=addtabs\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625233974),
(10, 1, 'sysadmin', '/HbAcUdwhrj.php/auth/rule/multi', '權限管理 / 菜單規則', '{\"action\":\"\",\"ids\":\"3\",\"params\":\"ismenu=0\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625233985),
(11, 1, 'sysadmin', '/HbAcUdwhrj.php/auth/rule/multi', '權限管理 / 菜單規則', '{\"action\":\"\",\"ids\":\"79\",\"params\":\"ismenu=0\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625233992),
(12, 1, 'sysadmin', '/HbAcUdwhrj.php/auth/rule/multi', '權限管理 / 菜單規則', '{\"action\":\"\",\"ids\":\"73\",\"params\":\"ismenu=0\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625233993),
(13, 1, 'sysadmin', '/HbAcUdwhrj.php/ajax/weigh', '', '{\"ids\":\"66,1,2,6,7,8,3,5,9,10,11,12,4,67,73,79\",\"changeid\":\"66\",\"pid\":\"0\",\"field\":\"weigh\",\"orderway\":\"desc\",\"table\":\"auth_rule\",\"pk\":\"id\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625233996),
(14, 1, 'sysadmin', '/HbAcUdwhrj.php/auth/rule/multi', '權限管理 / 菜單規則', '{\"action\":\"\",\"ids\":\"1\",\"params\":\"ismenu=0\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625234000),
(15, 1, 'sysadmin', '/HbAcUdwhrj.php/auth/rule/multi', '權限管理 / 菜單規則', '{\"action\":\"\",\"ids\":\"4\",\"params\":\"ismenu=0\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625234014),
(16, 1, 'sysadmin', '/HbAcUdwhrj.php/general.config/edit', '常規管理 / 系統配置 / 編輯', '{\"__token__\":\"***\",\"row\":{\"name\":\"運動神人\",\"beian\":\"\",\"cdnurl\":\"\",\"version\":\"1.0.1\",\"timezone\":\"Asia\\/Shanghai\",\"forbiddenip\":\"\",\"languages\":\"{&quot;backend&quot;:&quot;zh-cn&quot;,&quot;frontend&quot;:&quot;zh-cn&quot;}\",\"fixedpage\":\"dashboard\"}}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625234038),
(17, 1, 'sysadmin', '/HbAcUdwhrj.php/index/login', '登入', '{\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625234677),
(18, 1, 'sysadmin', '/HbAcUdwhrj.php/index/login', '登入', '{\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625234720),
(19, 1, 'sysadmin', '/HbAcUdwhrj.php/general/config/check', '常規管理 / 系統配置', '{\"row\":{\"name\":\"123\"}}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625234933),
(20, 1, 'sysadmin', '/HbAcUdwhrj.php/general/config/check', '常規管理 / 系統配置', '{\"row\":{\"name\":\"url\"}}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625234937),
(21, 1, 'sysadmin', '/HbAcUdwhrj.php/general/config/check', '常規管理 / 系統配置', '{\"row\":{\"name\":\"url2\"}}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625235025),
(22, 1, 'sysadmin', '/HbAcUdwhrj.php/general.config/add', '常規管理 / 系統配置 / 添加', '{\"__token__\":\"***\",\"row\":{\"group\":\"basic\",\"type\":\"array\",\"name\":\"url2\",\"title\":\"路徑\",\"setting\":{\"table\":\"\",\"conditions\":\"\",\"key\":\"furl\",\"value\":\"htt\\/ps:\\/\\/sportgod.cc\"},\"value\":\"1\",\"content\":\"value1|title1\\r\\nvalue2|title2\",\"tip\":\"\",\"rule\":\"\",\"extend\":\"\"}}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625235027),
(23, 1, 'sysadmin', '/HbAcUdwhrj.php/general.config/add', '常規管理 / 系統配置 / 添加', '{\"__token__\":\"***\",\"row\":{\"group\":\"basic\",\"type\":\"array\",\"name\":\"url2\",\"title\":\"路徑\",\"setting\":{\"table\":\"\",\"conditions\":\"\",\"key\":\"furl\",\"value\":\"htt\\/ps:\\/\\/sportgod.cc\"},\"value\":\"1\",\"content\":\"value1|title1\\r\\nvalue2|title2\",\"tip\":\"\",\"rule\":\"\",\"extend\":\"\"}}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625235028),
(24, 1, 'sysadmin', '/HbAcUdwhrj.php/index/login?url=%2FHbAcUdwhrj.php%2Fgeneral%2Fconfig%3Fref%3Daddtabs', '登入', '{\"url\":\"\\/HbAcUdwhrj.php\\/general\\/config?ref=addtabs\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625235258),
(25, 1, 'sysadmin', '/HbAcUdwhrj.php/index/login?url=%2FHbAcUdwhrj.php', '登入', '{\"url\":\"\\/HbAcUdwhrj.php\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625244188),
(26, 1, 'sysadmin', '/HbAcUdwhrj.php/index/login?url=%2FHbAcUdwhrj.php', '登入', '{\"url\":\"\\/HbAcUdwhrj.php\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625244325),
(27, 1, 'sysadmin', '/backend.php/index/login', '登入', '{\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625245309),
(28, 1, 'sysadmin', '/backend.php/general.config/edit', '常規管理 / 系統配置 / 編輯', '{\"__token__\":\"***\",\"row\":{\"name\":\"運動神人\",\"beian\":\"\",\"cdnurl\":\"\",\"version\":\"1.0.0\",\"timezone\":\"Asia\\/Shanghai\",\"forbiddenip\":\"\",\"languages\":\"{&quot;backend&quot;:&quot;zh-cn&quot;,&quot;frontend&quot;:&quot;zh-cn&quot;}\",\"fixedpage\":\"user\\/merchant\",\"url\":\"{&quot;furl&quot;:&quot;https:\\/\\/sportgod.cc&quot;,&quot;api&quot;:&quot;https:\\/\\/sportgod.cc\\/api&quot;,&quot;burl&quot;:&quot;https:\\/\\/sportgod.cc\\/backend.php&quot;}\"}}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625245561),
(29, 1, 'sysadmin', '/backend.php/general.profile/update', '常規管理 / 個人資料 / 更新個人信息', '{\"__token__\":\"***\",\"row\":{\"avatar\":\"\\/assets\\/img\\/avatar.png\",\"email\":\"sysadmin@admin.com\",\"nickname\":\"RD\",\"password\":\"***\"}}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625245587),
(30, 1, 'sysadmin', '/backend.php/index/login', '登入', '{\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625245597),
(31, 1, 'sysadmin', '/backend.php/index/login', '登入', '{\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625245934),
(32, 1, 'sysadmin', '/backend.php/index/login', '登入', '{\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625245975),
(33, 1, 'sysadmin', '/backend.php/index/login?url=%2Fbackend.php', '登入', '{\"url\":\"\\/backend.php\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\",\"keeplogin\":\"1\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625299958),
(34, 1, 'sysadmin', '/backend.php/general.config/edit', '常規管理 / 系統配置 / 編輯', '{\"__token__\":\"***\",\"row\":{\"name\":\"運動神人\",\"beian\":\"\",\"cdnurl\":\"\",\"version\":\"1.0.1\",\"timezone\":\"Asia\\/Shanghai\",\"forbiddenip\":\"\",\"languages\":\"{&quot;backend&quot;:&quot;zh-cn&quot;,&quot;frontend&quot;:&quot;zh-cn&quot;}\",\"fixedpage\":\"user\\/merchant\",\"url\":\"{&quot;furl&quot;:&quot;https:\\/\\/sportgod.cc&quot;,&quot;api&quot;:&quot;https:\\/\\/sportgod.cc\\/api&quot;,&quot;burl&quot;:&quot;https:\\/\\/sportgod.cc\\/backend.php&quot;}\"}}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625300019),
(35, 1, 'sysadmin', '/backend.php/index/login?url=%2Fbackend.php', '登入', '{\"url\":\"\\/backend.php\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625450945),
(36, 1, 'sysadmin', '/backend.php/general.config/edit', '常規管理 / 系統配置 / 編輯', '{\"__token__\":\"***\",\"row\":{\"name\":\"運動神人2\",\"beian\":\"\",\"cdnurl\":\"\",\"version\":\"1.0.1\",\"timezone\":\"Asia\\/Shanghai\",\"forbiddenip\":\"\",\"languages\":\"{&quot;backend&quot;:&quot;zh-cn&quot;,&quot;frontend&quot;:&quot;zh-cn&quot;}\",\"fixedpage\":\"user\\/merchant\",\"url\":\"{&quot;furl&quot;:&quot;https:\\/\\/sportgod.cc&quot;,&quot;api&quot;:&quot;https:\\/\\/sportgod.cc\\/api&quot;,&quot;burl&quot;:&quot;https:\\/\\/sportgod.cc\\/backend.php&quot;}\"}}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625450993),
(37, 1, 'sysadmin', '/backend.php/general.config/edit', '常規管理 / 系統配置 / 編輯', '{\"__token__\":\"***\",\"row\":{\"name\":\"運動神人3\",\"beian\":\"\",\"cdnurl\":\"\",\"version\":\"1.0.1\",\"timezone\":\"Asia\\/Shanghai\",\"forbiddenip\":\"\",\"languages\":\"{&quot;backend&quot;:&quot;zh-cn&quot;,&quot;frontend&quot;:&quot;zh-cn&quot;}\",\"fixedpage\":\"user\\/merchant\",\"url\":\"{&quot;furl&quot;:&quot;https:\\/\\/sportgod.cc&quot;,&quot;api&quot;:&quot;https:\\/\\/sportgod.cc\\/api&quot;,&quot;burl&quot;:&quot;https:\\/\\/sportgod.cc\\/backend.php&quot;}\"}}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625451002),
(38, 1, 'sysadmin', '/backend.php/general.config/edit', '常規管理 / 系統配置 / 編輯', '{\"__token__\":\"***\",\"row\":{\"name\":\"運動神人4\",\"beian\":\"\",\"cdnurl\":\"\",\"version\":\"1.0.1\",\"timezone\":\"Asia\\/Shanghai\",\"forbiddenip\":\"\",\"languages\":\"{&quot;backend&quot;:&quot;zh-cn&quot;,&quot;frontend&quot;:&quot;zh-cn&quot;}\",\"fixedpage\":\"user\\/merchant\",\"url\":\"{&quot;furl&quot;:&quot;https:\\/\\/sportgod.cc&quot;,&quot;api&quot;:&quot;https:\\/\\/sportgod.cc\\/api&quot;,&quot;burl&quot;:&quot;https:\\/\\/sportgod.cc\\/backend.php&quot;}\"}}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625451525),
(39, 1, 'sysadmin', '/backend.php/general.config/edit', '常規管理 / 系統配置 / 編輯', '{\"__token__\":\"***\",\"row\":{\"name\":\"運動神人4\",\"beian\":\"\",\"cdnurl\":\"\",\"version\":\"1.0.1\",\"timezone\":\"Asia\\/Shanghai\",\"forbiddenip\":\"\",\"languages\":\"{&quot;backend&quot;:&quot;zh-cn&quot;,&quot;frontend&quot;:&quot;zh-cn&quot;}\",\"fixedpage\":\"user\\/merchant\",\"url\":\"{&quot;furl&quot;:&quot;https:\\/\\/sportgod.cc&quot;,&quot;api&quot;:&quot;https:\\/\\/sportgod.cc\\/api&quot;,&quot;burl&quot;:&quot;https:\\/\\/sportgod.cc\\/backend.php&quot;}\"}}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625451530),
(40, 1, 'sysadmin', '/backend.php/general.config/edit', '常規管理 / 系統配置 / 編輯', '{\"__token__\":\"***\",\"row\":{\"name\":\"運動神人4\",\"beian\":\"\",\"cdnurl\":\"\",\"version\":\"1.0.1\",\"timezone\":\"Asia\\/Shanghai\",\"forbiddenip\":\"\",\"languages\":\"{&quot;backend&quot;:&quot;zh-cn&quot;,&quot;frontend&quot;:&quot;zh-cn&quot;}\",\"fixedpage\":\"user\\/merchant\",\"url\":\"{&quot;furl&quot;:&quot;https:\\/\\/sportgod.cc&quot;,&quot;api&quot;:&quot;https:\\/\\/sportgod.cc\\/api&quot;,&quot;burl&quot;:&quot;https:\\/\\/sportgod.cc\\/backend.php&quot;}\"}}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625451534),
(41, 1, 'sysadmin', '/backend.php/general.config/edit', '常規管理 / 系統配置 / 編輯', '{\"__token__\":\"***\",\"row\":{\"name\":\"運動神人4\",\"beian\":\"\",\"cdnurl\":\"\",\"version\":\"1.0.1\",\"timezone\":\"Asia\\/Shanghai\",\"forbiddenip\":\"\",\"languages\":\"{&quot;backend&quot;:&quot;zh-cn&quot;,&quot;frontend&quot;:&quot;zh-cn&quot;}\",\"fixedpage\":\"user\\/merchant\",\"url\":\"{&quot;furl&quot;:&quot;https:\\/\\/sportgod.cc&quot;,&quot;api&quot;:&quot;https:\\/\\/sportgod.cc\\/api&quot;,&quot;burl&quot;:&quot;https:\\/\\/sportgod.cc\\/backend.php&quot;}\"}}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625451535),
(42, 1, 'sysadmin', '/backend.php/general.config/edit', '常規管理 / 系統配置 / 編輯', '{\"__token__\":\"***\",\"row\":{\"name\":\"運動神人6\",\"beian\":\"\",\"cdnurl\":\"\",\"version\":\"1.0.1\",\"timezone\":\"Asia\\/Shanghai\",\"forbiddenip\":\"\",\"languages\":\"{&quot;backend&quot;:&quot;zh-cn&quot;,&quot;frontend&quot;:&quot;zh-cn&quot;}\",\"fixedpage\":\"user\\/merchant\",\"url\":\"{&quot;furl&quot;:&quot;https:\\/\\/sportgod.cc&quot;,&quot;api&quot;:&quot;https:\\/\\/sportgod.cc\\/api&quot;,&quot;burl&quot;:&quot;https:\\/\\/sportgod.cc\\/backend.php&quot;}\"}}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625451661),
(43, 1, 'sysadmin', '/backend.php/general.config/edit', '常規管理 / 系統配置 / 編輯', '{\"__token__\":\"***\",\"row\":{\"name\":\"運動神人6\",\"beian\":\"\",\"cdnurl\":\"\",\"version\":\"1.0.1\",\"timezone\":\"Asia\\/Shanghai\",\"forbiddenip\":\"\",\"languages\":\"{&quot;backend&quot;:&quot;zh-cn&quot;,&quot;frontend&quot;:&quot;zh-cn&quot;}\",\"fixedpage\":\"user\\/merchant\",\"url\":\"{&quot;furl&quot;:&quot;https:\\/\\/sportgod.cc&quot;,&quot;api&quot;:&quot;https:\\/\\/sportgod.cc\\/api&quot;,&quot;burl&quot;:&quot;https:\\/\\/sportgod.cc\\/backend.php&quot;}\"}}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625451663),
(44, 1, 'sysadmin', '/backend.php/index/login', '登入', '{\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625451683),
(45, 1, 'sysadmin', '/backend.php/index/login?url=%2Fbackend.php', '登入', '{\"url\":\"\\/backend.php\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625456112),
(46, 1, 'sysadmin', '/backend.php/general.config/edit', '常規管理 / 系統配置 / 編輯', '{\"__token__\":\"***\",\"row\":{\"name\":\"運動神人\",\"beian\":\"\",\"cdnurl\":\"\",\"version\":\"1.0.1\",\"timezone\":\"Asia\\/Shanghai\",\"forbiddenip\":\"\",\"languages\":\"{&quot;backend&quot;:&quot;zh-cn&quot;,&quot;frontend&quot;:&quot;zh-cn&quot;}\",\"fixedpage\":\"user\\/merchant\",\"url\":\"{&quot;furl&quot;:&quot;https:\\/\\/sportgod.cc&quot;,&quot;api&quot;:&quot;https:\\/\\/sportgod.cc\\/api&quot;,&quot;burl&quot;:&quot;https:\\/\\/sportgod.cc\\/backend.php&quot;}\"}}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625456121),
(47, 1, 'sysadmin', '/backend.php/general.config/edit', '常規管理 / 系統配置 / 編輯', '{\"__token__\":\"***\",\"row\":{\"name\":\"運動神人1\",\"beian\":\"\",\"cdnurl\":\"\",\"version\":\"1.0.1\",\"timezone\":\"Asia\\/Shanghai\",\"forbiddenip\":\"\",\"languages\":\"{&quot;backend&quot;:&quot;zh-cn&quot;,&quot;frontend&quot;:&quot;zh-cn&quot;}\",\"fixedpage\":\"user\\/merchant\",\"url\":\"{&quot;furl&quot;:&quot;https:\\/\\/sportgod.cc&quot;,&quot;api&quot;:&quot;https:\\/\\/sportgod.cc\\/api&quot;,&quot;burl&quot;:&quot;https:\\/\\/sportgod.cc\\/backend.php&quot;}\"}}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625456129),
(48, 1, 'sysadmin', '/backend.php/index/login', '登入', '{\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625456138),
(49, 1, 'sysadmin', '/backend.php/general.config/edit', '常規管理 / 系統配置 / 編輯', '{\"__token__\":\"***\",\"row\":{\"name\":\"運動神人\",\"beian\":\"\",\"cdnurl\":\"\",\"version\":\"1.0.0\",\"timezone\":\"Asia\\/Shanghai\",\"forbiddenip\":\"\",\"languages\":\"{&quot;backend&quot;:&quot;zh-cn&quot;,&quot;frontend&quot;:&quot;zh-cn&quot;}\",\"fixedpage\":\"user\\/merchant\",\"url\":\"{&quot;furl&quot;:&quot;https:\\/\\/sportgod.cc&quot;,&quot;api&quot;:&quot;https:\\/\\/sportgod.cc\\/api&quot;,&quot;burl&quot;:&quot;https:\\/\\/sportgod.cc\\/backend.php&quot;}\"}}', '36.237.73.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1625456251),
(50, 0, 'Unknown', '/backend.php/index/login?url=%2Fbackend.php%2Fuser%2Fuser%3Fref%3Daddtabs', '', '{\"url\":\"\\/backend.php\\/user\\/user?ref=addtabs\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1626110218),
(51, 1, 'sysadmin', '/backend.php/index/login?url=%2Fbackend.php%2Fuser%2Fuser%3Fref%3Daddtabs', '登入', '{\"url\":\"\\/backend.php\\/user\\/user?ref=addtabs\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1626110233),
(52, 1, 'sysadmin', '/backend.php/index/login?url=%2Fbackend.php', '登入', '{\"url\":\"\\/backend.php\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '114.39.52.147', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1626137928),
(53, 1, 'sysadmin', '/backend.php/auth/rule/multi', '權限管理 / 菜單規則', '{\"action\":\"\",\"ids\":\"85\",\"params\":\"ismenu=0\"}', '114.39.52.147', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1626137948),
(54, 1, 'sysadmin', '/backend.php/auth/rule/multi', '權限管理 / 菜單規則', '{\"action\":\"\",\"ids\":\"85\",\"params\":\"ismenu=1\"}', '114.39.52.147', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1626137949),
(55, 1, 'sysadmin', '/backend.php/index/login?url=%2Fbackend.php%2Fuser%2Fuser%3Fref%3Daddtabs', '登入', '{\"url\":\"\\/backend.php\\/user\\/user?ref=addtabs\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '114.39.52.147', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1626141503),
(56, 1, 'sysadmin', '/backend.php/user/user/add?dialog=1', '會員管理 / 會員管理 / 添加', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"username\":\"chao\",\"nickname\":\"chao\",\"password\":\"***\",\"mobile\":\"0928565121\",\"gender\":\"0\",\"status\":\"1\"}}', '114.39.52.147', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1626141516),
(57, 1, 'sysadmin', '/backend.php/index/login?url=%2Fbackend.php', '登入', '{\"url\":\"\\/backend.php\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '114.39.52.147', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1626371772),
(58, 1, 'sysadmin', '/backend.php/index/login?url=%2Fbackend.php%2Fuser%2Farticle%3Fref%3Daddtabs', '登入', '{\"url\":\"\\/backend.php\\/user\\/article?ref=addtabs\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '114.39.52.147', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1626378273),
(59, 1, 'sysadmin', '/backend.php/index/login?url=%2Fbackend.php%2Fauth%2Frule%3Faddtabs%3D1', '登入', '{\"url\":\"\\/backend.php\\/auth\\/rule?addtabs=1\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '114.39.101.183', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1626402658),
(60, 1, 'sysadmin', '/backend.php/auth/rule/add?dialog=1', '權限管理 / 菜單規則 / 添加', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"ismenu\":\"1\",\"pid\":\"0\",\"name\":\"frontend\",\"title\":\"前台管理\",\"icon\":\"fa fa-window-maximize\",\"weigh\":\"0\",\"condition\":\"\",\"remark\":\"\",\"status\":\"normal\"}}', '114.39.101.183', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1626402691),
(61, 1, 'sysadmin', '/backend.php/ajax/weigh', '', '{\"ids\":\"66,90,67,73,79,85,1,2,6,7,8,3,4,5,9,10,11,12\",\"changeid\":\"90\",\"pid\":\"0\",\"field\":\"weigh\",\"orderway\":\"desc\",\"table\":\"auth_rule\",\"pk\":\"id\"}', '114.39.101.183', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1626402702),
(62, 1, 'sysadmin', '/backend.php/auth/rule/add?dialog=1', '權限管理 / 菜單規則 / 添加', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"ismenu\":\"1\",\"pid\":\"90\",\"name\":\"frontend\\/articlecat\",\"title\":\"文章分類\",\"icon\":\"fa fa-bookmark\",\"weigh\":\"0\",\"condition\":\"\",\"remark\":\"\",\"status\":\"normal\"}}', '114.39.101.183', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1626402947),
(63, 1, 'sysadmin', '/backend.php/auth/rule/add?dialog=1', '權限管理 / 菜單規則 / 添加', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"ismenu\":\"0\",\"pid\":\"85\",\"name\":\"frontend\\/articlecat\\/index\",\"title\":\"查看\",\"icon\":\"fa fa-circle-o\",\"weigh\":\"0\",\"condition\":\"\",\"remark\":\"\",\"status\":\"normal\"}}', '114.39.101.183', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1626402963),
(64, 1, 'sysadmin', '/backend.php/auth/rule/add?dialog=1', '權限管理 / 菜單規則 / 添加', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"ismenu\":\"0\",\"pid\":\"91\",\"name\":\"frontend\\/articlecat\\/add\",\"title\":\"添加\",\"icon\":\"fa fa-circle-o\",\"weigh\":\"0\",\"condition\":\"\",\"remark\":\"\",\"status\":\"normal\"}}', '114.39.101.183', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1626402982),
(65, 1, 'sysadmin', '/backend.php/auth/rule/edit/ids/92?dialog=1', '權限管理 / 菜單規則 / 編輯', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"ismenu\":\"0\",\"pid\":\"91\",\"name\":\"frontend\\/articlecat\\/index\",\"title\":\"查看\",\"icon\":\"fa fa-circle-o\",\"weigh\":\"0\",\"condition\":\"\",\"remark\":\"\",\"status\":\"normal\"},\"ids\":\"92\"}', '114.39.101.183', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1626402991),
(66, 1, 'sysadmin', '/backend.php/auth/rule/add?dialog=1', '權限管理 / 菜單規則 / 添加', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"ismenu\":\"1\",\"pid\":\"91\",\"name\":\"frontend\\/articlecat\\/del\",\"title\":\"刪除\",\"icon\":\"fa fa-circle-o\",\"weigh\":\"0\",\"condition\":\"\",\"remark\":\"\",\"status\":\"normal\"}}', '114.39.101.183', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1626403002),
(67, 1, 'sysadmin', '/backend.php/auth/rule/add?dialog=1', '權限管理 / 菜單規則 / 添加', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"ismenu\":\"0\",\"pid\":\"0\",\"name\":\"frontend\\/articlecat\\/edit\",\"title\":\"編輯\",\"icon\":\"fa fa-circle-o\",\"weigh\":\"0\",\"condition\":\"\",\"remark\":\"\",\"status\":\"normal\"}}', '114.39.101.183', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1626403023),
(68, 1, 'sysadmin', '/backend.php/auth/rule/add?dialog=1', '權限管理 / 菜單規則 / 添加', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"ismenu\":\"0\",\"pid\":\"91\",\"name\":\"frontend\\/articlecat\\/edit\",\"title\":\"編輯\",\"icon\":\"fa fa-circle-o\",\"weigh\":\"0\",\"condition\":\"\",\"remark\":\"\",\"status\":\"normal\"}}', '114.39.101.183', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1626403027),
(69, 1, 'sysadmin', '/backend.php/auth/rule/multi', '權限管理 / 菜單規則', '{\"action\":\"\",\"ids\":\"94\",\"params\":\"ismenu=0\"}', '114.39.101.183', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1626403031),
(70, 0, 'Unknown', '/backend.php/index/login?url=%2Fbackend.php', '登入', '{\"url\":\"\\/backend.php\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '111.254.224.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.107 Safari/537.36', 1627871825),
(71, 1, 'sysadmin', '/backend.php/index/login?url=%2Fbackend.php', '登入', '{\"url\":\"\\/backend.php\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '111.254.224.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.107 Safari/537.36', 1627871829),
(72, 1, 'sysadmin', '/backend.php/frontend/articlecat/add?dialog=1', '前台管理 / 文章分類 / 添加', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"cat_name\":\"www\",\"type\":\"1\",\"status\":\"1\"}}', '111.254.224.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.107 Safari/537.36', 1627871875),
(73, 1, 'sysadmin', '/backend.php/user/article/add?dialog=1', '會員管理 / 文章管理 / 添加', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"cat_id\":\"1\",\"title\":\"rrrr\",\"content\":\"456456\",\"status\":\"1\"}}', '111.254.224.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.107 Safari/537.36', 1627871907),
(74, 1, 'sysadmin', '/backend.php/index/login?url=%2Fbackend.php', '登入', '{\"url\":\"\\/backend.php\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '111.254.224.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36', 1628058310),
(75, 1, 'sysadmin', '/backend.php/general.config/edit', '常規管理 / 系統配置 / 編輯', '{\"__token__\":\"***\",\"row\":{\"name\":\"運動神人\",\"beian\":\"\",\"cdnurl\":\"\",\"version\":\"1.0.0\",\"timezone\":\"Asia\\/Shanghai\",\"forbiddenip\":\"\",\"languages\":\"{&quot;backend&quot;:&quot;zh-cn&quot;,&quot;frontend&quot;:&quot;zh-cn&quot;}\",\"fixedpage\":\"user\\/user\",\"url\":\"{&quot;furl&quot;:&quot;https:\\/\\/sportgod.cc&quot;,&quot;api&quot;:&quot;https:\\/\\/sportgod.cc\\/api&quot;,&quot;burl&quot;:&quot;https:\\/\\/sportgod.cc\\/backend.php&quot;}\"}}', '111.254.224.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36', 1628058342),
(76, 1, 'sysadmin', '/backend.php/index/login?url=%2Fbackend.php%2Ffrontend%2Farticlecat%3Faddtabs%3D1', '登入', '{\"url\":\"\\/backend.php\\/frontend\\/articlecat?addtabs=1\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '111.254.224.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36', 1628061765),
(77, 1, 'sysadmin', '/backend.php/frontend/articlecat/edit/ids/1?dialog=1', '前台管理 / 文章分類 / 編輯', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"id\":\"1\",\"cat_name\":\"籃球\",\"type\":\"1\",\"status\":\"1\"},\"ids\":\"1\"}', '111.254.224.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36', 1628061776),
(78, 1, 'sysadmin', '/backend.php/frontend/articlecat/edit/ids/1?dialog=1', '前台管理 / 文章分類 / 編輯', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"id\":\"1\",\"cat_name\":\"籃球\",\"type\":\"1\",\"status\":\"1\"},\"ids\":\"1\"}', '111.254.224.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36', 1628061810),
(79, 1, 'sysadmin', '/backend.php/frontend/articlecat/add?dialog=1', '前台管理 / 文章分類 / 添加', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"cat_name\":\"新聞\",\"type\":\"1\",\"status\":\"1\"}}', '111.254.224.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36', 1628061820),
(80, 1, 'sysadmin', '/backend.php/frontend/articlecat/edit/ids/2?dialog=1', '前台管理 / 文章分類 / 編輯', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"id\":\"2\",\"cat_name\":\"新聞\",\"type\":\"0\",\"status\":\"1\"},\"ids\":\"2\"}', '111.254.224.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36', 1628061826),
(81, 1, 'sysadmin', '/backend.php/frontend/articlecat/add?dialog=1', '前台管理 / 文章分類 / 添加', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"cat_name\":\"棒球\",\"type\":\"1\",\"status\":\"1\"}}', '111.254.224.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36', 1628061833),
(82, 1, 'sysadmin', '/backend.php/ajax/weigh', '', '{\"ids\":\"2,1,3\",\"changeid\":\"2\",\"pid\":\"\",\"field\":\"weigh\",\"orderway\":\"asc\",\"table\":\"article_cat\",\"pk\":\"id\"}', '111.254.224.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36', 1628061835),
(83, 1, 'sysadmin', '/backend.php/user/article/add?dialog=1', '會員管理 / 文章管理 / 添加', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"cat_id\":\"2\",\"title\":\"最新消息\",\"content\":\"456789\",\"status\":\"1\"}}', '111.254.224.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36', 1628061873),
(84, 1, 'sysadmin', '/backend.php/auth/rule/edit/ids/85?dialog=1', '權限管理 / 菜單規則 / 編輯', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"ismenu\":\"1\",\"pid\":\"90\",\"name\":\"frontend\\/article\",\"title\":\"文章管理\",\"icon\":\"fa fa-file-text-o\",\"weigh\":\"0\",\"condition\":\"\",\"remark\":\"\",\"status\":\"normal\"},\"ids\":\"85\"}', '111.254.224.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36', 1628062588),
(85, 1, 'sysadmin', '/backend.php/auth/rule/edit/ids/86?dialog=1', '權限管理 / 菜單規則 / 編輯', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"ismenu\":\"0\",\"pid\":\"85\",\"name\":\"frontend\\/article\\/index\",\"title\":\"查看\",\"icon\":\"fa fa-circle-o\",\"weigh\":\"0\",\"condition\":\"\",\"remark\":\"\",\"status\":\"normal\"},\"ids\":\"86\"}', '111.254.224.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36', 1628062602),
(86, 1, 'sysadmin', '/backend.php/auth/rule/edit/ids/87?dialog=1', '權限管理 / 菜單規則 / 編輯', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"ismenu\":\"0\",\"pid\":\"85\",\"name\":\"frontend\\/article\\/add\",\"title\":\"添加\",\"icon\":\"fa fa-circle-o\",\"weigh\":\"0\",\"condition\":\"\",\"remark\":\"\",\"status\":\"normal\"},\"ids\":\"87\"}', '111.254.224.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36', 1628062613),
(87, 1, 'sysadmin', '/backend.php/auth/rule/edit/ids/88?dialog=1', '權限管理 / 菜單規則 / 編輯', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"ismenu\":\"0\",\"pid\":\"85\",\"name\":\"frontend\\/article\\/edit\",\"title\":\"編輯\",\"icon\":\"fa fa-circle-o\",\"weigh\":\"0\",\"condition\":\"\",\"remark\":\"\",\"status\":\"normal\"},\"ids\":\"88\"}', '111.254.224.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36', 1628062620),
(88, 1, 'sysadmin', '/backend.php/auth/rule/edit/ids/89?dialog=1', '權限管理 / 菜單規則 / 編輯', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"ismenu\":\"0\",\"pid\":\"85\",\"name\":\"frontend\\/article\\/del\",\"title\":\"刪除\",\"icon\":\"fa fa-circle-o\",\"weigh\":\"0\",\"condition\":\"\",\"remark\":\"\",\"status\":\"normal\"},\"ids\":\"89\"}', '111.254.224.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36', 1628062627),
(89, 1, 'sysadmin', '/backend.php/frontend/article/edit/ids/7?dialog=1', '前台管理 / 文章管理 / 編輯', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"id\":\"7\",\"cat_id\":\"3\",\"title\":\"0005\",\"content\":\"222\",\"status\":\"1\"},\"ids\":\"7\"}', '111.254.224.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36', 1628062751),
(90, 1, 'sysadmin', '/backend.php/frontend/article/edit/ids/2?dialog=1', '前台管理 / 文章管理 / 編輯', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"id\":\"2\",\"cat_id\":\"1\",\"title\":\"文字編輯器測試\",\"content\":\"8855\",\"status\":\"1\"},\"ids\":\"2\"}', '111.254.224.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36', 1628062797),
(91, 1, 'sysadmin', '/backend.php/index/login?url=%2Fbackend.php%2Fuser%2Fuser%3Fref%3Daddtabs', '登入', '{\"url\":\"\\/backend.php\\/user\\/user?ref=addtabs\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '111.254.194.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36', 1628229551),
(92, 1, 'sysadmin', '/backend.php/user/user/del', '會員管理 / 會員管理 / 刪除', '{\"action\":\"del\",\"ids\":\"1\",\"params\":\"\"}', '111.254.194.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36', 1628229556),
(93, 1, 'sysadmin', '/backend.php/index/login?url=%2Fbackend.php', '登入', '{\"url\":\"\\/backend.php\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '114.39.107.242', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 1628325327),
(94, 1, 'sysadmin', '/backend.php/index/login?url=%2Fbackend.php', '登入', '{\"url\":\"\\/backend.php\",\"__token__\":\"***\",\"username\":\"sysadmin\",\"password\":\"***\"}', '111.254.221.106', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36', 1628847570);

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
  `content` mediumtext NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `cat_id` int(11) NOT NULL DEFAULT '0',
  `fav` int(11) NOT NULL DEFAULT '0' COMMENT '收藏人數',
  `status` int(11) NOT NULL DEFAULT '1',
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 傾印資料表的資料 `article`
--

INSERT INTO `article` (`id`, `title`, `content`, `user_id`, `cat_id`, `fav`, `status`, `createtime`, `updatetime`) VALUES
(1, 'rrrr', '456456', 0, 1, 0, 1, 0, 0),
(2, '文字編輯器測試', '<span style=\"color:#E53333;font-size:24px;\">8855</span>', 2, 1, 0, 1, 1628061731, 1628062797),
(3, '最新消息', '456789', 0, 2, 1, 1, 1628061873, 1628849189),
(4, '測試', 'w', 2, 1, 0, 1, 1628061908, 1628061908),
(5, '棒球文章', '4568', 2, 3, 0, 1, 1628062004, 1628062004),
(6, '999', '555', 2, 3, 0, 1, 1628062412, 1628062412),
(7, '0005', '222', 2, 3, 0, 1, 1628062426, 1628062751),
(8, '測試編輯氣2', '<span style=\"font-size:14px;color:#003399;\">文字編輯氣<br />4895146<br /><strong><span style=\"background-color:#E53333;\">555</span></strong></span>', 2, 3, 0, 1, 1628063226, 1628063226);

-- --------------------------------------------------------

--
-- 資料表結構 `article_cat`
--

CREATE TABLE `article_cat` (
  `id` int(11) NOT NULL,
  `cat_name` varchar(20) NOT NULL,
  `weigh` int(11) NOT NULL,
  `type` int(11) NOT NULL DEFAULT '1' COMMENT '0後台 1通用',
  `status` int(11) NOT NULL DEFAULT '1',
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 傾印資料表的資料 `article_cat`
--

INSERT INTO `article_cat` (`id`, `cat_name`, `weigh`, `type`, `status`, `createtime`, `updatetime`) VALUES
(1, '籃球', 2, 1, 1, 0, 1628061776),
(2, '新聞', 1, 0, 1, 1628061820, 1628061826),
(3, '棒球', 3, 1, 1, 1628061833, 1628061833);

-- --------------------------------------------------------

--
-- 資料表結構 `article_fav`
--

CREATE TABLE `article_fav` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `createtime` int(11) NOT NULL,
  `updatetime` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- 傾印資料表的資料 `article_fav`
--

INSERT INTO `article_fav` (`id`, `article_id`, `user_id`, `createtime`, `updatetime`) VALUES
(7, 3, 2, 1628849189, 1628849189);

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

--
-- 傾印資料表的資料 `article_msg`
--

INSERT INTO `article_msg` (`id`, `article_id`, `msg`, `user_id`, `status`, `createtime`, `updatetime`) VALUES
(1, 1, '1', 2, 1, 0, 0),
(2, 1, '6', 2, 1, 0, 0),
(3, 1, '測試留言', 2, 1, 0, 0),
(4, 2, '789', 2, 1, 1628061749, 1628061749),
(5, 2, '<h2><span style=\"color:#FF9900;\">文字編輯器留言測試</span></h2>', 2, 1, 1628063135, 1628063135),
(6, 2, '<span style=\"font-family:DFKai-SB;font-size:18px;color:#E53333;\">789<br />換行留言</span>', 2, 1, 1628063176, 1628063176);

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

--
-- 傾印資料表的資料 `attachment`
--

INSERT INTO `attachment` (`id`, `admin_id`, `user_id`, `url`, `imagewidth`, `imageheight`, `imagetype`, `imageframes`, `filename`, `filesize`, `mimetype`, `extparam`, `createtime`, `updatetime`, `uploadtime`, `storage`, `sha1`) VALUES
(1, 1, 0, '/assets/img/qrcode.png', '150', '150', 'png', 0, 'qrcode.png', 21859, 'image/png', '', 1499681848, 1499681848, 1499681848, 'local', '17163603d0263e4838b9387ff2cd4877e8b018f6');

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

--
-- 傾印資料表的資料 `auth_group`
--

INSERT INTO `auth_group` (`id`, `pid`, `name`, `rules`, `createtime`, `updatetime`, `status`) VALUES
(1, 0, 'Admin group', '*', 1490883540, 149088354, 'normal'),
(2, 1, 'Second group', '13,14,16,15,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,40,41,42,43,44,45,46,47,48,49,50,55,56,57,58,59,60,61,62,63,64,65,1,9,10,11,7,6,8,2,4,5', 1490883540, 1505465692, 'normal'),
(3, 2, 'Third group', '1,4,9,10,11,13,14,15,16,17,40,41,42,43,44,45,46,47,48,49,50,55,56,57,58,59,60,61,62,63,64,65,5', 1490883540, 1502205322, 'normal'),
(4, 1, 'Second group 2', '1,4,13,14,15,16,17,55,56,57,58,59,60,61,62,63,64,65', 1490883540, 1502205350, 'normal'),
(5, 2, 'Third group 2', '1,2,6,7,8,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34', 1490883540, 1502205344, 'normal');

-- --------------------------------------------------------

--
-- 資料表結構 `auth_group_access`
--

CREATE TABLE `auth_group_access` (
  `uid` int(10) UNSIGNED NOT NULL COMMENT '会员ID',
  `group_id` int(10) UNSIGNED NOT NULL COMMENT '级别ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='权限分组表';

--
-- 傾印資料表的資料 `auth_group_access`
--

INSERT INTO `auth_group_access` (`uid`, `group_id`) VALUES
(1, 1);

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

--
-- 傾印資料表的資料 `auth_rule`
--

INSERT INTO `auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(1, 'file', 0, 'dashboard', 'Dashboard', 'fa fa-dashboard', '', 'Dashboard tips', 0, 1497429920, 1625234000, 119, 'normal'),
(2, 'file', 0, 'general', 'General', 'fa fa-cogs', '', '', 1, 1497429920, 1497430169, 99, 'normal'),
(3, 'file', 0, 'category', 'Category', 'fa fa-leaf', '', 'Category tips', 0, 1497429920, 1625233985, 0, 'normal'),
(4, 'file', 0, 'addon', 'Addon', 'fa fa-rocket', '', 'Addon tips', 0, 1502035509, 1625234014, 0, 'normal'),
(5, 'file', 0, 'auth', 'Auth', 'fa fa-group', '', '', 1, 1497429920, 1497430092, 0, 'normal'),
(6, 'file', 2, 'general/config', 'Config', 'fa fa-cog', '', 'Config tips', 1, 1497429920, 1497430683, 60, 'normal'),
(7, 'file', 2, 'general/attachment', 'Attachment', 'fa fa-file-image-o', '', 'Attachment tips', 1, 1497429920, 1497430699, 53, 'normal'),
(8, 'file', 2, 'general/profile', 'Profile', 'fa fa-user', '', '', 1, 1497429920, 1497429920, 34, 'normal'),
(9, 'file', 5, 'auth/admin', 'Admin', 'fa fa-user', '', 'Admin tips', 1, 1497429920, 1497430320, 118, 'normal'),
(10, 'file', 5, 'auth/adminlog', 'Admin log', 'fa fa-list-alt', '', 'Admin log tips', 1, 1497429920, 1497430307, 113, 'normal'),
(11, 'file', 5, 'auth/group', 'Group', 'fa fa-group', '', 'Group tips', 1, 1497429920, 1497429920, 109, 'normal'),
(12, 'file', 5, 'auth/rule', 'Rule', 'fa fa-bars', '', 'Rule tips', 1, 1497429920, 1497430581, 104, 'normal'),
(13, 'file', 1, 'dashboard/index', 'View', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 136, 'normal'),
(14, 'file', 1, 'dashboard/add', 'Add', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 135, 'normal'),
(15, 'file', 1, 'dashboard/del', 'Delete', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 133, 'normal'),
(16, 'file', 1, 'dashboard/edit', 'Edit', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 134, 'normal'),
(17, 'file', 1, 'dashboard/multi', 'Multi', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 132, 'normal'),
(18, 'file', 6, 'general/config/index', 'View', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 52, 'normal'),
(19, 'file', 6, 'general/config/add', 'Add', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 51, 'normal'),
(20, 'file', 6, 'general/config/edit', 'Edit', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 50, 'normal'),
(21, 'file', 6, 'general/config/del', 'Delete', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 49, 'normal'),
(22, 'file', 6, 'general/config/multi', 'Multi', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 48, 'normal'),
(23, 'file', 7, 'general/attachment/index', 'View', 'fa fa-circle-o', '', 'Attachment tips', 0, 1497429920, 1497429920, 59, 'normal'),
(24, 'file', 7, 'general/attachment/select', 'Select attachment', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 58, 'normal'),
(25, 'file', 7, 'general/attachment/add', 'Add', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 57, 'normal'),
(26, 'file', 7, 'general/attachment/edit', 'Edit', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 56, 'normal'),
(27, 'file', 7, 'general/attachment/del', 'Delete', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 55, 'normal'),
(28, 'file', 7, 'general/attachment/multi', 'Multi', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 54, 'normal'),
(29, 'file', 8, 'general/profile/index', 'View', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 33, 'normal'),
(30, 'file', 8, 'general/profile/update', 'Update profile', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 32, 'normal'),
(31, 'file', 8, 'general/profile/add', 'Add', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 31, 'normal'),
(32, 'file', 8, 'general/profile/edit', 'Edit', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 30, 'normal'),
(33, 'file', 8, 'general/profile/del', 'Delete', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 29, 'normal'),
(34, 'file', 8, 'general/profile/multi', 'Multi', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 28, 'normal'),
(35, 'file', 3, 'category/index', 'View', 'fa fa-circle-o', '', 'Category tips', 0, 1497429920, 1497429920, 142, 'normal'),
(36, 'file', 3, 'category/add', 'Add', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 141, 'normal'),
(37, 'file', 3, 'category/edit', 'Edit', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 140, 'normal'),
(38, 'file', 3, 'category/del', 'Delete', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 139, 'normal'),
(39, 'file', 3, 'category/multi', 'Multi', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 138, 'normal'),
(40, 'file', 9, 'auth/admin/index', 'View', 'fa fa-circle-o', '', 'Admin tips', 0, 1497429920, 1497429920, 117, 'normal'),
(41, 'file', 9, 'auth/admin/add', 'Add', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 116, 'normal'),
(42, 'file', 9, 'auth/admin/edit', 'Edit', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 115, 'normal'),
(43, 'file', 9, 'auth/admin/del', 'Delete', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 114, 'normal'),
(44, 'file', 10, 'auth/adminlog/index', 'View', 'fa fa-circle-o', '', 'Admin log tips', 0, 1497429920, 1497429920, 112, 'normal'),
(45, 'file', 10, 'auth/adminlog/detail', 'Detail', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 111, 'normal'),
(46, 'file', 10, 'auth/adminlog/del', 'Delete', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 110, 'normal'),
(47, 'file', 11, 'auth/group/index', 'View', 'fa fa-circle-o', '', 'Group tips', 0, 1497429920, 1497429920, 108, 'normal'),
(48, 'file', 11, 'auth/group/add', 'Add', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 107, 'normal'),
(49, 'file', 11, 'auth/group/edit', 'Edit', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 106, 'normal'),
(50, 'file', 11, 'auth/group/del', 'Delete', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 105, 'normal'),
(51, 'file', 12, 'auth/rule/index', 'View', 'fa fa-circle-o', '', 'Rule tips', 0, 1497429920, 1497429920, 103, 'normal'),
(52, 'file', 12, 'auth/rule/add', 'Add', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 102, 'normal'),
(53, 'file', 12, 'auth/rule/edit', 'Edit', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 101, 'normal'),
(54, 'file', 12, 'auth/rule/del', 'Delete', 'fa fa-circle-o', '', '', 0, 1497429920, 1497429920, 100, 'normal'),
(55, 'file', 4, 'addon/index', 'View', 'fa fa-circle-o', '', 'Addon tips', 0, 1502035509, 1502035509, 0, 'normal'),
(56, 'file', 4, 'addon/add', 'Add', 'fa fa-circle-o', '', '', 0, 1502035509, 1502035509, 0, 'normal'),
(57, 'file', 4, 'addon/edit', 'Edit', 'fa fa-circle-o', '', '', 0, 1502035509, 1502035509, 0, 'normal'),
(58, 'file', 4, 'addon/del', 'Delete', 'fa fa-circle-o', '', '', 0, 1502035509, 1502035509, 0, 'normal'),
(59, 'file', 4, 'addon/downloaded', 'Local addon', 'fa fa-circle-o', '', '', 0, 1502035509, 1502035509, 0, 'normal'),
(60, 'file', 4, 'addon/state', 'Update state', 'fa fa-circle-o', '', '', 0, 1502035509, 1502035509, 0, 'normal'),
(63, 'file', 4, 'addon/config', 'Setting', 'fa fa-circle-o', '', '', 0, 1502035509, 1502035509, 0, 'normal'),
(64, 'file', 4, 'addon/refresh', 'Refresh', 'fa fa-circle-o', '', '', 0, 1502035509, 1502035509, 0, 'normal'),
(65, 'file', 4, 'addon/multi', 'Multi', 'fa fa-circle-o', '', '', 0, 1502035509, 1502035509, 0, 'normal'),
(66, 'file', 0, 'user', 'User', 'fa fa-list', '', '', 1, 1516374729, 1516374729, 143, 'normal'),
(67, 'file', 66, 'user/user', 'User', 'fa fa-user', '', '', 1, 1516374729, 1516374729, 0, 'normal'),
(68, 'file', 67, 'user/user/index', 'View', 'fa fa-circle-o', '', '', 0, 1516374729, 1516374729, 0, 'normal'),
(69, 'file', 67, 'user/user/edit', 'Edit', 'fa fa-circle-o', '', '', 0, 1516374729, 1516374729, 0, 'normal'),
(70, 'file', 67, 'user/user/add', 'Add', 'fa fa-circle-o', '', '', 0, 1516374729, 1516374729, 0, 'normal'),
(71, 'file', 67, 'user/user/del', 'Del', 'fa fa-circle-o', '', '', 0, 1516374729, 1516374729, 0, 'normal'),
(72, 'file', 67, 'user/user/multi', 'Multi', 'fa fa-circle-o', '', '', 0, 1516374729, 1516374729, 0, 'normal'),
(73, 'file', 66, 'user/group', 'User group', 'fa fa-users', '', '', 0, 1516374729, 1625233993, 0, 'normal'),
(74, 'file', 73, 'user/group/add', 'Add', 'fa fa-circle-o', '', '', 0, 1516374729, 1516374729, 0, 'normal'),
(75, 'file', 73, 'user/group/edit', 'Edit', 'fa fa-circle-o', '', '', 0, 1516374729, 1516374729, 0, 'normal'),
(76, 'file', 73, 'user/group/index', 'View', 'fa fa-circle-o', '', '', 0, 1516374729, 1516374729, 0, 'normal'),
(77, 'file', 73, 'user/group/del', 'Del', 'fa fa-circle-o', '', '', 0, 1516374729, 1516374729, 0, 'normal'),
(78, 'file', 73, 'user/group/multi', 'Multi', 'fa fa-circle-o', '', '', 0, 1516374729, 1516374729, 0, 'normal'),
(79, 'file', 66, 'user/rule', 'User rule', 'fa fa-circle-o', '', '', 0, 1516374729, 1625233992, 0, 'normal'),
(80, 'file', 79, 'user/rule/index', 'View', 'fa fa-circle-o', '', '', 0, 1516374729, 1516374729, 0, 'normal'),
(81, 'file', 79, 'user/rule/del', 'Del', 'fa fa-circle-o', '', '', 0, 1516374729, 1516374729, 0, 'normal'),
(82, 'file', 79, 'user/rule/add', 'Add', 'fa fa-circle-o', '', '', 0, 1516374729, 1516374729, 0, 'normal'),
(83, 'file', 79, 'user/rule/edit', 'Edit', 'fa fa-circle-o', '', '', 0, 1516374729, 1516374729, 0, 'normal'),
(84, 'file', 79, 'user/rule/multi', 'Multi', 'fa fa-circle-o', '', '', 0, 1516374729, 1516374729, 0, 'normal'),
(85, 'file', 90, 'frontend/article', '文章管理', 'fa fa-file-text-o', '', '', 1, 1626110494, 1628062588, 0, 'normal'),
(86, 'file', 85, 'frontend/article/index', '查看', 'fa fa-circle-o', '', '', 0, 1626110513, 1628062602, 0, 'normal'),
(87, 'file', 85, 'frontend/article/add', '添加', 'fa fa-circle-o', '', '', 0, 1626110533, 1628062613, 0, 'normal'),
(88, 'file', 85, 'frontend/article/edit', '編輯', 'fa fa-circle-o', '', '', 0, 1626110549, 1628062620, 0, 'normal'),
(89, 'file', 85, 'frontend/article/del', '刪除', 'fa fa-circle-o', '', '', 0, 1626110561, 1628062627, 0, 'normal'),
(90, 'file', 0, 'frontend', '前台管理', 'fa fa-window-maximize', '', '', 1, 1626402691, 1626402691, 137, 'normal'),
(91, 'file', 90, 'frontend/articlecat', '文章分類', 'fa fa-bookmark', '', '', 1, 1626402947, 1626402947, 0, 'normal'),
(92, 'file', 91, 'frontend/articlecat/index', '查看', 'fa fa-circle-o', '', '', 0, 1626402963, 1626402991, 0, 'normal'),
(93, 'file', 91, 'frontend/articlecat/add', '添加', 'fa fa-circle-o', '', '', 0, 1626402982, 1626402982, 0, 'normal'),
(94, 'file', 91, 'frontend/articlecat/del', '刪除', 'fa fa-circle-o', '', '', 0, 1626403002, 1626403031, 0, 'normal'),
(95, 'file', 91, 'frontend/articlecat/edit', '編輯', 'fa fa-circle-o', '', '', 0, 1626403027, 1626403027, 0, 'normal');

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

--
-- 傾印資料表的資料 `category`
--

INSERT INTO `category` (`id`, `pid`, `type`, `name`, `nickname`, `flag`, `image`, `keywords`, `description`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(1, 0, 'page', '官方新闻', 'news', 'recommend', '/assets/img/qrcode.png', '', '', 'news', 1495262190, 1495262190, 1, 'normal'),
(2, 0, 'page', '移动应用', 'mobileapp', 'hot', '/assets/img/qrcode.png', '', '', 'mobileapp', 1495262244, 1495262244, 2, 'normal'),
(3, 2, 'page', '微信公众号', 'wechatpublic', 'index', '/assets/img/qrcode.png', '', '', 'wechatpublic', 1495262288, 1495262288, 3, 'normal'),
(4, 2, 'page', 'Android开发', 'android', 'recommend', '/assets/img/qrcode.png', '', '', 'android', 1495262317, 1495262317, 4, 'normal'),
(5, 0, 'page', '软件产品', 'software', 'recommend', '/assets/img/qrcode.png', '', '', 'software', 1495262336, 1499681850, 5, 'normal'),
(6, 5, 'page', '网站建站', 'website', 'recommend', '/assets/img/qrcode.png', '', '', 'website', 1495262357, 1495262357, 6, 'normal'),
(7, 5, 'page', '企业管理软件', 'company', 'index', '/assets/img/qrcode.png', '', '', 'company', 1495262391, 1495262391, 7, 'normal'),
(8, 6, 'page', 'PC端', 'website-pc', 'recommend', '/assets/img/qrcode.png', '', '', 'website-pc', 1495262424, 1495262424, 8, 'normal'),
(9, 6, 'page', '移动端', 'website-mobile', 'recommend', '/assets/img/qrcode.png', '', '', 'website-mobile', 1495262456, 1495262456, 9, 'normal'),
(10, 7, 'page', 'CRM系统 ', 'company-crm', 'recommend', '/assets/img/qrcode.png', '', '', 'company-crm', 1495262487, 1495262487, 10, 'normal'),
(11, 7, 'page', 'SASS平台软件', 'company-sass', 'recommend', '/assets/img/qrcode.png', '', '', 'company-sass', 1495262515, 1495262515, 11, 'normal'),
(12, 0, 'test', '测试1', 'test1', 'recommend', '/assets/img/qrcode.png', '', '', 'test1', 1497015727, 1497015727, 12, 'normal'),
(13, 0, 'test', '测试2', 'test2', 'recommend', '/assets/img/qrcode.png', '', '', 'test2', 1497015738, 1497015738, 13, 'normal');

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

--
-- 傾印資料表的資料 `config`
--

INSERT INTO `config` (`id`, `name`, `group`, `title`, `tip`, `type`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
(1, 'name', 'basic', 'Site name', '请填写站点名称', 'string', '運動神人', '', 'required', '', NULL),
(2, 'beian', 'basic', 'Beian', '粤ICP备15000000号-1', 'string', '', '', '', '', NULL),
(3, 'cdnurl', 'basic', 'Cdn url', '如果全站静态资源使用第三方云储存请配置该值', 'string', '', '', '', '', NULL),
(4, 'version', 'basic', 'Version', '如果静态资源有变动请重新配置该值', 'string', '1.0.0', '', 'required', '', NULL),
(5, 'timezone', 'basic', 'Timezone', '', 'string', 'Asia/Shanghai', '', 'required', '', NULL),
(6, 'forbiddenip', 'basic', 'Forbidden ip', '一行一条记录', 'text', '', '', '', '', NULL),
(7, 'languages', 'basic', 'Languages', '', 'array', '{\"backend\":\"zh-cn\",\"frontend\":\"zh-cn\"}', '', 'required', '', NULL),
(8, 'fixedpage', 'basic', 'Fixed page', '请尽量输入左侧菜单栏存在的链接', 'string', 'user/user', '', 'required', '', NULL),
(9, 'categorytype', 'dictionary', 'Category type', '', 'array', '{\"default\":\"Default\",\"page\":\"Page\",\"article\":\"Article\",\"test\":\"Test\"}', '', '', '', ''),
(10, 'configgroup', 'dictionary', 'Config group', '', 'array', '{\"basic\":\"Basic\",\"email\":\"Email\",\"dictionary\":\"Dictionary\",\"user\":\"User\",\"example\":\"Example\"}', '', '', '', ''),
(11, 'mail_type', 'email', 'Mail type', '选择邮件发送方式', 'select', '1', '[\"请选择\",\"SMTP\",\"Mail\"]', '', '', ''),
(12, 'mail_smtp_host', 'email', 'Mail smtp host', '错误的配置发送邮件会导致服务器超时', 'string', 'smtp.qq.com', '', '', '', ''),
(13, 'mail_smtp_port', 'email', 'Mail smtp port', '(不加密默认25,SSL默认465,TLS默认587)', 'string', '465', '', '', '', ''),
(14, 'mail_smtp_user', 'email', 'Mail smtp user', '（填写完整用户名）', 'string', '10000', '', '', '', ''),
(15, 'mail_smtp_pass', 'email', 'Mail smtp password', '（填写您的密码）', 'string', 'password', '', '', '', ''),
(16, 'mail_verify_type', 'email', 'Mail vertify type', '（SMTP验证方式[推荐SSL]）', 'select', '2', '[\"无\",\"TLS\",\"SSL\"]', '', '', ''),
(17, 'mail_from', 'email', 'Mail from', '', 'string', '10000@qq.com', '', '', '', ''),
(18, 'url', 'basic', '路徑', 'API路徑', 'array', '{\"furl\":\"https://sportgod.cc\",\"api\":\"https://sportgod.cc/api\",\"burl\":\"https://sportgod.cc/backend.php\"}', '{\"value1\":\"title1\",\"value2\":\"title2\"}', '', '', NULL);

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

--
-- 傾印資料表的資料 `test`
--

INSERT INTO `test` (`id`, `admin_id`, `category_id`, `category_ids`, `week`, `flag`, `genderdata`, `hobbydata`, `title`, `content`, `image`, `images`, `attachfile`, `keywords`, `description`, `city`, `json`, `price`, `views`, `startdate`, `activitytime`, `year`, `times`, `refreshtime`, `createtime`, `updatetime`, `deletetime`, `weigh`, `switch`, `status`, `state`) VALUES
(1, 0, 12, '12,13', 'monday', 'hot,index', 'male', 'music,reading', '我是一篇测试文章', '<p>我是测试内容</p>', '/assets/img/avatar.png', '/assets/img/avatar.png,/assets/img/qrcode.png', '/assets/img/avatar.png', '关键字', '描述', '广西壮族自治区/百色市/平果县', '{\"a\":\"1\",\"b\":\"2\"}', 0.00, 0, '2017-07-10', '2017-07-10 18:24:45', 2017, '18:24:45', 1499682285, 1499682526, 1499682526, NULL, 0, 1, 'normal', '1');

-- --------------------------------------------------------

--
-- 資料表結構 `user`
--

CREATE TABLE `user` (
  `id` int(10) UNSIGNED NOT NULL COMMENT 'ID',
  `group_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '组别ID',
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
  `verification` varchar(255) DEFAULT '' COMMENT '验证'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='会员表';

--
-- 傾印資料表的資料 `user`
--

INSERT INTO `user` (`id`, `group_id`, `username`, `nickname`, `password`, `salt`, `email`, `mobile`, `avatar`, `level`, `gender`, `birthday`, `bio`, `money`, `score`, `successions`, `maxsuccessions`, `prevtime`, `logintime`, `loginip`, `loginfailure`, `joinip`, `jointime`, `createtime`, `updatetime`, `token`, `status`, `verification`) VALUES
(2, 0, 'chao', 'chao', '26ce13a0c8dd05c819b1d41a20d5e74b', 'Ytn4go', '', '0928565121', '', 0, 0, NULL, '', 0.00, 0, 1, 1, 1628058393, 1628847672, '111.254.221.106', 0, '', NULL, 1626141516, 1628847672, '', '1', '');

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

--
-- 傾印資料表的資料 `user_group`
--

INSERT INTO `user_group` (`id`, `name`, `rules`, `createtime`, `updatetime`, `status`) VALUES
(1, '默认组', '1,2,3,4,5,6,7,8,9,10,11,12', 1515386468, 1516168298, 'normal');

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

--
-- 傾印資料表的資料 `user_rule`
--

INSERT INTO `user_rule` (`id`, `pid`, `name`, `title`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(1, 0, 'index', 'Frontend', '', 1, 1516168079, 1516168079, 1, 'normal'),
(2, 0, 'api', 'API Interface', '', 1, 1516168062, 1516168062, 2, 'normal'),
(3, 1, 'user', 'User Module', '', 1, 1515386221, 1516168103, 12, 'normal'),
(4, 2, 'user', 'User Module', '', 1, 1515386221, 1516168092, 11, 'normal'),
(5, 3, 'index/user/login', 'Login', '', 0, 1515386247, 1515386247, 5, 'normal'),
(6, 3, 'index/user/register', 'Register', '', 0, 1515386262, 1516015236, 7, 'normal'),
(7, 3, 'index/user/index', 'User Center', '', 0, 1516015012, 1516015012, 9, 'normal'),
(8, 3, 'index/user/profile', 'Profile', '', 0, 1516015012, 1516015012, 4, 'normal'),
(9, 4, 'api/user/login', 'Login', '', 0, 1515386247, 1515386247, 6, 'normal'),
(10, 4, 'api/user/register', 'Register', '', 0, 1515386262, 1516015236, 8, 'normal'),
(11, 4, 'api/user/index', 'User Center', '', 0, 1516015012, 1516015012, 10, 'normal'),
(12, 4, 'api/user/profile', 'Profile', '', 0, 1516015012, 1516015012, 3, 'normal');

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

--
-- 傾印資料表的資料 `user_token`
--

INSERT INTO `user_token` (`token`, `user_id`, `createtime`, `expiretime`) VALUES
('67e4c8535b54f33c8bd013041d87faf0d72266af', 2, 1628847672, 1631439672),
('e0bf6c63db235bb13a3f15d966c6b4c19b6d5d95', 2, 1626141522, 1628733522);

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
-- 資料表索引 `ems`
--
ALTER TABLE `ems`
  ADD PRIMARY KEY (`id`) USING BTREE;

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
  ADD KEY `email` (`email`),
  ADD KEY `mobile` (`mobile`);

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
-- 資料表索引 `version`
--
ALTER TABLE `version`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID', AUTO_INCREMENT=2;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `admin_log`
--
ALTER TABLE `admin_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID', AUTO_INCREMENT=95;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `area`
--
ALTER TABLE `area`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `article`
--
ALTER TABLE `article`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `article_cat`
--
ALTER TABLE `article_cat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `article_fav`
--
ALTER TABLE `article_fav`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `article_msg`
--
ALTER TABLE `article_msg`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `attachment`
--
ALTER TABLE `attachment`
  MODIFY `id` int(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID', AUTO_INCREMENT=2;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `auth_group`
--
ALTER TABLE `auth_group`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `auth_rule`
--
ALTER TABLE `auth_rule`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `category`
--
ALTER TABLE `category`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `config`
--
ALTER TABLE `config`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `ems`
--
ALTER TABLE `ems`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `sms`
--
ALTER TABLE `sms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `test`
--
ALTER TABLE `test`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID', AUTO_INCREMENT=2;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user`
--
ALTER TABLE `user`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID', AUTO_INCREMENT=3;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user_group`
--
ALTER TABLE `user_group`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user_money_log`
--
ALTER TABLE `user_money_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user_rule`
--
ALTER TABLE `user_rule`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user_score_log`
--
ALTER TABLE `user_score_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `version`
--
ALTER TABLE `version`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
