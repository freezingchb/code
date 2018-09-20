<?php
ini_set("display_errors", "Off");
error_reporting(E_ERROR);
header('content-type:text/html;charset=utf8');

require __DIR__ . '/vendor/autoload.php';
$dbConfig = require __DIR__ . '/config/db.php';

// 抓取凤凰网商业
for ($page = 5; $page >= 1; $page--) {
    $db = new MysqliDb ($dbConfig);
    $url = "http://biz.ifeng.com/listpage/26481/{$page}/list.shtml";

    // 抓取列表
    $html = file_get_contents($url);
    $data = \QL\QueryList::html($html)->rules([
        'url' =>  ['.col_L .box_list h2 a', 'href'],
        'title' =>  ['.col_L .box_list h2 a', 'text'],
    ])->query()->getData()->all();

    // 抓取内容页
    foreach ($data as $item) {
        if (empty($item['url']) || empty($item['title']))
            continue;

        // 查重
        if (checkNewsExist($db, $item['title']))
            continue;

        $content = \QL\QueryList::get($item['url'])->find('#artical_real')->html();
        sleep(1);

        if (empty($content))
            continue;

        $content = preg_replace('/<span class="ifengLogo">.+?<\/span>/i', '', $content); // 去除内容区logo

        $id = saveNews($db, $item['title'], makeUp($content));
        echo $id . "\n";//die;
    }
}

// 抓取中国青年网-财经
$db = new MysqliDb ($dbConfig);
$url = "http://finance.youth.cn/finance_cyxfgsxw/";
$html = file_get_contents($url);
$data = \QL\QueryList::html($html)->rules( [
    'url' =>  ['.rdwz ul li a', 'href'],
    'title' =>  ['.rdwz ul li a', 'text'],
])->query()->getData()->all();
foreach ($data as $item) {
    if (empty($item['url']) || empty($item['title']))
        continue;
    $item['title'] = mb_convert_encoding($item['title'], 'utf-8', 'GB2312');

    // 查重
    if (checkNewsExist($db, $item['title']))
        continue;

    $item['url'] = 'http://finance.youth.cn/finance_cyxfgsxw/' . $item['url'];
    $content = \QL\QueryList::get($item['url'])->encoding('UTF-8','GB2312')->find('.TRS_Editor')->html();
    sleep(1);

    if (empty($content))
        continue;

    // 图片地址相对转绝对
    $replacement = '<img src="' . substr($item['url'], 0, strrpos($item['url'], '/') + 1) . '${2}"';
    $content = preg_replace('/<img.+?src=[\'|"](\.)(.+?)[\'|"]/i', $replacement, $content);
    $id = saveNews($db, $item['title'], makeUp($content));
    echo $id . "\n";//die;
}

/**
 * 判断标题是否重复
 *
 * @param $db
 * @param $title
 * @return bool
 */
function checkNewsExist($db, $title) {
    $row = $db->where ('title_md5', md5($title))->getOne('home_activity_content');
    return !empty($row);
}

/**
 * 保存新闻数据
 *
 * @param $db
 * @param $title
 * @param $content
 * @return mixed
 */
function saveNews($db, $title, $content) {
    $id = $db->insert('home_activity', [
        'title' => $title,
        'pic' => getFirstPic($content), // 缩略图
        'summary' => cutStr(preg_replace('/[ ]*/iu', '', strip_tags($content)) , 100, true),
        'content' => '',
        'status' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'type' => 3, // 新闻稿类型
        'keyword' => '',
        'is_hot' => 0,
    ]);
    $db->insert('home_activity_content', [
        'id' => $id,
        'title_md5' => md5($title),
        'content' => $content,
    ]);
    return $id;
}

/**
 * 获取内容的第一张图片
 *
 * @param $content
 * @return string
 */
function getFirstPic($content)
{
    preg_match('/<img.+?src=[\'|"](.+?)[\'|"]/i', $content, $matches);
    $pic = empty($matches[1]) ? '' : $matches[1];
    return $pic;
}

/**
 * 文本排版
 *
 * @param $string
 * @return mixed
 */
function makeUp($string) {
    // 去除标签属性
    $string = preg_replace([
        '/style\s*=\s*".*?"/i',
        "/style\s*=\s*'.*?'/i",
        '/class\s*=\s*".*?"/i',
        "/class\s*=\s*'.*?'/i",
        '/alt\s*=\s*".*?"/i',
        "/alt\s*=\s*'.*?'/i",
    ], [
        '',
        '',
        '',
        '',
        '',
        '',
    ], $string);

    // p标签首行缩进
    $string = preg_replace('/<p.*?>[ ]*/iu', '<p>', $string);
    $string = preg_replace('/<p.*?>(&nbsp;)*/iu', '<p>', $string);
    $string = preg_replace('/<p.*?>/iu', '<p style="text-indent:2em;">', $string);

    // 图片居中
    $string = preg_replace('/<p.*?>\s*<img(.*?)>/i', '<p style="text-align:center"><img style="max-width:500px" ${1}>', $string);
    return $string;
}

/**
 * utf8编码字符串截取函数
 *
 * @author freezingchb
 * @param $string
 * @param $len
 * @param boolean $ext 是否添加后缀...
 * @param int $start
 * @param string $code
 * @return string
 */
function cutStr($string, $len, $ext = false, $start = 0, $code = 'UTF-8')
{
    if ($code == 'UTF-8') {
        $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
        preg_match_all($pa, $string, $t_string);

        if (count($t_string[0]) - $start > $len) {
            $string = join('', array_slice($t_string[0], $start, $len));
            $ext && $string .= "...";
            return $string;
        } else {
            return join('', array_slice($t_string[0], $start, $len));
        }
    } else {
        $start = $start * 2;
        $len = $len * 2;
        $strLen = strlen($string);
        $tmpStr = '';
        for ($i = 0; $i < $strLen; $i++) {
            if ($i >= $start && $i < ($start + $len)) {
                if (ord(substr($string, $i, 1)) > 129)
                    $tmpStr .= substr($string, $i, 2);
                else
                    $tmpStr .= substr($string, $i, 1);
            }
            if (ord(substr($string, $i, 1)) > 129)
                $i++;
        }
        if ($ext && strlen($tmpStr) < $strLen)
            $tmpStr .= "...";
        return $tmpStr;
    }
}
