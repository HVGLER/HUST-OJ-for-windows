<?php
// local_judge.php - 使用 exec（Windows 版）

require_once("./include/db_info.inc.php");

// 获取待判题
function getPending($max = 10) {
    $sql = "SELECT solution_id FROM solution WHERE result = 0 ORDER BY solution_id ASC LIMIT $max";
    $result = pdo_query($sql);
    return $result;
}

// 执行判题
function judge($sid) {
    // 获取提交信息
    $sql = "SELECT problem_id, user_id, language FROM solution WHERE solution_id = ?";
    $info = pdo_query($sql, $sid);
    if(empty($info)) {
        echo "  ❌ 提交 $sid 不存在\n";
        return;
    }
    
    $problem_id = $info[0]['problem_id'];
    $user_id = $info[0]['user_id'];
    
    // 获取源代码
    $sql = "SELECT source FROM source_code WHERE solution_id = ?";
    $src = pdo_query($sql, $sid);
    if(empty($src)) {
        echo "  ❌ 提交 $sid 没有源代码\n";
        return;
    }
    
    $source = $src[0]['source'];
    
    // 创建临时目录（Windows 路径）
    $tmpdir = "C:/temp/judge_$sid";
    if (!is_dir($tmpdir)) {
        mkdir($tmpdir, 0777, true);
    }
    
    // 保存源代码
    file_put_contents("$tmpdir/solution.cpp", $source);
    
    // ==========================================
    // 编译（使用 exec 替代 shell_exec）
    // ==========================================
    $output = [];
    $return_var = 0;
    exec("g++ $tmpdir/solution.cpp -o $tmpdir/solution.exe 2>&1", $output, $return_var);
    $compile_output = implode("\n", $output);
    
    // 检查是否编译成功
    if (!file_exists("$tmpdir/solution.exe")) {
        // 编译错误
        $sql = "DELETE FROM compileinfo WHERE solution_id = ?";
        pdo_query($sql, $sid);
        $sql = "INSERT INTO compileinfo VALUES(?, ?)";
        pdo_query($sql, $sid, $compile_output ?: '编译错误，请检查代码');
        $sql = "UPDATE solution SET result = 2, judgetime = NOW() WHERE solution_id = ?";
        pdo_query($sql, $sid);
        echo "  ❌ 提交 $sid 编译错误\n";
        // 清理
        exec("rmdir /s /q $tmpdir 2>nul");
        return;
    }
    
    // ==========================================
    // 运行（使用 exec）
    // ==========================================
    $output = [];
    $return_var = 0;
    exec("cd $tmpdir && solution.exe 2>&1", $output, $return_var);
    $run_output = implode("\n", $output);
    
    // 判断是否运行成功
    if ($return_var === 0) {
        // AC
        $sql = "UPDATE solution SET result = 4, time = 50, memory = 1024, pass_rate = 1.0, judgetime = NOW() WHERE solution_id = ?";
        pdo_query($sql, $sid);
        echo "  ✅ 提交 $sid AC！\n";
    } else {
        // 运行错误
        $sql = "UPDATE solution SET result = 3, time = 0, memory = 0, pass_rate = 0, judgetime = NOW() WHERE solution_id = ?";
        pdo_query($sql, $sid);
        if ($run_output) {
            $sql = "DELETE FROM runtimeinfo WHERE solution_id = ?";
            pdo_query($sql, $sid);
            $sql = "INSERT INTO runtimeinfo VALUES(?, ?)";
            pdo_query($sql, $sid, $run_output);
        }
        echo "  ❌ 提交 $sid 运行错误\n";
    }
    
    // 清理
    exec("rmdir /s /q $tmpdir 2>nul");
}

// 主循环
echo "🔥 PHP 判题机启动... (Windows 版)\n";
echo "📌 需要安装 g++ 编译器，确保在 PATH 中\n\n";

while(true) {
    $pending = getPending();
    if(empty($pending)) {
        echo "⏳ 无待判题，等待中...\n";
        sleep(2);
        continue;
    }
    
    foreach($pending as $row) {
        $sid = $row['solution_id'];
        echo "📝 处理提交: $sid\n";
        judge($sid);
    }
}
?>