<?php
require_once('./include/db_info.inc.php');
require_once('./include/const.inc.php');
require_once('./include/cache_start.php');
require_once('./include/curl.php');
require_once('./include/memcache.php');
require_once('./include/setlang.php');
require_once("./include/set_get_key.php");
if (empty($_SESSION[$OJ_NAME . '_user_id'])) {
    echo "sb";
    echo '<a href="loginpage.php">登陆</a>';
} else {
    echo $user_id;
}
?>