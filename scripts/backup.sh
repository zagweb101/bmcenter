#!/usr/bin/env bash
#
# نسخ احتياطي لقاعدة PostgreSQL (BAYT ALMOSWER). PRD §23.
# صيغة custom (-Fc) لاستعادة مرنة عبر pg_restore. تشفير/تخزين منفصل يُضاف في الإنتاج.
#
# الاستخدام:
#   PGPASSWORD=*** ./scripts/backup.sh
# متغيّرات البيئة (بقيم افتراضية للتطوير):
#   PGHOST=127.0.0.1 PGPORT=5432 PGUSER=postgres PGDATABASE=bayt_almoswer
#   BACKUP_DIR=./storage/backups  RETENTION_DAYS=14
set -euo pipefail

PGHOST="${PGHOST:-127.0.0.1}"
PGPORT="${PGPORT:-5432}"
PGUSER="${PGUSER:-postgres}"
PGDATABASE="${PGDATABASE:-bayt_almoswer}"
BACKUP_DIR="${BACKUP_DIR:-./storage/backups}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"

mkdir -p "$BACKUP_DIR"
STAMP="$(date +%Y%m%d_%H%M%S)"
OUT="$BACKUP_DIR/${PGDATABASE}_${STAMP}.dump"

echo "▶ نسخ احتياطي: $PGDATABASE → $OUT"
pg_dump -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$PGDATABASE" -Fc -f "$OUT"

# سلامة الملف
if [ ! -s "$OUT" ]; then
  echo "✗ فشل: ملف النسخة فارغ" >&2
  exit 1
fi
echo "✓ تمت النسخة ($(du -h "$OUT" | cut -f1))"

# الاحتفاظ: حذف النسخ الأقدم من RETENTION_DAYS
find "$BACKUP_DIR" -name "${PGDATABASE}_*.dump" -type f -mtime +"$RETENTION_DAYS" -print -delete \
  | sed 's/^/  حُذِفت نسخة قديمة: /' || true

echo "✓ انتهى."
