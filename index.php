<style>
body {
    background: url('/image/555.jpg') 0% 0% / 100% no-repeat fixed !important;
    min-height: 100vh !important;
}
</style>
<?php
////////////////////////////Common head
$cache_time = 30;
$OJ_CACHE_SHARE = false;
require_once('./include/cache_start.php');
require_once('./include/db_info.inc.php');
require_once('./include/memcache.php');
require_once('./include/setlang.php');
require_once('./include/bbcode.php');

$view_title = "Welcome To Online Judge";
$result = false;

if (isset($OJ_ON_SITE_CONTEST_ID)) {
    header("location:contest.php?cid=" . $OJ_ON_SITE_CONTEST_ID);
    exit();
}

///////////////////////////MAIN

// NOIP赛制比赛时，本月之星，统计图不计入相关比赛提交
$now = date('Y-m-d H:i:s', time());
$noip_contests = "";
$NOIP_flag = 0;
$not_in_noip_contests = "";

if (isset($OJ_NOIP_KEYWORD) && !empty($OJ_NOIP_KEYWORD)) {
    $sql = "SELECT contest_id FROM contest WHERE start_time<'$now' AND end_time>'$now' AND (title LIKE '%" . addslashes($OJ_NOIP_KEYWORD) . "%' OR (contest_type & 20)>0)";
    $rows = pdo_query($sql);
    
    if (is_array($rows) && !empty($rows)) {
        $noip_ids = array();
        foreach ($rows as $row) {
            if (isset($row['contest_id'])) {
                $noip_ids[] = intval($row['contest_id']);
                $NOIP_flag++;
            }
        }
        if (!empty($noip_ids)) {
            $noip_contests = implode(',', $noip_ids);
            $not_in_noip_contests = " AND contest_id NOT IN ($noip_contests)";
        }
    }
}

// 新闻翻页
$sql = "SELECT COUNT('news_id') AS ids FROM `news` WHERE `defunct`!='Y' AND `title`!='faqs." . addslashes($OJ_LANG) . "'";
$result = pdo_query($sql);

$ids = 0;
if (is_array($result) && !empty($result) && isset($result[0]['ids'])) {
    $ids = intval($result[0]['ids']);
}

$idsperpage = 15;
$pages = intval(ceil($ids / $idsperpage));
if ($pages < 1) $pages = 1;

$page = 1;
if (isset($_GET['page']) && is_numeric($_GET['page'])) {
    $page = intval($_GET['page']);
    if ($page < 1) $page = 1;
    if ($page > $pages) $page = $pages;
}

$pagesperframe = 5;
$frame = intval(ceil($page / $pagesperframe));
$spage = ($frame - 1) * $pagesperframe + 1;
$epage = min($spage + $pagesperframe - 1, $pages);
$sid = ($page - 1) * $idsperpage;

// 初始化所有可能在模板中使用的变量
$view_news = "";
$chart_data_all = array();
$chart_data_ac = array();
$speed = '0/day';

// syzoj/sidebar 有自己的新闻查询逻辑，放在了template里面
if (!($OJ_TEMPLATE == "syzoj" || $OJ_TEMPLATE == "sidebar")) {
    $sql = "SELECT * FROM `news` "
        . "WHERE `defunct`!='Y' AND `title`!='faqs." . addslashes($OJ_LANG) . "'"
        . "ORDER BY `importance` ASC,`time` DESC "
        . "LIMIT 50";
    
    $view_news .= "<div class='panel panel-default' style='width:80%;margin:0 auto;'>";
    $view_news .= "<div class='panel-heading'><h3>" . htmlspecialchars($MSG_NEWS) . "</h3></div>";
    $view_news .= "<div class='panel-body'>";
    
    $result = mysql_query_cache($sql);
    if (is_array($result) && !empty($result)) {
        foreach ($result as $row) {
            $view_news .= "<div class='panel panel-default'>";
            $view_news .= "<div class='panel-heading'><big>" . htmlspecialchars($row['title']) . "</big>-<small>" . htmlspecialchars($row['user_id']) . "</small></div>";
            $view_news .= "<div class='panel-body'>" . bbcode_to_html($row['content']) . "</div>";
            $view_news .= "</div>";
        }
    }
    
    $view_news .= "</div>";
    $view_news .= "<div class='panel-footer'></div>";
    $view_news .= "</div>";
}

// 获取最近提交统计的起始ID
$view_apc_info = "";
$last_1000_id = 0;

$sql = "SELECT MIN(solution_id) AS id FROM solution WHERE in_date >= NOW() - INTERVAL 8 DAY 
        UNION ALL 
        SELECT MAX(solution_id) AS id FROM solution 
        ORDER BY id DESC LIMIT 1";

$cache_result = mysql_query_cache($sql);

if (is_array($cache_result) && !empty($cache_result) && isset($cache_result[0]['id'])) {
    $last_1000_id = intval($cache_result[0]['id']) - 1000;
    if ($last_1000_id < 0) $last_1000_id = 0;
}

// 确保 $last_1000_id 是整数
$last_1000_id = intval($last_1000_id);

// 查询所有提交数据用于生成统计图表
if ($last_1000_id > 0) {
    $sql = "SELECT DATE(in_date) AS md, COUNT(1) AS c FROM (SELECT in_date FROM solution WHERE solution_id > $last_1000_id $not_in_noip_contests AND result<13 AND problem_id>0 AND result>=4) AS solution GROUP BY md ORDER BY md DESC LIMIT 1000";
    $result = mysql_query_cache($sql);
    
    if (is_array($result) && !empty($result)) {
        foreach ($result as $row) {
            if (isset($row['md']) && isset($row['c'])) {
                $chart_data_all[] = array($row['md'], intval($row['c']));
            }
        }
    }
}

// 查询AC提交数据用于生成统计图表
if ($last_1000_id > 0) {
    $sql = "SELECT DATE(in_date) AS md, COUNT(1) AS c FROM (SELECT in_date FROM solution WHERE solution_id > $last_1000_id $not_in_noip_contests AND result=4 AND problem_id>0) AS solution GROUP BY md ORDER BY md DESC LIMIT 1000";
    $result2 = mysql_query_cache($sql);
    $ac = array();
    
    if (is_array($result2) && !empty($result2)) {
        foreach ($result2 as $row) {
            if (isset($row['md']) && isset($row['c'])) {
                $ac[$row['md']] = intval($row['c']);
            }
        }
    }
    
    if (!empty($chart_data_all)) {
        foreach ($chart_data_all as $item) {
            $md = $item[0];
            if (isset($ac[$md])) {
                $chart_data_ac[] = array($md, $ac[$md]);
            } else {
                $chart_data_ac[] = array($md, 0);
            }
        }
    }
}

// 计算提交速度
if (isset($_SESSION[$OJ_NAME . '_administrator'])) {
    if ($last_1000_id > 0) {
        $sql = "SELECT AVG(sp) AS sp FROM (SELECT AVG(1) AS sp, judgetime DIV 3600 FROM solution WHERE result>3 AND solution_id > $last_1000_id GROUP BY (judgetime DIV 3600) ORDER BY sp) AS tt";
        $result = mysql_query_cache($sql);
        if (is_array($result) && !empty($result) && isset($result[0]['sp'])) {
            $speed = round(floatval($result[0]['sp']), 2) . '/min';
        }
    }
} else {
    if (!empty($chart_data_all) && isset($chart_data_all[0][1])) {
        $speed = $chart_data_all[0][1] . '/day';
    }
}

// 确保所有变量都存在，避免模板中出现未定义变量
$view_problemset = "";  // 如果模板需要
$view_rank = "";        // 如果模板需要
$view_user = "";        // 如果模板需要

// 如果使用 syzoj 模板，确保传递正确的数据
if ($OJ_TEMPLATE == "syzoj" || $OJ_TEMPLATE == "sidebar") {
    // 为 syzoj 模板准备数据
    $news_list = array();
    $sql = "SELECT * FROM `news` "
        . "WHERE `defunct`!='Y' AND `title`!='faqs." . addslashes($OJ_LANG) . "'"
        . "ORDER BY `importance` ASC,`time` DESC "
        . "LIMIT 10";  // syzoj 模板可能只需要最新的几条
    
    $news_result = mysql_query_cache($sql);
    if (is_array($news_result) && !empty($news_result)) {
        foreach ($news_result as $row) {
            $news_list[] = array(
                'title' => htmlspecialchars($row['title']),
                'user_id' => htmlspecialchars($row['user_id']),
                'content' => bbcode_to_html($row['content']),
                'time' => $row['time']
            );
        }
    }
    
    // 将新闻数据传递给模板使用的变量
    $view_news_data = $news_list;
}

/////////////////////////Template
require("template/" . $OJ_TEMPLATE . "/index.php");

// 长期登录功能
if (isset($OJ_LONG_LOGIN) && $OJ_LONG_LOGIN 
    && (!isset($_SESSION[$OJ_NAME . '_user_id'])) 
    && isset($_COOKIE[$OJ_NAME . "_user"]) 
    && isset($_COOKIE[$OJ_NAME . "_check"])) {
    echo "<script>let xhr=new XMLHttpRequest();xhr.open('GET','login.php',true);xhr.send();setTimeout('location.reload()',800);</script>";
}

/////////////////////////Common foot
if (file_exists('./include/cache_end.php')) {
    require_once('./include/cache_end.php');
}
?>