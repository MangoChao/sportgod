<?php

// 公共助手函数

use Symfony\Component\VarExporter\VarExporter;

if (!function_exists('__')) {

    /**
     * 获取语言变量值
     * @param string $name 语言变量名
     * @param array  $vars 动态变量值
     * @param string $lang 语言
     * @return mixed
     */
    function __($name, $vars = [], $lang = '')
    {
        if (is_numeric($name) || !$name) {
            return $name;
        }
        if (!is_array($vars)) {
            $vars = func_get_args();
            array_shift($vars);
            $lang = '';
        }
        return \think\Lang::get($name, $vars, $lang);
    }
}

if (!function_exists('format_bytes')) {

    /**
     * 将字节转换为可读文本
     * @param int    $size      大小
     * @param string $delimiter 分隔符
     * @return string
     */
    function format_bytes($size, $delimiter = '')
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        for ($i = 0; $size >= 1024 && $i < 6; $i++) {
            $size /= 1024;
        }
        return round($size, 2) . $delimiter . $units[$i];
    }
}

if (!function_exists('datetime')) {

    /**
     * 将时间戳转换为日期时间
     * @param int    $time   时间戳
     * @param string $format 日期时间格式
     * @return string
     */
    function datetime($time, $format = 'Y-m-d H:i:s')
    {
        $time = is_numeric($time) ? $time : strtotime($time);
        return date($format, $time);
    }
}

if (!function_exists('human_date')) {

    /**
     * 获取语义化时间
     * @param int $time  时间
     * @param int $local 本地时间
     * @return string
     */
    function human_date($time, $local = null)
    {
        return \fast\Date::human($time, $local);
    }
}

if (!function_exists('cdnurl')) {

    /**
     * 获取上传资源的CDN的地址
     * @param string  $url    资源相对地址
     * @param boolean $domain 是否显示域名 或者直接传入域名
     * @return string
     */
    function cdnurl($url, $domain = false)
    {
        $regex = "/^((?:[a-z]+:)?\/\/|data:image\/)(.*)/i";
        $url = preg_match($regex, $url) ? $url : \think\Config::get('upload.cdnurl') . $url;
        if ($domain && !preg_match($regex, $url)) {
            $domain = is_bool($domain) ? request()->domain() : $domain;
            $url = $domain . $url;
        }
        return $url;
    }
}


if (!function_exists('is_really_writable')) {

    /**
     * 判断文件或文件夹是否可写
     * @param string $file 文件或目录
     * @return    bool
     */
    function is_really_writable($file)
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return is_writable($file);
        }
        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . md5(mt_rand());
            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }
            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return true;
        } elseif (!is_file($file) or ($fp = @fopen($file, 'ab')) === false) {
            return false;
        }
        fclose($fp);
        return true;
    }
}

if (!function_exists('rmdirs')) {

    /**
     * 删除文件夹
     * @param string $dirname  目录
     * @param bool   $withself 是否删除自身
     * @return boolean
     */
    function rmdirs($dirname, $withself = true)
    {
        if (!is_dir($dirname)) {
            return false;
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        if ($withself) {
            @rmdir($dirname);
        }
        return true;
    }
}

// if (!function_exists('copydirs')) {

//     /**
//      * 复制文件夹
//      * @param string $source 源文件夹
//      * @param string $dest   目标文件夹
//      */
//     function copydirs($source, $dest)
//     {
//         if (!is_dir($dest)) {
//             mkdir($dest, 0755, true);
//         }
//         foreach (
//             $iterator = new RecursiveIteratorIterator(
//                 new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
//                 RecursiveIteratorIterator::SELF_FIRST
//             ) as $item
//         ) {
//             if ($item->isDir()) {
//                 $sontDir = $dest . DS . $iterator->getSubPathName();
//                 if (!is_dir($sontDir)) {
//                     mkdir($sontDir, 0755, true);
//                 }
//             } else {
//                 copy($item, $dest . DS . $iterator->getSubPathName());
//             }
//         }
//     }
// }

if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst($string)
    {
        return mb_strtoupper(mb_substr($string, 0, 1)) . mb_strtolower(mb_substr($string, 1));
    }
}

if (!function_exists('addtion')) {

    /**
     * 附加关联字段数据
     * @param array $items  数据列表
     * @param mixed $fields 渲染的来源字段
     * @return array
     */
    function addtion($items, $fields)
    {
        if (!$items || !$fields) {
            return $items;
        }
        $fieldsArr = [];
        if (!is_array($fields)) {
            $arr = explode(',', $fields);
            foreach ($arr as $k => $v) {
                $fieldsArr[$v] = ['field' => $v];
            }
        } else {
            foreach ($fields as $k => $v) {
                if (is_array($v)) {
                    $v['field'] = isset($v['field']) ? $v['field'] : $k;
                } else {
                    $v = ['field' => $v];
                }
                $fieldsArr[$v['field']] = $v;
            }
        }
        foreach ($fieldsArr as $k => &$v) {
            $v = is_array($v) ? $v : ['field' => $v];
            $v['display'] = isset($v['display']) ? $v['display'] : str_replace(['_ids', '_id'], ['_names', '_name'], $v['field']);
            $v['primary'] = isset($v['primary']) ? $v['primary'] : '';
            $v['column'] = isset($v['column']) ? $v['column'] : 'name';
            $v['model'] = isset($v['model']) ? $v['model'] : '';
            $v['table'] = isset($v['table']) ? $v['table'] : '';
            $v['name'] = isset($v['name']) ? $v['name'] : str_replace(['_ids', '_id'], '', $v['field']);
        }
        unset($v);
        $ids = [];
        $fields = array_keys($fieldsArr);
        foreach ($items as $k => $v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $ids[$n] = array_merge(isset($ids[$n]) && is_array($ids[$n]) ? $ids[$n] : [], explode(',', $v[$n]));
                }
            }
        }
        $result = [];
        foreach ($fieldsArr as $k => $v) {
            if ($v['model']) {
                $model = new $v['model'];
            } else {
                $model = $v['name'] ? \think\Db::name($v['name']) : \think\Db::table($v['table']);
            }
            $primary = $v['primary'] ? $v['primary'] : $model->getPk();
            $result[$v['field']] = $model->where($primary, 'in', $ids[$v['field']])->column("{$primary},{$v['column']}");
        }

        foreach ($items as $k => &$v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $curr = array_flip(explode(',', $v[$n]));

                    $v[$fieldsArr[$n]['display']] = implode(',', array_intersect_key($result[$n], $curr));
                }
            }
        }
        return $items;
    }
}

if (!function_exists('var_export_short')) {

    /**
     * 返回打印数组结构
     * @param string $var 数组
     * @return string
     */
    function var_export_short($var)
    {
        return VarExporter::export($var);
    }
}

if (!function_exists('letter_avatar')) {
    /**
     * 首字母头像
     * @param $text
     * @return string
     */
    function letter_avatar($text)
    {
        $total = unpack('L', hash('adler32', $text, true))[1];
        $hue = $total % 360;
        list($r, $g, $b) = hsv2rgb($hue / 360, 0.3, 0.9);

        $bg = "rgb({$r},{$g},{$b})";
        $color = "#ffffff";
        $first = mb_strtoupper(mb_substr($text, 0, 1));
        $src = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="100" width="100"><rect fill="' . $bg . '" x="0" y="0" width="100" height="100"></rect><text x="50" y="50" font-size="50" text-copy="fast" fill="' . $color . '" text-anchor="middle" text-rights="admin" alignment-baseline="central">' . $first . '</text></svg>');
        $value = 'data:image/svg+xml;base64,' . $src;
        return $value;
    }
}

if (!function_exists('hsv2rgb')) {
    function hsv2rgb($h, $s, $v)
    {
        $r = $g = $b = 0;

        $i = floor($h * 6);
        $f = $h * 6 - $i;
        $p = $v * (1 - $s);
        $q = $v * (1 - $f * $s);
        $t = $v * (1 - (1 - $f) * $s);

        switch ($i % 6) {
            case 0:
                $r = $v;
                $g = $t;
                $b = $p;
                break;
            case 1:
                $r = $q;
                $g = $v;
                $b = $p;
                break;
            case 2:
                $r = $p;
                $g = $v;
                $b = $t;
                break;
            case 3:
                $r = $p;
                $g = $q;
                $b = $v;
                break;
            case 4:
                $r = $t;
                $g = $p;
                $b = $v;
                break;
            case 5:
                $r = $v;
                $g = $p;
                $b = $q;
                break;
        }

        return [
            floor($r * 255),
            floor($g * 255),
            floor($b * 255)
        ];
    }
}

if (!function_exists('check_nav_active')) {
    /**
     * 检测会员中心导航是否高亮
     */
    function check_nav_active($url, $classname = 'active')
    {
        $auth = \app\common\library\Auth::instance();
        $requestUrl = $auth->getRequestUri();
        $url = ltrim($url, '/');
        return $requestUrl === str_replace(".", "/", $url) ? $classname : '';
    }
}

if (!function_exists('check_cors_request')) {
    /**
     * 跨域检测
     */
    function check_cors_request()
    {
        if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN']) {
            $info = parse_url($_SERVER['HTTP_ORIGIN']);
            $domainArr = explode(',', config('fastadmin.cors_request_domain'));
            $domainArr[] = request()->host(true);
            if (in_array("*", $domainArr) || in_array($_SERVER['HTTP_ORIGIN'], $domainArr) || (isset($info['host']) && in_array($info['host'], $domainArr))) {
                header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
            } else {
                header('HTTP/1.1 403 Forbidden');
                exit;
            }

            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');

            if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
                }
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                    header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
                }
                exit;
            }
        }
    }
}

if (!function_exists('xss_clean')) {
    /**
     * 清理XSS
     */
    function xss_clean($content, $is_image = false)
    {
        return \app\common\library\Security::instance()->xss_clean($content, $is_image);
    }
}

if (!function_exists('curl_post')) {

    /**
     * curl post
     *
     * @param $url
     * @param $postData
     * @param $options
     * @return string
     */
    function curl_post($url = '', $postData = '', $options = [])
    {
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, false); // 0不带头文件，1带头文件（返回值中带有头文件）
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15); //设置等待时间
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}

if (!function_exists('file_get_content')) {
    function file_get_content($url)
    {
        // if (function_exists('file_get_contents')) {
        // $file_contents = @file_get_contents($url);
        // }
        // if ($file_contents == '') {
        $ch = curl_init();
        $timeout = 30;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        // }
        return $file_contents;
    }
}

if (!function_exists('create_mpg_aes_encrypt')) {
    /**
     * AES加密
     */
    function create_mpg_aes_encrypt($parameter = null, $key = "", $iv = "")
    {
        $return_str = '';
        if (!empty($parameter)) {
            //將參數經過 URL ENCODED QUERY STRING
            $return_str = http_build_query($parameter);
        }
        return trim(bin2hex(openssl_encrypt(addpadding($return_str), 'aes-256-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv)));
    }
}


if (!function_exists('addpadding')) {
    function addpadding($string, $blocksize = 32)
    {
        $len = strlen($string);
        $pad = $blocksize - ($len % $blocksize);
        $string .= str_repeat(chr($pad), $pad);
        return $string;
    }
}


if (!function_exists('create_aes_decrypt')) {
    /**
     * AES解密
     */
    function create_aes_decrypt($parameter = "", $key = "", $iv = "")
    {
        return strippadding(openssl_decrypt(hex2bin($parameter), 'AES-256-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv));
    }
}

if (!function_exists('strippadding')) {
    function strippadding($string)
    {
        $slast = ord(substr($string, -1));
        $slastc = chr($slast);
        $pcheck = substr($string, -$slast);
        if (preg_match("/$slastc{" . $slast . "}/", $string)) {
            $string = substr($string, 0, strlen($string) - $slast);
            return $string;
        } else {
            return false;
        }
    }
}


if (!function_exists('toDotNetUrlEncode')) {
    function toDotNetUrlEncode($source)
    {
        $search = [
            '%2d',
            '%5f',
            '%2e',
            '%21',
            '%2a',
            '%28',
            '%29',
        ];
        $replace = [
            '-',
            '_',
            '.',
            '!',
            '*',
            '(',
            ')',
        ];
        $replaced = str_replace($search, $replace, $source);
        return $replaced;
    }
}

/**轉譯yt連結 */
if (!function_exists('getYoutubeEmbedUrl')) {
    function getYoutubeEmbedUrl($url)
    {
        $baseUrlRegex = '/youtu.be\/([a-zA-Z0-9_-]+)\??/i';
        $longUrlRegex = '/youtube.com\/((?:embed)|(?:watch))((?:\?v\=)|(?:\/))([a-zA-Z0-9_-]+)/i';
        $shortsUrlRegex = '/youtube.com\/shorts\/([a-zA-Z0-9_-]+)/i';

        $youtube_id = "";
        if (preg_match($longUrlRegex, $url, $matches)) {
            $youtube_id = $matches[count($matches) - 1];
        }

        if (preg_match($baseUrlRegex, $url, $matches)) {
            $youtube_id = $matches[count($matches) - 1];
        }

        if (preg_match($shortsUrlRegex, $url, $matches)) {
            $youtube_id = $matches[count($matches) - 1];
        }

        if ($youtube_id) {
            return 'https://www.youtube.com/embed/' . $youtube_id;
        } else {
            return "";
        }
    }
}

if (!function_exists('getTeachCatList')) {
    function getTeachCatList()
    {
        return [
            0 => '請選擇分類',
            1 => '籌碼學策略',
            2 => '算牌策略',
            3 => '牌路打法',
            4 => '心態學',
        ];
    }
}


if (!function_exists('getTimeDescribe')) {
    function getTimeDescribe($timestamp)
    {
        $currentTime = time();
        $timeDifference = $currentTime - $timestamp;

        if ($timeDifference < 60) {
            return "剛剛";
        } elseif ($timeDifference < 3600) {
            $minutes = floor($timeDifference / 60);
            return $minutes . "分鐘前";
        } elseif ($timeDifference < 86400) {
            $hours = floor($timeDifference / 3600);
            return $hours . "小時前";
        } elseif ($timeDifference < 604800) {
            $days = floor($timeDifference / 86400);
            if ($days == 1) {
                return "昨天";
            } else {
                return $days . "天前";
            }
        } elseif ($timeDifference < 2592000) {
            $weeks = floor($timeDifference / 604800);
            return $weeks . "週前";
        } elseif ($timeDifference < 31536000) {
            $months = floor($timeDifference / 2592000);
            return $months . "個月前";
        } else {
            $years = floor($timeDifference / 31536000);
            return $years . "年前";
        }
    }
}

$GLOBALS['redis'] = redisInit();
function redisInit()
{
    $redis = null;
    if (extension_loaded('redis')) {
        $redisConfig = \think\Config::get("redis");
        if ($redisConfig) {
            $redisClass = new \app\common\library\token\driver\Redis($redisConfig);
            $redis = $redisClass->handler();
        }
    } else {
        // \think\Log::notice("need redis");
    }
    return $redis;
}

if (!function_exists('getRedis')) {
    function getRedis()
    {
        return $GLOBALS['redis'];
    }
}

if (!function_exists('calculateComply')) {
    function calculateComply(&$mPred, $masterScore, $guestsScore, $catId, $modelPred)
    {
        $mPred->master_score = $masterScore;
        $mPred->guests_score = $guestsScore;

        // === 無效賽事 ===
        if ($masterScore == -1 || $guestsScore == -1) {
            $mPred->comply = -1;
            $mPred->result_ratio = 0;
            $mPred->save();
            return;
        }

        if ($mPred->pred_type == 1) {
            // ======== 讓分盤（修正版） ========
            $hasHomeLine  = !empty($mPred->master_refund);   // 主讓?
            $hasAwayLine  = !empty($mPred->guests_refund);   // 客讓?
            $lineStr      = $hasHomeLine ? $mPred->master_refund : $mPred->guests_refund;
            if (!$lineStr) {
                \think\Log::notice('讓分有誤, pred_id:' . $mPred->id);
                return;
            }

            // 盤口方：true=主隊是盤口(主讓)、false=客隊是盤口(客讓)
            $isHomeLine = $hasHomeLine;

            // 玩家押注方：true=押主、false=押客
            $betOnHome = ((int)$mPred->winteam) === 1;

            // 以「盤口方」做分差：盤口方分數 - 對手分數
            $diff = $isHomeLine ? ($masterScore - $guestsScore) : ($guestsScore - $masterScore);

            // 解析：H 與 P，記住是 '+' 還是 '-'
            $isPlus = false;
            $isMinus = false;
            $hasPercent = false;
            if (strpos($lineStr, '+') !== false) {
                $hasPercent = true;
                $isPlus = true;
                [$H, $P] = explode('+', $lineStr) + [0, 0];
            } elseif (strpos($lineStr, '-') !== false) {
                $hasPercent = true;
                $isMinus = true;
                [$H, $P] = explode('-', $lineStr) + [0, 0];
            } else {
                $H = $lineStr;
                $P = 0;
            }

            $H = (float)$H;
            $P = (float)$P;

            // 玩家是否「押盤口方」
            $bettorPickedLineTeam = ($isHomeLine && $betOnHome) || (!$isHomeLine && !$betOnHome);

            // === 判斷輸贏 ===
            if ($diff > $H) {
                // 盤口方過盤：押盤口方贏
                $mPred->comply       = $bettorPickedLineTeam ? 1 : 2;
                $mPred->result_ratio = $bettorPickedLineTeam ? +100 : -100;
            } elseif ($diff < $H) {
                // 盤口方未過盤：押盤口方輸
                $mPred->comply       = $bettorPickedLineTeam ? 2 : 1;
                $mPred->result_ratio = $bettorPickedLineTeam ? -100 : +100;
            } else {
                // 平盤
                if ($hasPercent && $P > 0) {
                    if ($isPlus) {
                        // +P：盤口方贏 P%
                        $mPred->comply       = $bettorPickedLineTeam ? 1 : 2;
                        $mPred->result_ratio = $bettorPickedLineTeam ? +$P : -$P;
                    } elseif ($isMinus) {
                        // -P：盤口方輸 P%
                        $mPred->comply       = $bettorPickedLineTeam ? 2 : 1;
                        $mPred->result_ratio = $bettorPickedLineTeam ? -$P : +$P;
                    }
                } else {
                    // 無百分比 → 和局
                    $mPred->comply       = 3;
                    $mPred->result_ratio = 0;
                }
            }
        } else {
            // ======== 大小盤（修正版） ========
            $bigscore = $mPred->bigscore;
            $isPlus = false;
            $isMinus = false;
            $hasPercent = false;

            if (strpos($bigscore, '+') !== false) {
                $hasPercent = true;
                $isPlus = true;
                [$T, $P] = explode('+', $bigscore) + [0, 0];
            } elseif (strpos($bigscore, '-') !== false) {
                $hasPercent = true;
                $isMinus = true;
                [$T, $P] = explode('-', $bigscore) + [0, 0];
            } else {
                $T = $bigscore;
                $P = 0;
            }

            $T = (float)$T;
            $P = (float)$P;

            $sum = $masterScore + $guestsScore;
            $isOver = ((int)$mPred->bigsmall) === 1; // 1=押大, 0=押小

            if ($sum > $T) {
                // 大分贏，小分輸
                $mPred->comply       = $isOver ? 1 : 2;
                $mPred->result_ratio = $isOver ? +100 : -100;
            } elseif ($sum < $T) {
                // 小分贏，大分輸
                $mPred->comply       = $isOver ? 2 : 1;
                $mPred->result_ratio = $isOver ? -100 : +100;
            } else {
                // 平盤
                if ($hasPercent && $P > 0) {
                    if ($isPlus) {
                        // +P：押大贏 P%、押小輸 P%
                        $mPred->comply       = $isOver ? 1 : 2;
                        $mPred->result_ratio = $isOver ? +$P : -$P;
                    } elseif ($isMinus) {
                        // -P：押大輸 P%、押小贏 P%
                        $mPred->comply       = $isOver ? 2 : 1;
                        $mPred->result_ratio = $isOver ? -$P : +$P;
                    }
                } else {
                    // 無百分比 → 和局
                    $mPred->comply       = 3;
                    $mPred->result_ratio = 0;
                }
            }
        }

        // === 模擬勝率調整 ===
        if ($mPred->isauto == 1 && $mPred->isread == 0 && $mPred->comply == 2) {
            $stats = $modelPred->alias('p')
                ->join('event e', 'e.id = p.event_id')
                ->where('p.analyst_id', $mPred->analyst_id)
                ->where('e.event_category_id', $catId)
                ->where('p.comply', 'in', [1, 2])
                ->field([
                    "SUM(CASE WHEN p.comply = 1 THEN 1 ELSE 0 END) AS wins",
                    "SUM(CASE WHEN p.comply = 2 THEN 1 ELSE 0 END) AS loses"
                ])
                ->find();

            $wins  = (int)($stats['wins'] ?? 0);
            $loses = (int)($stats['loses'] ?? 0);
            $total = $wins + $loses;
            $rate  = $total > 0 ? ($wins / $total) : 0;

            if ($rate < 0.8) {
                $mPred->comply = 1;
                $mPred->result_ratio = abs($mPred->result_ratio);
                if ($mPred->pred_type == 1) {
                    $mPred->winteam = $mPred->winteam ? 0 : 1;
                } elseif ($mPred->pred_type == 2) {
                    $mPred->bigsmall = $mPred->bigsmall ? 0 : 1;
                }

                \think\Log::notice(sprintf(
                    '[模擬調整] analyst_id=%d cat=%d 原勝率=%.2f%%，已反轉 pred_id=%d winteam=%d bigsmall=%d comply=%d',
                    $mPred->analyst_id,
                    $catId,
                    $rate * 100,
                    $mPred->id,
                    $mPred->winteam,
                    $mPred->bigsmall,
                    $mPred->comply
                ));
            }
        }

        $mPred->save();
    }
}

const cookie = __DIR__.'/cookie.txt';
function loginSetCookie()
{
    $gameurl = "https://agiv-2.hau888.net";
    $url = $gameurl . "/login.php";
    $post = [
        'luserid' => '35817',
        'lpassword' => 'a123456',
        'paction' => 'login-processing',
        'remember' => 1
    ];
    // $cookie = './cookie.txt';
    // $cookie = __DIR__.'/cookie.txt';

    \think\Log::notice("模擬登錄 : ".cookie);
    
    $curl = curl_init(); //初始化curl模塊
    curl_setopt($curl, CURLOPT_URL, $url); //登錄提交的地址
    curl_setopt($curl, CURLOPT_HEADER, 0); //是否显示头信息
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0); //是否自動顯示返回的信息
    curl_setopt($curl, CURLOPT_COOKIEJAR, cookie); //設置Cookie信息保存在指定的文件中
    curl_setopt($curl, CURLOPT_POST, 1); //post方式提交

    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post)); //要提交的信息
    curl_exec($curl); //執行cURL
    curl_close($curl); //關閉cURL資源，並且釋放系統資源
}

