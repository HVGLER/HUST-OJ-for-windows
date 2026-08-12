<?php
if ($_SESSION[$OJ_NAME.'_'.'getkey']!=$_GET['getkey']){
?>
<script language=javascript>
        history.go(-1);
</script>
<?php 
    exit(1);
}
else{
   unset($_SESSION[$OJ_NAME.'_'.'getkey']);
}
?>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("./include/db_info.inc.php");
require_once("./include/my_func.inc.php");
require_once('./include/setlang.php');
if (isset($OJ_CSRF) && $OJ_CSRF) require_once("./include/csrf_check.php");

$use_cookie = false;
$login = false;

if ($OJ_LONG_LOGIN && isset($_COOKIE[$OJ_NAME . "_user"]) && isset($_COOKIE[$OJ_NAME . "_check"])) {
    $C_check = $_COOKIE[$OJ_NAME . "_check"];
    $C_user = $_COOKIE[$OJ_NAME . "_user"];
    $use_cookie = true;
    $C_num = strlen($C_check) - 1;
    $C_num = ($C_num * $C_num) % 7;
    if ($C_check[strlen($C_check) - 1] != $C_num) {
        setcookie($OJ_NAME . "_check", "", 0);
        setcookie($OJ_NAME . "_user", "", 0);
        echo "<script>\n alert('$MSG_COOKIE_ERROR (-1)'); \n history.go(-1); \n </script>";
        exit(0);
    }
    
    $C_info_result = pdo_query("SELECT `password`,`accesstime` FROM `users` WHERE `user_id`=? and defunct='N'", $C_user);
    if (!is_array($C_info_result) || empty($C_info_result)) {
        setcookie($OJ_NAME . "_check", "", 0);
        setcookie($OJ_NAME . "_user", "", 0);
        echo "<script>\n alert('用户不存在或已被禁用'); \n history.go(-1); \n </script>";
        exit(0);
    }
    $C_info = $C_info_result[0];
    
    if (!isset($C_info[0]) || !isset($C_info[1])) {
        setcookie($OJ_NAME . "_check", "", 0);
        setcookie($OJ_NAME . "_user", "", 0);
        echo "<script>\n alert('用户数据异常'); \n history.go(-1); \n </script>";
        exit(0);
    }
    
    $C_len = strlen($C_info[1]);
    $C_res = "";
    for ($i = 0; $i < strlen($C_info[0]); $i++) {
        $tp = ord($C_info[0][$i]);
        $C_res .= chr(39 + ($tp * $tp + ord($C_info[1][$i % $C_len]) * $tp) % 88);
    }
    if (substr($C_check, 0, -1) == sha1($C_res))
        $login = $C_user;
    else {
        setcookie($OJ_NAME . "_check", "", 0);
        setcookie($OJ_NAME . "_user", "", 0);
        echo "<script>\n alert('$MSG_COOKIE_ERROR (-2)'); \n history.go(-1); \n </script>";
        exit(0);
    }
}

$vcode = "";
if (!$use_cookie) {
    if (isset($_POST['vcode'])) $vcode = trim($_POST['vcode']);
    if ($OJ_VCODE && ($vcode != $_SESSION[$OJ_NAME . '_' . "vcode"] || $vcode == "" || $vcode == null)) {
        $_SESSION[$OJ_NAME . '_' . "vfail"] = true;
        echo "<script language='javascript'>\n";
        echo "alert('Verify Code Wrong!');\n";
        echo "history.go(-1);\n";
        echo "</script>";
        exit(0);
    }
    $view_errors = "";
    require_once("./include/login-" . $OJ_LOGIN_MOD . ".php");
    $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($user_id) || empty($password)) {
        echo "<script language='javascript'>\n";
        echo "alert('请输入用户名和密码！');\n";
        echo "history.go(-1);\n";
        echo "</script>";
        exit(0);
    }
    
    $fiveMinutesAgo = date('Y-m-d H:i:s', strtotime("-5 minutes"));
    $failed = pdo_query("SELECT
                        (SELECT COUNT(1) FROM loginlog WHERE user_id=? AND password='login fail' AND time>=?) as user_fail,
                        (SELECT COUNT(1) FROM loginlog WHERE ip=? AND password='login fail' AND time>=?) as ip_fail;", $user_id, $fiveMinutesAgo, $ip, $fiveMinutesAgo);
    
    if (isset($OJ_LOGIN_FAIL_LIMIT) && ($OJ_LOGIN_FAIL_LIMIT > 0)) {
        $user_fail = 0;
        $ip_fail = 0;
        if (is_array($failed) && !empty($failed) && isset($failed[0])) {
            $user_fail = isset($failed[0][0]) ? intval($failed[0][0]) : 0;
            $ip_fail = isset($failed[0][1]) ? intval($failed[0][1]) : 0;
        }
        if ($user_fail > $OJ_LOGIN_FAIL_LIMIT || $ip_fail > $OJ_LOGIN_FAIL_LIMIT * 4) {
            $view_errors = "Failed login too frequently!";
            require("template/" . $OJ_TEMPLATE . "/error.php");
            exit(0);
        }
    }
    $login = check_login($user_id, $password);
}

if ($login) {
    session_regenerate_id();
    $group_name = "";
    $group_row = pdo_query("select group_name,nick from users where user_id=?", $login);
    if (is_array($group_row) && !empty($group_row) && isset($group_row[0])) {
        $group_name = isset($group_row[0]['group_name']) ? $group_row[0]['group_name'] : '';
        $_SESSION[$OJ_NAME . '_nick'] = isset($group_row[0]['nick']) ? $group_row[0]['nick'] : '';
        $_SESSION[$OJ_NAME . '_group_name'] = $group_name;
    }
    if (empty($group_name)) {
        $sql = "SELECT * FROM `privilege` WHERE `user_id`=?";
        $_SESSION[$OJ_NAME . '_' . 'user_id'] = $login;
        $result = pdo_query($sql, $login);
    } else {
        $sql = "SELECT * FROM `privilege` WHERE `user_id`=? or (user_id=? and rightstr like 'c%' )";
        $_SESSION[$OJ_NAME . '_' . 'user_id'] = $login;
        $result = pdo_query($sql, $login, $group_name);
    }
    
    if (is_array($result)) {
        foreach ($result as $row) {
            if (isset($row['valuestr']))
                $_SESSION[$OJ_NAME . '_' . $row['rightstr']] = $row['valuestr'];
            else
                $_SESSION[$OJ_NAME . '_' . $row['rightstr']] = true;
        }
    }
    
    if (isset($_SESSION[$OJ_NAME . '_vip'])) {
        $sql = "select contest_id from contest where title like '%[VIP]%'";
        $result = pdo_query($sql);
        if (is_array($result)) {
            foreach ($result as $row) {
                $_SESSION[$OJ_NAME . '_c' . $row['contest_id']] = true;
            }
        }
    }

    $sql = "update users set accesstime=now() where user_id=?";
    $result = pdo_query($sql, $login);

    if ($OJ_LONG_LOGIN) {
        $C_info_result = pdo_query("SELECT `password` , `accesstime` FROM`users` WHERE`user_id`=? and defunct='N'", $login);
        if (is_array($C_info_result) && !empty($C_info_result) && isset($C_info_result[0])) {
            $C_info = $C_info_result[0];
            $C_len = strlen($C_info[1]);
            $C_res = "";
            for ($i = 0; $i < strlen($C_info[0]); $i++) {
                $tp = ord($C_info[0][$i]);
                $C_res .= chr(39 + ($tp * $tp + ord($C_info[1][$i % $C_len]) * $tp) % 88);
            }
            $C_res = sha1($C_res);
            $C_time = time() + 86400 * $OJ_KEEP_TIME;
            setcookie($OJ_NAME . "_user", $login, $C_time);
            setcookie($OJ_NAME . "_check", $C_res . (strlen($C_res) * strlen($C_res)) % 7, $C_time);
        }
    }
    echo "<script language='javascript'>\n";
    if (isset($_SESSION[$OJ_NAME . "_administrator"]))
        echo "window.location.href='admin/';\n";
    else if (isset($_SESSION[$OJ_NAME . "_contest_creator"]))
        echo "window.location.href='contest.php?my';\n";
    else if ($OJ_NEED_LOGIN)
        echo "window.location.href='index.php';\n";
    else
        echo "setTimeout('history.go(-2)',500);\n";
    echo "</script>";
} else {
    if (isset($OJ_LOG_ENABLED) && $OJ_LOG_ENABLED && isset($logger)) {
        $params = json_encode($_REQUEST, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $logger->info($params);
    }
    if ($view_errors) {
        require("template/" . $OJ_TEMPLATE . "/error.php");
    } else {
        echo "<script language='javascript'>\n";
        echo "alert('UserName or Password Wrong!');\n";
        echo "history.go(-1);\n";
        echo "</script>";
    }
}

if (!isset($user_id)) $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : 'unknown';
if (!isset($ip)) $ip = $_SERVER['REMOTE_ADDR'];

$sql = "INSERT INTO `loginlog`(user_id,password,ip,time) VALUES(?,'login ok',?,NOW())";
pdo_query($sql, $user_id, $ip);
?>