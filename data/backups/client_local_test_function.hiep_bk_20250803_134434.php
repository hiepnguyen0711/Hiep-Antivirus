<?php
include "resize-class.php";
function resize_img($file, $option, $w, $h, $folder = 'thumb')
{
    $resizeObj = new resize('../uploads/images/' . $file);
    //Resize image (options: exact, portrait, landscape, auto, crop)
    $resizeObj->resizeImage($w, $h, $option);
    $resizeObj->saveImage('../uploads/images/' . $folder . '/' . $file, 100);
    return $file;
}
function get_size_img($file)
{
    $size = getimagesize($file);
    list($width, $height) = $size;
    return $width . 'x' . $height;
}
// function get_json($object, $key = '', $attributes = '')
// {
//     $string = file_get_contents(URLPATH . "admin/lib/config.json");
//     $arr_config   = json_decode($string, true);
//     if ($key == '') {
//         return $arr_config[$object];
//     } elseif ($key != '' and $attributes == '') {
//         return $arr_config[$object][$key];
//     } else {
//         return $arr_config[$object][$key][$attributes];
//     }
// }
function get_json($object, $key = '', $attributes = '')
{
    global $config;
    $arr_config   = $config;
    if ($key == '') {
        return $arr_config[$object];
    } elseif ($key != '' and $attributes == '') {
        return $arr_config[$object][$key];
    } else {
        return $arr_config[$object][$key][$attributes];
    }
}

function numberformat($number)
{
    $number2 = str_replace(',', '.',  number_format($number));
    return $number2;
}
function token()
{
    $token = sha1(time() . rand(0, 99999));
    $_SESSION['token'] = $token;
    return $token;
}
function getthoigiandang($ngaydang)
{
    $date1 = $ngaydang;
    $date2 = date('Y-m-d H:i:s', time());

    $diff = abs(strtotime($date2) - strtotime($date1));

    $years = floor($diff / (365 * 60 * 60 * 24));
    $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
    $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));
    $hours = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24) / (60 * 60));
    $minutes = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24 - $hours * 60 * 60) / 60);
    $seconds = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24 - $hours * 60 * 60 - $minutes * 60));
    $thoigian =  $years . " năm, " . $months . " tháng, " . $days . " ngày, " . $hours . " gio, " . $minutes . " phút, " . $seconds . " giay";
    if ($years == 0) {
        $thoigian = $months . ' tháng';
    }
    if ($months == 0) {
        $thoigian = $days . ' ngày';
    }
    if ($days == 0) {
        $thoigian = $hours . ' giờ';
    }
    if ($hours == 0) {
        $thoigian = $minutes . ' phút';
    }
    if ($minutes == 0) {
        $thoigian = $seconds . ' giây';
    }
    return $thoigian . ' trước';
}

function thoigiandang($ngaydang)
{
    // Nếu là chuỗi định dạng ngày (ví dụ: "2025-05-14 14:56:47"), chuyển sang timestamp
    if (!is_numeric($ngaydang)) {
        $ngaydang = strtotime($ngaydang);
        if ($ngaydang === false) return 'Không xác định thời gian';
    }

    // Lấy thời gian hiện tại và tính khoảng cách
    $current_time = time();
    $diff = abs($current_time - $ngaydang);

    // Tính toán các đơn vị thời gian
    $years = floor($diff / (365 * 24 * 60 * 60));
    $months = floor(($diff % (365 * 24 * 60 * 60)) / (30 * 24 * 60 * 60));
    $days = floor(($diff % (30 * 24 * 60 * 60)) / (24 * 60 * 60));
    $hours = floor(($diff % (24 * 60 * 60)) / (60 * 60));
    $minutes = floor(($diff % (60 * 60)) / 60);
    $seconds = $diff % 60;

    // Xây dựng chuỗi kết quả
    $thoigian = '';
    if ($years > 0) {
        $thoigian = $years . ' năm';
    } elseif ($months > 0) {
        $thoigian = $months . ' tháng';
    } elseif ($days > 0) {
        $thoigian = $days . ' ngày';
    } elseif ($hours > 0) {
        $thoigian = $hours . ' giờ';
    } elseif ($minutes > 0) {
        $thoigian = $minutes . ' phút';
    } else {
        $thoigian = $seconds . ' giây';
    }

    return $thoigian . ' trước';
}
function catchuoi($text, $n = 80)
{
    // string is shorter than n, return as is
    $text = strip_tags($text);
    if (strlen($text) <= $n) {
        return $text;
    }
    $text = substr($text, 0, $n);
    if ($text[$n - 1] == ' ') {
        return trim($text) . "...";
    }
    $x  = explode(" ", $text);
    $sz = sizeof($x);
    if ($sz <= 1) {
        return $text . "...";
    }
    $x[$sz - 1] = '';
    return trim(implode(" ", $x)) . "...";
}

function bodautv($str)
{
    $unicode = array(
        'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
        'd' => 'đ',
        'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'i' => 'í|ì|ỉ|ĩ|ị',
        'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
        'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
        'D' => 'Đ',
        'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
        'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
        'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
        'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
        'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
    );

    foreach ($unicode as $nonUnicode => $uni) {
        $str = preg_replace("/($uni)/i", $nonUnicode, $str);
    }
    $str = preg_replace("/( )/", '-', $str);
    $str = preg_replace("/(\?)/", '-', $str);
    $str = preg_replace("/(:)/", '-', $str);
    $str = preg_replace("/(&)/", '', $str);
    $str = preg_replace("/,/", '-', $str);
    $str = preg_replace("/-+-/", '-', $str);
    $str = preg_replace('"/"', '-', $str);

    $str = trim($str, '-');
    return $str;
}
function replaceHTMLCharacter($str)
{
    $str  = preg_replace('/&/',        '&amp;',    $str);
    $str  = preg_replace('/</',        '&lt;',        $str);
    $str  = preg_replace('/>/',        '&gt;',        $str);
    $str  = preg_replace('/\"/',    '&quot;',    $str);
    $str  = preg_replace('/\'/',    '&apos;',    $str);
    return $str;
}

function chuoird($length)
{
    $str = '';
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $size = strlen($chars);
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[rand(0, $size - 1)];
    }
    return $str;
}
function chuoinum($length)
{
    $str = '';
    $chars = "0123456789";
    $size = strlen($chars);
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[rand(0, $size - 1)];
    }
    return $str;
}
function check_ptram($giacu, $giamoi)
{
    return round((($giacu - $giamoi) * 100 / $giacu));
}



//upload_file
function Uploadfile($file, $type, $folder, $name)
{
    if (isset($_FILES[$file]) && !$_FILES[$file]['error']) {
        $error = 0;
        $duoi = explode('.', $_FILES[$file]['name']); // tách chuỗi khi gặp dấu .
        $duoi = $duoi[(count($duoi) - 1)]; //lấy ra đuôi file

        $file_type = $_FILES[$file]["type"];
        $file_size = $_FILES[$file]["size"];
        $limit_size = 2000000;
        if ($type == 'file') {
            $limit_size = 5000000;
        }
        if ($file_size < $limit_size) {
            if ($type == 'images') {
                if ($file_type == 'image/svg' || $file_type == 'image/webp' || $file_type == 'image/jpg' || $file_type == 'image/png' || $file_type == 'image/jpeg' || $file_type == 'image/gif') {
                    $error = $error + 0;
                } else {
                    $error = $error + 1;
                }
            } elseif ($type == 'file') {
                if ($file_type == 'audio/mpeg' || 'video/mp4' || $file_type == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' || $file_type == 'application/vnd.ms-excel' || $file_type == 'application/pdf' || $file_type == 'application/msword' || $file_type == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                    $error = $error + 0;
                } else {
                    $error = $error + 1;
                }
            }
            if ($error == 0) {
                $file_name = $name . '.' . $duoi;
                if (file_exists($folder . $file_name) == 0) {
                    $file_name_news = $file_name;
                } else {
                    $file_name_news = $name . '-' . rand(1, 999) . '.' . $duoi;
                }

                if (move_uploaded_file($_FILES[$file]['tmp_name'], $folder . $file_name_news)) {
                    return $file_name_news;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    } else {
        return false;
    }
}
function multiple_Uploadfile($file, $type, $folder, $name)
{
    $total = count($_FILES[$file]['name']);
    //return $_FILES[$file]['tmp_name'][1];
    $list_file = '';
    for ($i = 0; $i < $total; $i++) {

        if ($_FILES[$file]['name'][$i] != '' && !$_FILES[$file][$i]['error']) {
            $error = 0;
            $duoi = explode('.', $_FILES[$file]['name'][$i]); // tách chuỗi khi gặp dấu .
            $duoi = $duoi[(count($duoi) - 1)]; //lấy ra đuôi file

            $file_type = $_FILES[$file]["type"][$i];
            $file_size = $_FILES[$file]["size"][$i];
            $limit_size = 2000000;
            if ($type == 'file') {
                $limit_size = 5000000;
            }
            if ($file_size < $limit_size) {
                if ($type == 'images') {
                    if ($file_type == 'image/svg' || $file_type == 'image/webp' || 'image/jpg' || $file_type == 'image/png' || $file_type == 'image/jpeg' || $file_type == 'image/gif') {
                        $error = $error + 0;
                    } else {
                        $error = $error + 1;
                    }
                } elseif ($type == 'file') {
                    if ($file_type == 'audio/mpeg' || 'video/mp4' || $file_type == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' || $file_type == 'application/vnd.ms-excel' || $file_type == 'application/pdf' || $file_type == 'application/msword' || $file_type == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                        $error = $error + 0;
                    } else {
                        $error = $error + 1;
                    }
                }
                if ($error == 0) {
                    $file_name = $name . '-' . rand(1, 999) . '.' . $duoi;
                    if (file_exists($folder . $file_name) == 0) {
                        $file_name_news = $file_name;
                    } else {
                        $file_name_news = $i . '-' . $file_name;
                    }
                    if (move_uploaded_file($_FILES[$file]['tmp_name'][$i], $folder . $file_name_news)) {
                        $list_file .= $file_name_news . ',';
                    } else {
                        $list_file .= '';
                    }
                } else {
                    $list_file .= '';
                }
            } else {
                $list_file .= '';
            }
        } else {
            $list_file .= '';
        }
    }
    $list_file = trim($list_file, ',');
    return $list_file;
}
/**
 * Enhanced shell detection function
 * Compatible with PHP 5.6+ and 7.x
 * Detects various types of malicious code patterns
 */
function check_shell($text)
{
    // Basic shell patterns
    $basic_patterns = array(
        '<?php', '<?=', '<%', '<script',
        'eval(', 'exec(', 'system(', 'shell_exec(', 'passthru(',
        'base64_decode(', 'gzinflate(', 'str_rot13(',
        'file_get_contents(', 'file_put_contents(', 'fopen(', 'fwrite(',
        'readdir(', 'scandir(', 'opendir(',
        'ini_get(', 'ini_set(', 'ini_restore(',
        'phpinfo(', 'show_source(', 'highlight_file(',
        '$_GET', '$_POST', '$_REQUEST', '$_COOKIE', '$_SESSION',
        '$_F=__FILE__;', '$_SERVER', 'GLOBALS',
        '<form', '<input', '<button', '<iframe'
    );

    // Advanced malware patterns
    $advanced_patterns = array(
        'chr(', 'ord(', 'hexdec(', 'dechex(',
        'pack(', 'unpack(',
        'create_function(', 'call_user_func(',
        'preg_replace.*\/e', 'assert(',
        'include_once', 'require_once',
        'ob_start(', 'ob_get_contents(',
        'error_reporting(0)', '@error_reporting',
        'set_time_limit(0)', '@set_time_limit',
        'ignore_user_abort', 'register_shutdown_function'
    );

    // Webshell specific patterns
    $webshell_patterns = array(
        'c99', 'r57', 'wso', 'b374k', 'adminer',
        'shell_exec', 'backdoor', 'rootkit',
        'FilesMan', 'Sec-Info', 'Safe-Mode',
        'mysql_connect', 'mysql_query',
        'base64_encode.*base64_decode',
        'gzdeflate.*gzinflate',
        'str_replace.*preg_replace'
    );

    $detection_count = 0;
    $detected_patterns = array();

    // Check basic patterns
    foreach ($basic_patterns as $pattern) {
        if (stripos($text, $pattern) !== false) {
            $detection_count++;
            $detected_patterns[] = $pattern;
        }
    }

    // Check advanced patterns
    foreach ($advanced_patterns as $pattern) {
        if (stripos($text, $pattern) !== false) {
            $detection_count++;
            $detected_patterns[] = $pattern;
        }
    }

    // Check webshell patterns with regex
    foreach ($webshell_patterns as $pattern) {
        if (preg_match('/' . preg_quote($pattern, '/') . '/i', $text)) {
            $detection_count++;
            $detected_patterns[] = $pattern;
        }
    }

    // Additional checks for obfuscated code
    if (preg_match('/[a-zA-Z0-9+\/]{50,}={0,2}/', $text)) {
        $detection_count++;
        $detected_patterns[] = 'base64_like_string';
    }

    // Check for hex encoded strings
    if (preg_match('/\\\\x[0-9a-fA-F]{2}/', $text)) {
        $detection_count++;
        $detected_patterns[] = 'hex_encoded';
    }

    // Log detection if patterns found
    if ($detection_count > 0 && function_exists('hiep_log_security')) {
        hiep_log_security('Shell detection: ' . $detection_count . ' patterns found: ' . implode(', ', $detected_patterns), 'WARNING');
    }

    // Return empty string if malicious content detected
    if ($detection_count > 0) {
        return "";
    } else {
        return $text;
    }
}

/**
 * Enhanced file upload security check
 * Compatible with PHP 5.6+ and 7.x
 */
function check_file_upload_security($filename, $content = null)
{
    $security_issues = array();

    // Check filename
    $dangerous_extensions = array(
        'php', 'php3', 'php4', 'php5', 'phtml', 'phps',
        'asp', 'aspx', 'jsp', 'jspx',
        'pl', 'py', 'rb', 'sh', 'bat', 'cmd',
        'exe', 'com', 'scr', 'msi',
        'js', 'vbs', 'jar', 'war',
        'htaccess', 'htpasswd'
    );

    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (in_array($file_ext, $dangerous_extensions)) {
        $security_issues[] = 'dangerous_extension';
    }

    // Check for double extensions
    if (preg_match('/\.(php|phtml|php3|php4|php5)\./i', $filename)) {
        $security_issues[] = 'double_extension';
    }

    // Check for null bytes
    if (strpos($filename, "\0") !== false) {
        $security_issues[] = 'null_byte';
    }

    // Check content if provided
    if ($content !== null) {
        if (check_shell($content) === "") {
            $security_issues[] = 'malicious_content';
        }
    }

    return empty($security_issues) ? true : $security_issues;
}

/**
 * Security logging function
 * Compatible with PHP 5.6+ and 7.x
 */
function hiep_log_security($message, $level = 'INFO')
{
    $log_file = dirname(__FILE__) . '/../logs/security.log';
    $log_dir = dirname($log_file);

    // Create log directory if not exists
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';

    $log_entry = "[{$timestamp}] [{$level}] IP: {$ip} | {$message} | User-Agent: {$user_agent}\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}
function doi_ngay($date)
{
    $thang = date('m', $date);
    $ngay = date('d', $date);
    $name = date('Y', $date);
    switch ($thang) {
        case "01":
            $tg = 'Tháng Một';
            break;
        case "02":
            $tg = 'Tháng Hai';
            break;
        case "03":
            $tg = 'Tháng Ba';
            break;
        case "04":
            $tg = 'Tháng Tư';
            break;
        case "05":
            $tg = 'Tháng Năm';
            break;
        case "06":
            $tg = 'Tháng Sáu';
            break;
        case "07":
            $tg = 'Tháng Bảy';
            break;
        case "08":
            $tg = 'Tháng Tám';
            break;
        case "09":
            $tg = 'Tháng Chín';
            break;
        case "10":
            $tg = 'Tháng Mười';
            break;
        case "11":
            $tg = 'Tháng Mười Một';
            break;
        case "12":
            $tg = 'Tháng Mười Hai';
            break;
        default:
            $tg = "";
    }
    return $ngay . ' ' . $tg . ', ' . $name . '';
}
function check_phone($text)
{
    $num1 = substr($text, 0, 1);
    $error = 0;
    if ((int)$text == 0) {
        $error = $error + 1;
    }
    if (substr($text, 0, 1) != 0) {
        $error = $error + 1;
    }
    if (strlen(strstr($html, '-')) > 0) {
        $error = $error + 1;
    }
    if (strlen($text) < 9 or strlen($text) > 10) {
        $error = $error + 1;
    }
    return $error;
}
function getImage($url)
{
    if (strpos($url, 'vimeo.com') !== false) {
        return getImageVimeo($url);
    } else {
        if (strpos($url, 'youtube.com') !== false) {
            // Xử lý URL từ youtube.com
            $queryString = parse_url($url, PHP_URL_QUERY);
            parse_str($queryString, $params);
            $v = isset($params['v']) ? $params['v'] : '';

            if (strlen($v) > 0) {
                return getImageYoutube2($v);
            } else {
                return "ID video YouTube không hợp lệ.";
            }
        } elseif (strpos($url, 'youtu.be') !== false) {
            // Xử lý URL từ youtu.be
            $path = parse_url($url, PHP_URL_PATH);
            $v = ltrim($path, '/');

            if (strpos($v, '?') !== false) {
                $v = substr($v, 0, strpos($v, '?'));
            }

            if (strlen($v) > 0) {
                return getImageYoutube2($v);
            } else {
                return "ID video YouTube không hợp lệ.";
            }
        } else {
            return getImageYoutube2($url);
        }
    }
}

function getImageYoutube($e)
{
    $url = $e;

    $queryString = parse_url($url, PHP_URL_QUERY);

    parse_str($queryString, $params);

    $v = $params['v'];

    //DISPLAY THE IMAGE
    if (strlen($v) > 0) {
        echo "http://img.youtube.com/vi/$v/hqdefault.jpg";
    }
}
function getImageYoutube2($e)
{
    $url = $e;

    $queryString = parse_url($url, PHP_URL_QUERY);

    parse_str($queryString, $params);

    //DISPLAY THE IMAGE
    if (strlen($url) > 0) {
        echo "http://img.youtube.com/vi/$url/hqdefault.jpg";
    }
}
function getImageVimeo($url)
{
    // Lấy ID của video Vimeo từ URL
    $vimeoId = (int) substr(parse_url($url, PHP_URL_PATH), 1);

    // Truy vấn API của Vimeo để lấy thông tin video
    $apiUrl = "https://vimeo.com/api/v2/video/$vimeoId.json";
    $response = file_get_contents($apiUrl);
    if ($response === FALSE) {
        return "Error fetching Vimeo video information.";
    }

    $data = json_decode($response, true);
    if (isset($data[0]['thumbnail_large'])) {
        return $data[0]['thumbnail_large'];
    } else {
        return "Thumbnail not found.";
    }
}

function debug()
{
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

function dd($arr)
{
    echo '<pre>';
    print_r($arr);
    echo '</pre>';
}

// function transfer($msg = '', $page = '', $stt = true)
// {
//     $showtext = $msg;
//     $page_transfer = $page;
//     $stt = $stt;
//     include("./sources/module/transfer.php");
//     exit();
// }

include 'simple_html_dom.php';
function validate_content($text, $type = '')
{ //$type='': bỏ thẻ tất cả thẻ html - $type='1': Loại bỏ hình ảnh, script
    if ($text != '') {
        $html = str_get_html($text);
        if ($type == '') {
            if (count($html->find('a')) > 0 or strlen(strstr($html, 'http')) > 0) {
                $html = '';
            } else {
                $html = strip_tags($html);
            }
        } elseif ($type == '0') {
            $html = strip_tags($html);
        } else {
            foreach ($html->find('img') as $element) {
                $element->outertext = '';
            }
            foreach ($html->find('script') as $element) {
                $element->outertext = '';
            }
            foreach ($html->find('a') as $element) {
                $element->outertext = '';
            }
            $html->load($html->save());
        }
        return addslashes($html);
    } else {
        return $text;
    }
} // lấy link type
function getSearch()
{
    $link = explode("?", $_SERVER['REQUEST_URI']);
    if ($link[1] != '') {
        $vari = explode("&", $link[1]);
        $search = array();
        foreach ($vari as $item) {
            $str = explode("=", $item);
            $search["$str[0]"] = urldecode(addslashes($str[1]));
        }
    }
    return $search;
}

function pnvn_getSearch()
{
    $search = array();

    // Lấy đường dẫn từ URL
    $url = parse_url($_SERVER['REQUEST_URI']);

    // Kiểm tra xem có phần path không
    if (isset($url['path'])) {
        // Tách các phần từ đường dẫn
        $pathSegments = explode('/', trim($url['path'], '/'));

        // Lấy giá trị cuối cùng từ phần path
        $lastSegment = end($pathSegments);

        // Gán giá trị vào mảng search với key là 'user'
        $search['user'] = $lastSegment;

        // Lấy giá trị trước đó từ phần path (nếu có)
        $secondToLastSegment = prev($pathSegments);

        // Gán giá trị vào mảng search với key là 'page'
        $search['page'] = $secondToLastSegment;
    }

    return $search;
}

function msv_Check_Login_Admin($user)
{
    if (isset($_SESSION['isLogin']) && $_SESSION['isLogin'] == 1 && $user['rank'] == 9999) {
    } else {
        header('location:' . URLLANG);
    }
}
function msv_Check_Login($user)
{
    if (isset($_SESSION['isLogin']) && $_SESSION['isLogin'] == 1 && $user['id'] != '') {
    } else {
        header('location:' . URLLANG . "dang-nhap.html");
    }
}

function normalizeChars($str)
{
    // Đảm bảo chuỗi là UTF-8
    $str = mb_convert_encoding($str, 'UTF-8', 'auto');

    // Danh sách các ký tự có dấu và tương ứng không dấu
    $unicodeMap = [
        'á' => 'a',
        'à' => 'a',
        'ả' => 'a',
        'ã' => 'a',
        'ạ' => 'a',
        'ă' => 'a',
        'ắ' => 'a',
        'ặ' => 'a',
        'ằ' => 'a',
        'ẳ' => 'a',
        'ẵ' => 'a',
        'â' => 'a',
        'ấ' => 'a',
        'ầ' => 'a',
        'ẩ' => 'a',
        'ẫ' => 'a',
        'ậ' => 'a',
        'é' => 'e',
        'è' => 'e',
        'ẻ' => 'e',
        'ẽ' => 'e',
        'ẹ' => 'e',
        'ê' => 'e',
        'ế' => 'e',
        'ề' => 'e',
        'ể' => 'e',
        'ễ' => 'e',
        'ệ' => 'e',
        'í' => 'i',
        'ì' => 'i',
        'ỉ' => 'i',
        'ĩ' => 'i',
        'ị' => 'i',
        'ó' => 'o',
        'ò' => 'o',
        'ỏ' => 'o',
        'õ' => 'o',
        'ọ' => 'o',
        'ô' => 'o',
        'ố' => 'o',
        'ồ' => 'o',
        'ổ' => 'o',
        'ỗ' => 'o',
        'ộ' => 'o',
        'ơ' => 'o',
        'ớ' => 'o',
        'ờ' => 'o',
        'ở' => 'o',
        'ỡ' => 'o',
        'ợ' => 'o',
        'ú' => 'u',
        'ù' => 'u',
        'ủ' => 'u',
        'ũ' => 'u',
        'ụ' => 'u',
        'ư' => 'u',
        'ứ' => 'u',
        'ừ' => 'u',
        'ử' => 'u',
        'ữ' => 'u',
        'ự' => 'u',
        'ý' => 'y',
        'ỳ' => 'y',
        'ỷ' => 'y',
        'ỹ' => 'y',
        'ỵ' => 'y',
        'đ' => 'd',
        'Á' => 'A',
        'À' => 'A',
        'Ả' => 'A',
        'Ã' => 'A',
        'Ạ' => 'A',
        'Ă' => 'A',
        'Ắ' => 'A',
        'Ặ' => 'A',
        'Ằ' => 'A',
        'Ẳ' => 'A',
        'Ẵ' => 'A',
        'Â' => 'A',
        'Ấ' => 'A',
        'Ầ' => 'A',
        'Ẩ' => 'A',
        'Ẫ' => 'A',
        'Ậ' => 'A',
        'É' => 'E',
        'È' => 'E',
        'Ẻ' => 'E',
        'Ẽ' => 'E',
        'Ẹ' => 'E',
        'Ê' => 'E',
        'Ế' => 'E',
        'Ề' => 'E',
        'Ể' => 'E',
        'Ễ' => 'E',
        'Ệ' => 'E',
        'Í' => 'I',
        'Ì' => 'I',
        'Ỉ' => 'I',
        'Ĩ' => 'I',
        'Ị' => 'I',
        'Ó' => 'O',
        'Ò' => 'O',
        'Ỏ' => 'O',
        'Õ' => 'O',
        'Ọ' => 'O',
        'Ô' => 'O',
        'Ố' => 'O',
        'Ồ' => 'O',
        'Ổ' => 'O',
        'Ỗ' => 'O',
        'Ộ' => 'O',
        'Ơ' => 'O',
        'Ớ' => 'O',
        'Ờ' => 'O',
        'Ở' => 'O',
        'Ỡ' => 'O',
        'Ợ' => 'O',
        'Ú' => 'U',
        'Ù' => 'U',
        'Ủ' => 'U',
        'Ũ' => 'U',
        'Ụ' => 'U',
        'Ư' => 'U',
        'Ứ' => 'U',
        'Ừ' => 'U',
        'Ử' => 'U',
        'Ữ' => 'U',
        'Ự' => 'U',
        'Ý' => 'Y',
        'Ỳ' => 'Y',
        'Ỷ' => 'Y',
        'Ỹ' => 'Y',
        'Ỵ' => 'Y',
        'Đ' => 'D'
    ];

    // Thay thế các ký tự có dấu bằng không dấu
    $str = strtr($str, $unicodeMap);

    // Loại bỏ các ký tự không mong muốn (chỉ giữ lại chữ cái, số, dấu gạch ngang)
    $str = preg_replace('/[^a-zA-Z0-9-\s]/u', '', $str);

    // Thay khoảng trắng bằng dấu "-"
    $str = preg_replace('/\s+/u', '-', trim($str));

    // Chuyển tất cả sang chữ thường
    $str = mb_strtolower($str, 'UTF-8');

    return $str;
}



// Đếm số chữ
function DemSoChu($vanBan)
{
    // Loại bỏ khoảng trắng và ký tự đặc biệt không cần thiết
    $vanBan = preg_replace('/\s+/', ' ', $vanBan);

    // Đếm số ký tự trong chuỗi đã được làm sạch
    $soChu = strlen($vanBan);

    return $soChu;
}

function ChuyenDonVi($so)
{
    // Loại bỏ ký tự không phải số và chấm phẩy
    $so = preg_replace('/[^0-9]/', '', $so);

    // Chia số cho 1000 và lấy phần nguyên
    $soChia1000 = floor($so / 1000);

    // Format lại số với đơn vị "K"
    $ketQua = $soChia1000 . "K";

    return $ketQua;
}

function HienThiThoiGian($thoiDiemTruoc)
{
    // Lấy thời điểm hiện tại
    $thoiDiemHienTai = time();

    // Tính thời gian chênh lệch
    $thoiGianChenhLech = $thoiDiemHienTai - $thoiDiemTruoc;

    // Xử lý và hiển thị kết quả
    if ($thoiGianChenhLech < 60) {
        $soGiay = date("s", $thoiGianChenhLech);
        return "" . $soGiay . " Giây";
    } elseif ($thoiGianChenhLech < (60 * 60)) {
        $soPhut = date("i", $thoiGianChenhLech);
        return "" . $soPhut . " Phút";
    } elseif ($thoiGianChenhLech < (24 * 60 * 60)) {
        $soGio = date("H", $thoiGianChenhLech);
        return "" . $soGio . " Giờ";
    } elseif ($thoiGianChenhLech < (30 * 24 * 60 * 60)) {
        $soNgay = date("d", $thoiGianChenhLech);
        return "" . $soNgay . " Ngày";
    } else {
        return "Cách đây nhiều ngày";
    }
}

function KiemTraDangNhap()
{
    if (!isset($_SESSION['isLogin']) && $_SESSION['isLogin'] != 1) {
        return false;
    } else {
        return true;
    }
}


function customHtmlspecialchars($noi_dung)
{
    // Tìm thẻ <img>...</img>
    preg_match_all('/<img[^>]*>.*?<\/img>/', $noi_dung, $matches);

    // Nếu tìm thấy thẻ <img>...</img>, hiển thị bình thường
    if (!empty($matches[0])) {
        foreach ($matches[0] as $imgTag) {
            $noi_dung .= $imgTag;
        }
    }

    // Tìm và xử lý các nội dung còn lại
    preg_match_all('/<img[^>]*>.*?<\/img>(.*)/', $noi_dung, $remainingMatches);

    // Nếu có nội dung còn lại, sử dụng htmlspecialchars
    if (!empty($remainingMatches[1])) {
        foreach ($remainingMatches[1] as $str) {
            $noi_dung .= htmlspecialchars($str, ENT_QUOTES, 'UTF-8', false);
        }
    }

    return $noi_dung;
}
function validate_content_new($text, $type = '')
{ //$type='': bỏ thẻ tất cả thẻ html - $type='1': Loại bỏ hình ảnh, script
    if ($text != '') {
        $html = $text;
        if ($type == '0') {
            $html = strip_tags($html);
        } else {
            foreach ($html->find('script') as $element) {
                $element->outertext = '';
            }
            foreach ($html->find('a') as $element) {
                $element->outertext = '';
            }
            $html->load($html->save());
        }
        return $html;
    } else {
        return $text;
    }
}
function replaceScript($chuoi)
{
    // Tìm chuỗi "script" (không phân biệt hoa thường) và thay thế bằng "HackCC"
    $chuoi = preg_replace('/script/i', 'HackCC', $chuoi);

    return $chuoi;
}
// Hàm xử lý dữ liệu trước khi lưu vào cơ sở dữ liệu
function sanitizeInput($input)
{
    // Cho phép thẻ <img> và loại bỏ các thẻ không an toàn
    $cleanedInput = strip_tags($input, '<img>');

    // // Chuyển đổi các ký tự đặc biệt thành các ký tự HTML entities
    $safeInput = htmlspecialchars($cleanedInput, ENT_QUOTES, 'UTF-8');

    return $safeInput;
}

function decodeImgTags($chuoi)
{
    // Sử dụng preg_replace_callback để áp dụng html_entity_decode chỉ cho các thẻ <img>
    $chuoiMoi = preg_replace_callback('/&lt;img.*?&gt;/i', function ($matches) {
        return html_entity_decode($matches[0]);
    }, $chuoi);

    return $chuoiMoi;
}

function decodeImgAndWhitespace($chuoi)
{
    // Sử dụng preg_replace_callback để áp dụng html_entity_decode cho cả thẻ <img>, khoảng trắng và &nbsp;
    $chuoiMoi = preg_replace_callback('/(&lt;img.*?&gt;|\s)/i', function ($matches) {
        return html_entity_decode($matches[0]);
    }, $chuoi);

    // Loại bỏ tất cả các trường hợp của &nbsp; trong chuỗi
    $chuoiMoi = str_replace(array('nbsp;', '&amp;'), '', $chuoiMoi);


    return $chuoiMoi;
}
// Xóa bớt độ dài ký tự
function pnvn_trim($string, $length, $ellipsis = '...')
{
    // Loại bỏ các ký tự không mong muốn ở đầu và cuối chuỗi
    $trimmed_string = trim($string);

    // Kiểm tra độ dài của chuỗi sau khi trim và xác định xem cần giữ nguyên hay cắt bớt
    if (mb_strlen($trimmed_string, 'UTF-8') <= $length) {
        return $trimmed_string;
    } else {
        // Cắt chuỗi và thêm ellipsis
        $trimmed_string = mb_substr($trimmed_string, 0, $length - mb_strlen($ellipsis, 'UTF-8'), 'UTF-8') . $ellipsis;
        return $trimmed_string;
    }
}

// tự tạo link

function vanhiep_link($link)
{
    $link_str = "";
    $link_str = "href='" . $link['link'] . "' ";
    if (!empty($link['nofollow'])) {
        $link_str .= " rel='nofollow' ";
    } else {
        $link_str .= " rel='follow' ";
    }

    if (!empty($link['target'])) {
        $link_str .= " target='_blank' ";
    }

    return $link_str;
}
function vanhiep_alias($link)
{
    $link_str = "";
    $link_str = "href='" . URLLANG . $link['alias'] . ".html" . "' ";
    if (!empty($link['url'])) {
        $link_str = "href='" . $link['url'] . "' ";
    }
    return $link_str;
}
function doitien($money, $donvi)
{
    $url        = "https://www.exchange-rates.org/LookupExchangeRate.aspx?iso_code_from=" . $donvi . "&iso_code_to=vnd&amount=$money";
    $html       = file_get_html($url);
    $money_new  = $html->find('rate', 0)->innertext;
    $data       = str_replace(',', '', $money_new);
    $arr_data   = explode('.', $data);
    $data       = round($arr_data[0]);
    return $data;
}
function doitien_text($num, $type = '0')
{
    $tygia = $_SESSION['tyia'];
    $gia = $num / $tygia;
    if ($type == 0) {
        if ($_SESSION['tiente'] == 'VND') {
            $text = numberformat($gia) . ' ' . $_SESSION['tiente_code'];
        } else {
            $text = $_SESSION['tiente_code'] . number_format($gia);
        }
    } else {
        $text = round($gia, 1);
    }
    return $text;
}
function donvi_tien($num)
{
    if ($_SESSION['tiente'] == 'VND') {
        $text = numberformat($num) . ' ' . $_SESSION['tiente_code'];
    } else {
        $text = $_SESSION['tiente_code'] . number_format($num);
    }
    return $text;
}
function checkParentCate($parent)
{
    global $d;
    $parent_cate_check = $d->getCate($parent['id_loai']);
    if (!empty($parent_cate_check)) {
        if ($parent_cate_check['id_loai'] == 0) {
            return $parent_cate_check;
        } else {
            return checkParentCate($parent_cate_check);
        }
    } else {
        return $parent;
    }
}


function KiemTraLoi()
{
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}
// Hàm lấy IP của khách
function getUserIP()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    // Chuyển đổi IPv6 ::1 sang IPv4 127.0.0.1
    if ($ip === '::1') {
        $ip = '127.0.0.1';
    }

    return $ip;
}

function update_sitemap()
{
    global $d;
    try {
        $arr_table = array('#_category', '#_tintuc', '#_album', '#_video', '#_sanpham');

        // Đường dẫn tuyệt đối đến file sitemap.xml
        $sitemap_path = dirname(dirname(dirname(__FILE__))) . "/sitemap.xml";

        // Debug thông tin
        echo "Đường dẫn sitemap: " . $sitemap_path . "<br>";
        echo "URLPATH: " . URLPATH . "<br>";

        $txt = '<?xml version="1.0" encoding="UTF-8"?><?xml-stylesheet type="text/xsl" href="main-sitemap.xsl"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $txt .= '
        <url>
            <loc>' . URLPATH . '</loc>
            <lastmod>' . date("c", time()) . '</lastmod>
            <priority>1.0</priority>
        </url>';

        // Đếm số lượng URL đã thêm vào sitemap
        $count_urls = 1; // Đã thêm trang chủ

        for ($i = 0; $i < count($arr_table); $i++) {
            echo "Đang xử lý bảng: " . $arr_table[$i] . "<br>";
            $sql = "select alias, lang, cap_nhat from " . $arr_table[$i] . " where hien_thi = 1 order by cap_nhat DESC";
            $row_data = $d->o_fet($sql);
            echo "- Số lượng bản ghi: " . (is_array($row_data) ? count($row_data) : 0) . "<br>";

            if (is_array($row_data) && count($row_data) > 0) {
                foreach ($row_data as $key => $value) {
                    if ($value['alias'] != '') {
                        // Đảm bảo thời gian cap_nhat không nằm trong tương lai
                        $cap_nhat = (isset($value['cap_nhat']) && $value['cap_nhat'] > 0) ? $value['cap_nhat'] : time();
                        $cap_nhat = ($cap_nhat > time()) ? time() : $cap_nhat;
                        $txt .= '
        <url>
            <loc>' . URLPATH . $value['lang'] . '/' . $value['alias'] . '.html</loc>
            <lastmod>' . date("c", $cap_nhat) . '</lastmod>
            <priority>0.8</priority>
        </url>';
                        $count_urls++;
                    }
                }
            }
        }

        $txt .= '
</urlset>';

        echo "Tổng số URL trong sitemap: " . $count_urls . "<br>";

        // Kiểm tra quyền ghi file
        if (!is_dir(dirname($sitemap_path))) {
            echo "Thư mục không tồn tại: " . dirname($sitemap_path) . "<br>";
            return false;
        }

        if (!is_writable(dirname($sitemap_path))) {
            echo "Thư mục không có quyền ghi: " . dirname($sitemap_path) . "<br>";
            return false;
        }

        if (file_exists($sitemap_path) && !is_writable($sitemap_path)) {
            echo "File sitemap.xml không có quyền ghi<br>";
            // Thử thay đổi quyền của file
            if (!@chmod($sitemap_path, 0666)) {
                echo "Không thể thay đổi quyền của file sitemap.xml<br>";
                return false;
            }
        }

        // Ghi file với quyền truy cập 0644
        $result = file_put_contents($sitemap_path, $txt);
        if ($result !== false) {
            @chmod($sitemap_path, 0644);
            echo "Đã ghi file sitemap.xml thành công (" . $result . " bytes)<br>";
            return true;
        }

        echo "Không thể ghi file sitemap.xml<br>";
        if ($error = error_get_last()) {
            echo "Lỗi: " . $error['message'] . "<br>";
        }
        return false;
    } catch (Exception $e) {
        echo "Lỗi cập nhật sitemap: " . $e->getMessage() . "<br>";
        error_log("Lỗi cập nhật sitemap: " . $e->getMessage());
        return false;
    }
}


define("LANG",  get_json('lang', '0', 'code'));



function pnvn_remove_ptag($content) {
    // Sử dụng biểu thức chính quy để tìm và thay thế
    $patterns = array(
        '/<p[^>]*>(.*?)<\/p>/is', // Tìm tất cả thẻ p có thuộc tính
        '/<p>(.*?)<\/p>/is'       // Tìm tất cả thẻ p không có thuộc tính
    );
    
    // Thay thế bằng nội dung bên trong
    $replacements = array(
        '$1', // Giữ lại nội dung bên trong
        '$1'  // Giữ lại nội dung bên trong
    );
    
    // Thực hiện thay thế
    $content = preg_replace($patterns, $replacements, $content);
    
    // Loại bỏ khoảng trắng và xuống dòng dư thừa
    $content = trim($content);
    
    return $content;
}
