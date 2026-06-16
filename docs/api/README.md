# OpenAPI — core-api

> PRD §21 (API-First) و§29 (DoD: تحديث OpenAPI مع كل ميزة).

- **التوليد:** تلقائي عبر [Scramble](https://scramble.dedoc.co/) من المسارات + FormRequests + API Resources (لا حاجة لـ annotations).
- **اللقطة المعتمدة:** [`openapi.json`](openapi.json) — تُحدَّث بإعادة التوليد وتُراجَع عبر PR.
- **واجهة تفاعلية حيّة:** عند تشغيل الخادم محليًا → `http://localhost:8000/docs/api` (و JSON على `/docs/api.json`). Scramble يقيّد العرض لغير الإنتاج افتراضيًا.

## إعادة التوليد
```bash
cd services/core-api
php artisan scramble:export --path="../../docs/api/openapi.json"
```

## التحقق الآلي
اختبار `tests/Feature/OpenApiTest.php` يضمن نجاح التوليد وتغطية المسارات الأساسية ضمن CI (PRD §28).
