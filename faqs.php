<style>
body {
    background: url('/image/555.jpg') 0% 0% / 100% no-repeat fixed !important;
    min-height: 100vh !important;
}
</style>
<?php
// faqs.php - 修复版
session_start();

// 定义变量（防止 undefined）
if (!isset($OJ_TEMPLATE)) {
    $OJ_TEMPLATE = 'bs3';
}
if (!isset($OJ_NAME)) {
    $OJ_NAME = 'HHJ-OJ';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $OJ_NAME; ?> - 常见问题</title>
</head>
<body>

<div class="container"
    
    <div class="jumbotron">
        <center><font size="+3"><?php echo $OJ_NAME; ?> FAQ</font></center>
        <hr>

        <!-- Q1 -->
        <p><font color="green">Q</font>: gets函数没有了吗？<br>
        <font color="red">A</font>: gets函数因为不能限制输入的长度，造成了历史上大量的缓冲区溢出漏洞，因此在最新版本中被彻底删除了，请使用fgets这个函数取代。或者使用下面的宏定义来取代：<br>
        <pre>#define gets(S) fgets(S,sizeof(S),stdin)</pre>
        </p>
        <hr>

        <p><font color="green">Q</font>: 多打了一个空格或endl会报错？<br>
        <font color="red">A</font>: 对的，因为本系统是Windows，用不了默认的评判机。因为评判机的缺陷<br>
        <pre>not using endl and " "<br>
别用endl和空格
        </pre>
        </p>
        <hr>

        <!-- Q2 -->
        <p><font color="green">Q</font>: 这个在线裁判系统使用什么样的编译器和编译选项？<br>
        <font color="red">A</font>: 系统运行于 Windows 10LTSC2021不忘初心精简版。使用 GNU GCC/G++ 作为C/C++编译器，用 Java SE 17 编译 Java。对应的编译选项如下：<br>
        </p>
        <table border="1">
            <tr>
                <td>C:</td>
                <td><font color="blue">gcc Main.c -o Main -fno-asm -Wall -lm --static -std=c99 -DONLINE_JUDGE</font>
                    <pre>#pragma GCC optimize ("O2")</pre> 可以手工开启O2优化
                </td>
            </tr>
            <tr>
                <td>C++:</td>
                <td><font color="blue">g++ -fno-asm -Wall -lm --static -std=c++14 -DONLINE_JUDGE -o Main Main.cc</font></td>
            </tr>
            <tr>
                <td>Java:</td>
                <td><font color="blue">javac -J-Xms32m -J-Xmx256m Main.java</font>
                    <br><font size="-1" color="red">* Java有额外2秒和512M内存</font>
                </td>
            </tr>
        </table>
        <p>
        编译器版本为：<br>
        <font color="blue">Gcc version 11.5.0 (x86_64-posix-seh-rev0, Built by MinGW-W64 project)</font><br>
        <font color="blue">Java(TM) SE Runtime Environment 17 (build 17.0.19+9-LTS-183)</font><br>
        <font color="blue">Python 3.8.6 (tags/v3.8.6:db45529, Sep 23 2020, 15:52:53)</font>
        </p>
        <hr>

        <!-- Q3 -->
        <p><font color="green">Q</font>: 程序怎样取得输入、进行输出？<br>
        <font color="red">A</font>: 你的程序应该从标准输入 stdin ('Standard Input') 获取输入，并将结果输出到标准输出 stdout ('Standard Output')。例如，在C语言可以使用 'scanf'，在C++可以使用 'cin' 进行输入；在C使用 'printf'，在C++使用 'cout' 进行输出。
        <br><br>
        用户程序不允许直接读写文件，如果这样做可能会判为运行时错误 "<font color="green">Runtime Error</font>"。
        <br><br>

        <p>为了方便使用本地文件调试，C/C++也可以用下面的方法来仅在本地运行时进行输入、输出重定向。</p>
        <pre>
#ifdnef ONLINE_JUDGE
    freopen("sample.in", "r", stdin);
    freopen("sample.out", "w", stdout);
#endif
        </pre>
        <hr>

        <!-- Q4 -->
        <p><font color="green">Q</font>: 为什么我的程序在自己的电脑上正常编译，而系统告诉我编译错误！<br>
        <font color="red">A</font>: GCC的编译标准与VC6有些不同，更加符合c/c++标准：<br>
        <ul>
            <li><font color="blue">main</font> 函数必须返回 <font color="blue">int</font>，<font color="blue">void main</font> 的函数声明会报编译错误。</li>
            <li><font color="green">i</font> 在循环外失去定义 "<font color="blue">for</font>(<font color="blue">int</font> <font color="green">i</font>=0...){...}"</li>
            <li><font color="green">itoa</font> 不是ansi标准函数。</li>
            <li><font color="green">__int64</font> 不是ANSI标准定义，只能在VC使用，但是可以使用 <font color="blue">long long</font> 声明64位整数。如果用了__int64，试试提交前加一句 <font color="blue">#define __int64 long long</font>，scanf和printf请使用 %lld 作为格式。</li>
        </ul>
        <hr>

        <!-- Q5 -->
        <p><font color="green">Q</font>: 系统返回信息都是什么意思？<br>
        <font color="red">A</font>: 详见下述：<br>
        </p>
        <ul>
            <li><font color="blue">Pending</font>: 系统忙，你的答案在排队等待。</li>
            <li><font color="blue">Pending Rejudge</font>: 因为数据更新或其他原因，系统将重新判你的答案。</li>
            <li><font color="blue">Compiling</font>: 正在编译。</li>
            <li><font color="blue">Running &amp; Judging</font>: 正在运行和判断。</li>
            <li><font color="blue">Accepted</font>: 程序通过！</li>
            <li><font color="blue">Presentation Error</font>: 答案基本正确，但是格式不对。</li>
            <li><font color="blue">Wrong Answer</font>: 答案不对，仅仅通过样例数据的测试并不一定是正确答案，一定还有你没想到的地方。</li>
            <li><font color="blue">Time Limit Exceeded</font>: 运行超出时间限制，检查下是否有死循环，或者应该有更快的计算方法。</li>
            <li><font color="blue">Memory Limit Exceeded</font>: 超出内存限制，数据可能需要压缩，检查内存是否有泄露。</li>
            <li><font color="blue">Output Limit Exceeded</font>: 输出超过限制，你的输出比正确答案长了两倍。</li>
            <li><font color="blue">Runtime Error</font>: 运行时错误，非法的内存访问，数组越界，指针漂移，调用禁用的系统函数。请点击后获得详细输出。</li>
            <li><font color="blue">Compile Error</font>: 编译错误，请点击后获得编译器的详细输出。</li>
        </ul>
        <hr>

        <!-- Q6 -->
        <p><font color="green">Q</font>: 如何参加在线比赛？<br>
        <font color="red">A</font>: <a href="registerpage.php">注册</a> 一个帐号，然后就可以练习，点击比赛列表 Contests 可以看到正在进行的比赛并参加。</p>
        <hr>

        <center>
            <font color="green" size="+2">其他问题请访问 <a href="bbs"><?php echo $OJ_NAME; ?> 论坛系统</a></font>
        </center>
        <hr>

        <center>
            <table width="100%" border="0">
                <tr>
                    <td align="right" width="65%">
                        <a href="index.php"><font color="red"><?php echo $OJ_NAME; ?></font></a>
                        <a href="https://github.com/zhblue/hustoj"><font color="red">2024.8.4</font></a>
                    </td>
                </tr>
            </table>
        </center>
    </div>
</div>



</body>
</html>