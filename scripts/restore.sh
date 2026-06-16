#!/usr/bin/env bash
#
# استعادة قاعدة PostgreSQL من نسخة custom (-Fc). PRD §23.
# اختبار الاستعادة إلزامي قبل إطلاق 0C.
#
# الاستخدام:
#   PGPASSWORD=*** ./scripts/restore.sh <dump-file> [target-db]
# يُنشئ قاعدة الهدف إن لم تكن موجودة، ثم يستعيد فيها.
# تحذير: --clean يحذف الكائنات الموجودة في قاعدة الهدف أولًا.
set -euo pipefail

DUMP_FILE="${1:?يجب تمرير مسار ملف النسخة}"
TARGET_DB="${2:-bayt_almoswer_restore}"

PGHOST="${PGHOST:-127.0.0.1}"
PGPORT="${PGPORT:-5432}"
PGUSER="${PGUSER:-postgres}"

if [ ! -s "$DUMP_FILE" ]; then
  echo "✗ ملف النسخة غير موجود أو فارغ: $DUMP_FILE" >&2
  exit 1
fi

echo "▶ التأكد من وجود قاعدة الهدف: $TARGET_DB"
EXISTS=$(psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -tAc \
  "SELECT 1 FROM pg_database WHERE datname='${TARGET_DB}'" postgres || true)
if [ "$EXISTS" != "1" ]; then
  psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -c "CREATE DATABASE \"${TARGET_DB}\" ENCODING 'UTF8'" postgres
  echo "  أُنشئت قاعدة الهدف."
fi

echo "▶ استعادة $DUMP_FILE → $TARGET_DB"
pg_restore -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$TARGET_DB" \
  --clean --if-exists --no-owner --no-privileges "$DUMP_FILE"

echo "✓ تمت الاستعادة في $TARGET_DB"
