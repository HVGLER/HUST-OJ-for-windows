<?php
// reset_pwd.php - 重置管理员密码
require_once("./include/db_info.inc.php");
require_once("./include/my_func.inc.php");

// 如果已经登录，直接跳转
if (isset($_SESSION[$OJ_NAME . '_administrator'])) {
    header("Location: admin/");
    exit();
}

// 设置新密码
$new_password = 'admin123456';

// 获取所有管理员用户
$sql = "SELECT user_id FROM privilege WHERE rightstr='administrator'";
$result = pdo_query($sql);

if (is_array($result) && !empty($result)) {
    echo "<h3>找到以下管理员账号：</h3><ul>";
    $admin_users = array();
    foreach ($result as $row) {
        if (isset($row['user_id'])) {
            $admin_users[] = $row['user_id'];
            echo "<li>" . htmlspecialchars($row['user_id']) . "</li>";
        }
    }
    echo "</ul>";
    
    // 重置第一个管理员的密码
    if (!empty($admin_users)) {
        $admin_user = $admin_users[0];
        $encrypted = pwGen($new_password);
        $sql = "UPDATE users SET password=? WHERE user_id=?";
        $result = pdo_query($sql, $encrypted, $admin_user);
        
        if ($result !== false && $result !== 0) {
            echo "<div style='color:green;font-size:18px;padding:20px;border:2px solid green;'>";
            echo "✅ <strong>密码重置成功！</strong><br>";
            echo "用户名: <strong>" . htmlspecialchars($admin_user) . "</strong><br>";
            echo "新密码: <strong>" . $new_password . "</strong><br>";
            echo "<a href='loginpage.php'>点击这里登录</a>";
            echo "</div>";
        } else {
            echo "<div style='color:red;'>❌ 密码重置失败，请检查数据库连接</div>";
        }
    }
} else {
    echo "<div style='color:red;'>❌ 没有找到管理员账号！请先创建管理员。</div>";
    echo "<form method='post'>";
    echo "用户名: <input type='text' name='new_admin' value='admin'><br>";
    echo "密码: <input type='text' name='new_pwd' value='admin123'><br>";
    echo "<input type='submit' name='create' value='创建管理员'>";
    echo "</form>";
    
    if (isset($_POST['create'])) {
        $new_user = trim($_POST['new_admin']);
        $new_pwd = trim($_POST['new_pwd']);
        $encrypted = pwGen($new_pwd);
        
        // 创建用户
        $sql = "INSERT INTO users (user_id, password, nick, email, reg_time) VALUES (?, ?, ?, ?, NOW())";
        pdo_query($sql, $new_user, $encrypted, $new_user, $new_user . '@localhost');
        
        // 授予管理员权限
        $sql = "INSERT INTO privilege (user_id, rightstr) VALUES (?, 'administrator')";
        pdo_query($sql, $new_user);
        
        echo "<div style='color:green;'>✅ 管理员创建成功！用户名: $new_user, 密码: $new_pwd</div>";
    }
}
?>