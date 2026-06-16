# النسخ الاحتياطي والتعافي (Backup & Restore)

> **المرجع:** PRD 4.0 §23. **اختبار الاستعادة إلزامي قبل إطلاق 0C.**
> **الحالة:** السكربتات جاهزة ومجرّبة محليًا (استعادة ناجحة بمطابقة عدد السجلات).

## 1. النطاق

- قاعدة البيانات: PostgreSQL (`bayt_almoswer`).
- صيغة النسخة: PostgreSQL **custom format** (`-Fc`) — تدعم الاستعادة الانتقائية والتوازي عبر `pg_restore`.
- ملفات Object Storage (S3): تُنسخ ضمن سياسة المزوّد (versioning + lifecycle) — تُوثَّق عند ربط التخزين.

## 2. السكربتات

| السكربت | الغرض |
|---|---|
| [`scripts/backup.sh`](../../scripts/backup.sh) | `pg_dump -Fc` بطابع زمني + سياسة احتفاظ (حذف الأقدم من `RETENTION_DAYS`) |
| [`scripts/restore.sh`](../../scripts/restore.sh) | `pg_restore` إلى قاعدة هدف (يُنشئها إن لزم) — يُستخدم لاختبار الاستعادة |

### متغيّرات البيئة
`PGHOST` `PGPORT` `PGUSER` `PGPASSWORD` `PGDATABASE` `BACKUP_DIR` `RETENTION_DAYS`
(قيم افتراضية للتطوير: `127.0.0.1:5432`, `postgres`, `bayt_almoswer`, `./storage/backups`, `14`).

### تشغيل
```bash
# نسخة احتياطية
PGPASSWORD=*** ./scripts/backup.sh

# اختبار الاستعادة في قاعدة منفصلة (لا تلمس الإنتاج)
PGPASSWORD=*** ./scripts/restore.sh storage/backups/bayt_almoswer_YYYYMMDD_HHMMSS.dump bayt_almoswer_restore
```
> على ويندوز: تُشغَّل عبر Git Bash. أدوات `pg_dump`/`pg_restore` ضمن `PostgreSQL/<ver>/bin`.

## 3. الجدولة (Schedule)

- **نسخ يومي** مؤتمت (cron / Task Scheduler / مدير الاستضافة).
- **نسخة مسبقة قبل أي ترحيل حسّاس** (migration) — إلزامية (§23).
- يُفضَّل WAL archiving / PITR في الإنتاج لتقليل RPO.

## 4. الحماية (يُفعَّل قبل الإنتاج)

- **تشفير** النسخ at-rest.
- **تخزين منفصل** عن خادم القاعدة (موقع/حساب مختلف).
- **إقامة البيانات داخل المملكة** (PDPL — §21، §19.7).
- صلاحيات وصول مقيّدة + تدقيق على الوصول للنسخ.

## 5. RPO / RTO

| المؤشر | القيمة | الحالة |
|---|---|---|
| RPO (أقصى فقد بيانات مقبول) | TBD | يُعتمد قبل Production (§23) |
| RTO (أقصى زمن تعافٍ مقبول) | TBD | يُعتمد قبل Production (§23) |

## 6. إجراء اختبار الاستعادة (Restore Drill)

1. أخذ نسخة حديثة عبر `backup.sh`.
2. استعادتها في قاعدة منفصلة عبر `restore.sh <dump> bayt_almoswer_restore`.
3. **التحقق:** مطابقة عدد السجلات للجداول الأساسية (organizations/roles/permissions/users) بين المصدر والمستعاد.
4. إسقاط قاعدة الاختبار بعد التحقق.
5. توثيق النتيجة والتاريخ.

> **آخر تشغيل مُتحقَّق محليًا (2026-06-16):** نسخة 84KB، استعادة ناجحة، تطابق تام للأعداد (orgs=1, roles=7, perms=12, users=1).

## 7. قبل إطلاق 0C (Checklist)

- [ ] جدولة النسخ اليومي في الإنتاج.
- [ ] تفعيل التشفير والتخزين المنفصل.
- [ ] اعتماد RPO/RTO.
- [ ] اختبار استعادة موثّق على بيئة شبيهة بالإنتاج.
- [ ] نسخة مسبقة قبل كل ترحيل حسّاس.
