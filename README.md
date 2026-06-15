<div align="right" dir="rtl">

# منظومة بيت المصور الرقمية المتكاملة

**BAYT ALMOSWER. ACADEMY** — أول أكاديمية متخصصة في تعليم التصوير في السعودية

| | |
|---|---|
| **الاسم التقني** | `bayt-almoswer` |
| **الإصدار** | PRD 4.0 — Final / Build-Ready |
| **المعمارية** | Modular Monolith + API-First |
| **النطاق الأول** | `baytalmoswer.net` |
| **اللغة الأساسية** | العربية — RTL — Mobile First |
| **السوق الأول** | المملكة العربية السعودية |

> المرجع الأعلى للنطاق وقواعد العمل والامتثال والهوية هو **`docs/product/PRD-4.0.md`**.
> هذا المستودع مُهيّأ كهيكل (Scaffold). لم يبدأ تنفيذ منطق التطبيق بعد — البداية المعتمدة هي **MVP-0A**.

---

## بنية المستودع

```text
bayt-almoswer/
├── apps/
│   ├── core-admin/      # لوحة الإدارة (Next.js) — مؤجّل للتنفيذ في 0A
│   └── public-portal/   # نموذج التقاط بسيط فقط في MVP-0
├── services/
│   └── core-api/        # Laravel API (Modular Monolith)
├── packages/
│   ├── brand/           # أصول الهوية الرسمية (مصدر وحيد — لا يُعاد رسمها)
│   ├── design-tokens/   # Design Tokens (جاهزة — مستخرجة من البراند بوك)
│   ├── ui/              # مكوّنات واجهة مشتركة (RTL / Accessible)
│   ├── api-client/      # عميل API مولّد من OpenAPI
│   ├── auth/            # هوية / SSO / جلسات
│   ├── compliance/      # ZATCA + PDPL + Tax
│   ├── validation/      # قواعد تحقّق مشتركة
│   ├── analytics/       # Event Taxonomy + Views
│   └── config/          # إعداد مشترك
├── skills/              # مهارات بيت المصور العشر (معايير/Checklists)
├── docs/                # مستندات الحزمة صفر (انظر PRD §32)
├── infrastructure/      # IaC / استضافة تدعم إقامة البيانات (PDPL)
├── scripts/             # سكربتات تشغيلية
└── tests/               # اختبارات شاملة عبر الحزم
```

## القرارات المعمارية المثبّتة (ADR Seeds)

- **ADR-001** توحيد تهجئة العلامة على `baytalmoswer`.
- **ADR-002** Modular Monolith لا Microservices في الإصدار الأول.
- **ADR-003** `organization_id` على الكيانات الجذرية من اليوم الأول.
- **ADR-004** عزل ZATCA/Tax/PDPL خلف واجهات قابلة للاستبدال.
- **ADR-005** الـWebhook الموثوق مصدر حالة الدفع لا الـRedirect.
- **ADR-006** Decimal لا Float، والعملة الأساسية SAR.

## التقنيات

- **Backend:** Laravel (أحدث مستقر) · PostgreSQL · Redis · Queues · REST + OpenAPI
- **Frontend:** Next.js (أحدث مستقر) · TypeScript Strict · Tailwind CSS · PWA (بعد استقرار 0A)
- **البحث/التحليلات:** PostgreSQL FTS + `pg_trgm` + Views (قابل للترقية)

</div>
