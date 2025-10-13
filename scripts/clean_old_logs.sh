#!/bin/sh
# 通用版：清除專案 runtime/log/ 下三個月前的 YYYYMM 資料夾
# 放置位置：/www/wwwroot/<project_name>/scripts/clean_old_logs.sh
# 使用相對路徑讓不同專案可共用
# 例：cd /www/wwwroot/bigwinner_site/scripts && ./clean_old_logs.sh

# 以此腳本所在路徑為基準，推導出專案根目錄
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
LOG_DIR="$PROJECT_DIR/runtime/log"

# 計算三個月前的年月 (YYYYMM)
Y=$(date +%Y)
M=$(date +%m)
M=$((10#$M - 3))
if [ $M -le 0 ]; then
  M=$((M + 12))
  Y=$((Y - 1))
fi
CUTOFF=$(printf %04d%02d "$Y" "$M")

now=$(date +%F\ %T)
echo "[$now] Cleaning logs older than $CUTOFF under $LOG_DIR"

# 刪除三個月前的資料夾
for dir in "$LOG_DIR"/*; do
  [ -d "$dir" ] || continue
  bn=$(basename "$dir")

  case "$bn" in
    [0-9][0-9][0-9][0-9][0-9][0-9])
      if [ "$bn" -lt "$CUTOFF" ]; then
        echo "Deleting old log folder: $dir"
        rm -rf -- "$dir"
      fi
      ;;
  esac
done

now=$(date +%F\ %T)
echo "[$now] Cleaning completed."
