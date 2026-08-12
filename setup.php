<?php
// setup.php - 完全兼容所有 MySQL 版本
$host = 'localhost';
$username = 'oj';
$password = 'oj';

try {
    // 创建连接
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        die("连接失败: " . $conn->connect_error);
    }
    
    echo "MySQL 版本: " . $conn->server_info . "<br><br>";
    
    // 删除旧数据库（如果存在）
    $conn->query("DROP DATABASE IF EXISTS oj");
    
    // 创建数据库
    $sql = "CREATE DATABASE IF NOT EXISTS oj";
    if ($conn->query($sql) === TRUE) {
        echo "✅ 数据库创建成功<br>";
    }
    
    // 选择数据库
    $conn->select_db('oj');
    
    // 创建 users 表（完全兼容版本）
    $sql = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        created_at DATETIME,
        updated_at DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql) === TRUE) {
        echo "✅ 用户表创建成功<br>";
    } else {
        die("创建用户表失败: " . $conn->error);
    }
    
    // 插入演示用户
    $sql = "INSERT INTO users (username, password, email, created_at, updated_at) VALUES 
        ('admin', '123456', 'admin@example.com', NOW(), NOW()),
        ('test', '123456', 'test@example.com', NOW(), NOW()),
        ('user', '123456', 'user@example.com', NOW(), NOW())";
    
    if ($conn->query($sql) === TRUE) {
        echo "✅ 演示用户创建成功<br>";
    } else {
        echo "⚠️ 插入用户失败: " . $conn->error . "<br>";
    }
    
    // 创建 problems 表
    $sql = "CREATE TABLE problems (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        difficulty VARCHAR(20) DEFAULT '简单',
        ac_rate VARCHAR(10) DEFAULT '0%',
        created_at DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql) === TRUE) {
        echo "✅ 题目表创建成功<br>";
    } else {
        die("创建题目表失败: " . $conn->error);
    }
    
    // 插入示例题目
    $sql = "INSERT INTO problems (title, difficulty, ac_rate, created_at) VALUES
        ('两数之和', '简单', '85%', NOW()),
        ('反转链表', '中等', '62%', NOW()),
        ('二叉树最大深度', '简单', '78%', NOW()),
        ('N皇后问题', '困难', '41%', NOW()),
        ('最长公共子序列', '中等', '53%', NOW())";
    
    if ($conn->query($sql) === TRUE) {
        echo "✅ 示例题目创建成功<br>";
    } else {
        echo "⚠️ 插入题目失败: " . $conn->error . "<br>";
    }
    
    echo "<br>🎉 数据库初始化完成！";
    echo "<br><a href='index.php' style='display:inline-block; padding:10px 20px; background:#3b82f6; color:white; text-decoration:none; border-radius:5px; margin-top:10px;'>返回主页</a>";
    
} catch (Exception $e) {
    die("错误: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>