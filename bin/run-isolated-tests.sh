#!/usr/bin/env bash
#
# 跑需要「一个测试方法一个 PHP 进程」的用例。
#
# 背景：这些测试用 Mockery::mock('overload:...') 拦截业务代码里的 new XxxService()。
# overload 是进程级全局的，同一个进程里对同一个类 overload 两次会 fatal
# （Cannot redeclare Mockery_N_Xxx::mockery_init），所以每个测试方法都必须独占进程。
#
# 以前靠 PHPUnit 的 @runTestsInSeparateProcesses 注解（= 每方法一个子进程）实现。
# 但 PHP 8.4 下子进程在 autoload 阶段会触发 Illuminate 8（已 EOL）的弃用输出，
# PHPUnit 会把它当成测试错误，所以改为在 runner 级做同样的隔离：
# 每个测试方法起一个 phpunit 进程（--filter 精确匹配）。
#
# 用法: bin/run-isolated-tests.sh [-d key=value ...]
set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_BIN="${PHP_BIN:-php}"
PHPUNIT="$ROOT/vendor/bin/phpunit"
LIST="$ROOT/tests/isolated-files.txt"

failed_files=0
failed_tests=0
total_tests=0
failed_names=()

while IFS= read -r file; do
    [ -z "$file" ] && continue
    abs="$ROOT/$file"
    [ -f "$abs" ] || { echo "⚠️  文件不存在: $file"; continue; }
    methods=$(grep -oE 'public function (test[A-Za-z0-9_]+)' "$abs" | awk '{print $3}')
    [ -z "$methods" ] && continue
    file_failed=0
    for m in $methods; do
        total_tests=$((total_tests + 1))
        out=$("$PHP_BIN" "$PHPUNIT" --no-coverage "$@" --filter "$m" "$abs" 2>&1)
        rc=$?
        if [ $rc -ne 0 ]; then
            file_failed=$((file_failed + 1))
            failed_tests=$((failed_tests + 1))
            failed_names+=("${file#tests/}::$m")
        fi
    done
    if [ $file_failed -eq 0 ]; then
        method_count=$(echo "$methods" | wc -w | tr -d " "); echo "✅ ${file#tests/}  (${method_count} 个方法全通过)"
    else
        failed_files=$((failed_files + 1))
        echo "❌ ${file#tests/}  失败 $file_failed 个方法"
    fi
done < "$LIST"

echo
echo "隔离用例：$total_tests 个方法，失败 $failed_tests"
if [ ${#failed_names[@]} -gt 0 ]; then
    printf '  - %s\n' "${failed_names[@]}"
fi
[ $failed_tests -eq 0 ]
