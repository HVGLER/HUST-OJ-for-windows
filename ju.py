# ju.py - 从文件系统读取输入
import pymysql
import subprocess
import os
import time
import shutil
import glob

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': 'root',
    'database': 'hustoj',
    'charset': 'utf8mb4'
}

# ========== 超时配置 ==========
COMPILE_TIMEOUT = 30
RUN_TIMEOUT = 60
JUDGE_INTERVAL = 2
# ==============================

# 数据文件路径
DATA_PATH = r"E:\wordpress\oj\data"

def get_pending():
    """获取待判题"""
    conn = pymysql.connect(**DB_CONFIG)
    cursor = conn.cursor()
    cursor.execute("SELECT solution_id FROM solution WHERE result = 0 ORDER BY solution_id ASC LIMIT 1")
    result = cursor.fetchone()
    conn.close()
    return result[0] if result else None

def get_solution_info(sid):
    """获取提交信息"""
    conn = pymysql.connect(**DB_CONFIG)
    cursor = conn.cursor()
    cursor.execute("SELECT problem_id, user_id, language FROM solution WHERE solution_id = %s", (sid,))
    result = cursor.fetchone()
    conn.close()
    return result

def get_problem_data_files(problem_id):
    """获取题目的输入输出文件列表"""
    problem_dir = os.path.join(DATA_PATH, str(problem_id))
    if not os.path.exists(problem_dir):
        print(f"  ⚠️ 题目数据目录不存在: {problem_dir}")
        return []
    
    # 获取所有 .in 文件
    in_files = glob.glob(os.path.join(problem_dir, "*.in"))
    test_cases = []
    
    for in_file in in_files:
        base_name = os.path.splitext(in_file)[0]
        out_file = base_name + ".out"
        
        if os.path.exists(out_file):
            test_cases.append({
                'input': in_file,
                'output': out_file,
                'name': os.path.basename(base_name)
            })
    
    return test_cases

def update_result(sid, result, time_ms, memory_kb, pass_rate):
    """更新判题结果"""
    conn = pymysql.connect(**DB_CONFIG)
    cursor = conn.cursor()
    sql = "UPDATE solution SET result = %s, time = %s, memory = %s, pass_rate = %s, judgetime = NOW() WHERE solution_id = %s"
    cursor.execute(sql, (result, time_ms, memory_kb, pass_rate, sid))
    conn.commit()
    conn.close()

def compare_output(actual, expected):
    """比较实际输出和期望输出"""
    # 去除末尾空白字符再比较
    actual = actual.strip()
    expected = expected.strip()
    return actual == expected

def judge(sid):
    """判题核心"""
    print(f"📝 处理提交: {sid}")
    
    # 获取提交信息
    info = get_solution_info(sid)
    if not info:
        print(f"  ❌ 提交 {sid} 不存在")
        return
    
    problem_id, user_id, language = info
    print(f"  📊 题目ID: {problem_id}")
    
    # 获取源代码
    conn = pymysql.connect(**DB_CONFIG)
    cursor = conn.cursor()
    cursor.execute("SELECT source FROM source_code WHERE solution_id = %s", (sid,))
    source_row = cursor.fetchone()
    conn.close()
    
    if not source_row:
        print(f"  ❌ 提交 {sid} 没有源代码")
        return
    
    source = source_row[0]
    
    # 创建临时目录
    tmpdir = f"C:/temp/judge_{sid}"
    if os.path.exists(tmpdir):
        shutil.rmtree(tmpdir, ignore_errors=True)
    os.makedirs(tmpdir, exist_ok=True)
    
    # 保存源代码
    with open(f"{tmpdir}/solution.cpp", 'w', encoding='utf-8') as f:
        f.write(source)
    
    # ========== 编译 ==========
    print(f"  🔨 编译中（超时：{COMPILE_TIMEOUT}秒，-O2优化）...")
    try:
        compile_result = subprocess.run(
            ['g++', '-O2', f'{tmpdir}/solution.cpp', '-o', f'{tmpdir}/solution.exe'],
            capture_output=True,
            text=True,
            timeout=COMPILE_TIMEOUT
        )
    except subprocess.TimeoutExpired:
        print(f"  ⏰ 提交 {sid} 编译超时（>{COMPILE_TIMEOUT}秒）")
        conn = pymysql.connect(**DB_CONFIG)
        cursor = conn.cursor()
        cursor.execute("DELETE FROM compileinfo WHERE solution_id = %s", (sid,))
        cursor.execute("INSERT INTO compileinfo VALUES(%s, %s)", (sid, f"编译超时（>{COMPILE_TIMEOUT}秒）"))
        cursor.execute("UPDATE solution SET result = 2, judgetime = NOW() WHERE solution_id = %s", (sid,))
        conn.commit()
        conn.close()
        shutil.rmtree(tmpdir, ignore_errors=True)
        return
    
    if compile_result.returncode != 0 or not os.path.exists(f"{tmpdir}/solution.exe"):
        # 编译错误
        conn = pymysql.connect(**DB_CONFIG)
        cursor = conn.cursor()
        cursor.execute("DELETE FROM compileinfo WHERE solution_id = %s", (sid,))
        cursor.execute("INSERT INTO compileinfo VALUES(%s, %s)", (sid, compile_result.stderr or '编译错误'))
        cursor.execute("UPDATE solution SET result = 2, judgetime = NOW() WHERE solution_id = %s", (sid,))
        conn.commit()
        conn.close()
        print(f"  ❌ 提交 {sid} 编译错误")
        shutil.rmtree(tmpdir, ignore_errors=True)
        return
    
    # ========== 获取测试数据 ==========
    test_cases = get_problem_data_files(problem_id)
    if not test_cases:
        print(f"  ⚠️ 没有找到测试数据文件")
        shutil.rmtree(tmpdir, ignore_errors=True)
        # 标记为系统错误
        conn = pymysql.connect(**DB_CONFIG)
        cursor = conn.cursor()
        cursor.execute("UPDATE solution SET result = 11, judgetime = NOW() WHERE solution_id = %s", (sid,))
        conn.commit()
        conn.close()
        return
    
    print(f"  📁 找到 {len(test_cases)} 个测试点")
    
    # ========== 运行测试 ==========
    all_passed = True
    total_time = 0
    passed_count = 0
    
    for idx, test_case in enumerate(test_cases, 1):
        print(f"  🧪 测试点 {idx}/{len(test_cases)}: {test_case['name']}")
        
        # 读取输入数据
        with open(test_case['input'], 'r', encoding='utf-8') as f:
            input_data = f.read()
        
        # 读取期望输出
        with open(test_case['output'], 'r', encoding='utf-8') as f:
            expected_output = f.read()
        
        # 运行程序
        start_time = time.time()
        try:
            run_result = subprocess.run(
                [f'{tmpdir}/solution.exe'],
                input=input_data,
                capture_output=True,
                text=True,
                timeout=RUN_TIMEOUT
            )
            elapsed = time.time() - start_time
            total_time += elapsed
            
            # 检查运行结果
            if run_result.returncode != 0:
                print(f"    ❌ 运行时错误 (返回码: {run_result.returncode})")
                all_passed = False
                if run_result.stderr:
                    print(f"    ⚠️ 错误信息: {run_result.stderr[:100]}")
                break
            
            # 比较输出
            if compare_output(run_result.stdout, expected_output):
                passed_count += 1
                print(f"    ✅ 通过 (耗时: {elapsed:.3f}s)")
            else:
                print(f"    ❌ 答案错误")
                # 显示差异（前100个字符）
                print(f"    期望: {expected_output[:100]}...")
                print(f"    实际: {run_result.stdout[:100]}...")
                all_passed = False
                break
                
        except subprocess.TimeoutExpired:
            elapsed = time.time() - start_time
            print(f"    ⏰ 超时 (>{RUN_TIMEOUT}秒)")
            all_passed = False
            break
        except Exception as e:
            print(f"    ❌ 运行异常: {e}")
            all_passed = False
            break
    
    # ========== 更新结果 ==========
    if all_passed and passed_count == len(test_cases):
        # 全部通过 AC
        avg_time = total_time / len(test_cases) if test_cases else 0
        update_result(sid, 4, int(avg_time * 1000), 1024, 1.0)
        print(f"  ✅ 提交 {sid} AC！(平均耗时: {avg_time:.3f}s, 通过 {passed_count}/{len(test_cases)})")
    elif passed_count > 0:
        # 部分通过
        pass_rate = passed_count / len(test_cases)
        avg_time = total_time / len(test_cases) if test_cases else 0
        update_result(sid, 6, int(avg_time * 1000), 1024, pass_rate)  # 6 = 部分正确
        print(f"  ⚠️ 提交 {sid} 部分正确 (通过 {passed_count}/{len(test_cases)})")
    else:
        # 失败
        update_result(sid, 3, 0, 0, 0)
        print(f"  ❌ 提交 {sid} 失败")
    
    # 清理
    shutil.rmtree(tmpdir, ignore_errors=True)

def main():
    print(f"Python 判题机启动...")
    print(f"📋 配置: 编译超时={COMPILE_TIMEOUT}秒, 运行超时={RUN_TIMEOUT}秒")
    print(f"📋 数据路径: {DATA_PATH}")
    print("listenning...")
    
    # 检查数据目录是否存在
    if not os.path.exists(DATA_PATH):
        print(f"⚠️ 警告: 数据目录不存在: {DATA_PATH}")
    
    while True:
        try:
            sid = get_pending()
            if not sid:
                # print(f"⏳ 无待判题，{JUDGE_INTERVAL}秒后重试...")
                time.sleep(JUDGE_INTERVAL)
                continue
            judge(sid)
        except KeyboardInterrupt:
            print("\n🛑 判题机已停止")
            break
        except Exception as e:
            print(f"❌ 错误: {e}")
            import traceback
            traceback.print_exc()
            time.sleep(2)

if __name__ == '__main__':
    main()