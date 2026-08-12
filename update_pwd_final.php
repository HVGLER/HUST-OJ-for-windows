<?php
require_once('./include/db_info.inc.php');

$hashed = '5SXe4P+3saqpvCwoIf4YECnElZw0NzM0';
$user_id = 'admin';

// 使用 pdo_query 更新
$sql = "UPDATE users SET password = ? WHERE user_id = ?";
$result = pdo_query($sql, $hashed, $user_id);

if ($result !== false) {
    echo "✅ 密码更新成功！<br>";
    echo "请用 admin / 123456 登录";
} else {
    echo "❌ 更新失败，请手动执行 SQL";
}
?>