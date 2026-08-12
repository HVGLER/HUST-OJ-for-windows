<?php
require_once('./include/my_func.inc.php');

$password = '123456';
$hashed = pwGen($password);

echo "原始密码: " . $password . "<br>";
echo "加密后: " . $hashed . "<br>";
?>