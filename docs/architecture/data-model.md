# نموذج البيانات — MVP-0A (الأساس المؤسسي)

> **المرجع:** PRD 4.0 §11, 12, 14, 16, 17 + ADR-003, ADR-006  
> **الحالة:** تصميم أولي — يُعتمد عبر PR قبل البناء  
> **آخر تحديث:** 16 يونيو 2026

---

## نظرة عامة

```
Organization (جذر Multi-Tenancy)
├── Branch              # الفروع
├── User                # المستخدمون
├── Role                # الأدوار (Super Admin, Manager, CRM Agent...)
├── Permission          # الصلاحيات (RBAC)
├── Person              # الأشخاص (Person 360)
│   ├── Contact         # وسائل التواصل
│   ├── Lead            # العملاء المحتملون (MVP-0B)
│   ├── Student         # الطلاب (MVP-0B)
│   └── Consent         # السجلات والموافقات
├── AuditLog            # سجل الأنشطة
├── PrivacyRequest      # طلبات الخصوصية
├── File                # الملفات والمستندات
└── Settings            # إعدادات المؤسسة

Invoice (MVP-0C — الفاتورة)
├── InvoiceLine
├── Tax
├── Payment
└── PaymentAllocation
```

## الجداول الأساسية

### 1. organizations
- **id**: UUID (PK)
- **name**: string
- **vat_number**: string (UNIQUE)
- **legal_address**: text
- **country_code**: string (SA)
- **is_active**: boolean
- **created_at, updated_at**: timestamp

### 2. branches
- **id**: UUID (PK)
- **organization_id**: UUID (FK)
- **name**: string
- **code**: string (UNIQUE per org)
- **address**: text
- **is_active**: boolean

### 3. users (Eloquent\Authenticatable)
- **id**: UUID (PK)
- **organization_id**: UUID (FK) — Multi-Tenancy
- **name**: string
- **email**: string (UNIQUE per org)
- **password**: string (bcrypt)
- **email_verified_at**: timestamp (nullable)
- **is_active**: boolean
- **created_at, updated_at**: timestamp

### 4. roles
- **id**: UUID (PK)
- **organization_id**: UUID (FK)
- **name**: string (Super Admin, Manager, CRM Agent...)
- **is_system_role**: boolean
- **is_active**: boolean

### 5. permissions
- **id**: UUID (PK)
- **name**: string
- **resource**: string
- **description**: text

### 6. persons (Person 360)
- **id**: UUID (PK)
- **organization_id**: UUID (FK)
- **first_name**: string
- **last_name**: string
- **identification_number**: string (ENCRYPTED)
- **date_of_birth**: date (nullable)
- **gender**: enum (M, F, Other)
- **created_at, updated_at**: timestamp

### 7. contacts
- **id**: UUID (PK)
- **person_id**: UUID (FK)
- **type**: enum (email, phone, whatsapp)
- **value**: string
- **is_primary**: boolean
- **is_verified**: boolean

### 8. consents (PDPL — متطلبات الخصوصية)
- **id**: UUID (PK)
- **person_id**: UUID (FK)
- **type**: enum (privacy, marketing, whatsapp, image_usage)
- **status**: enum (Granted, Withdrawn)
- **granted_at**: timestamp
- **withdrawn_at**: timestamp (nullable)

### 9. audit_logs (الإلزامية — ADR-005)
- **id**: UUID (PK)
- **user_id**: UUID (FK)
- **organization_id**: UUID (FK)
- **model**: string (User, Invoice...)
- **model_id**: UUID
- **action**: enum (Create, Update, Delete, View)
- **old_values**: json
- **new_values**: json
- **ip_address**: ipaddr
- **created_at**: timestamp

### 10. privacy_requests
- **id**: UUID (PK)
- **person_id**: UUID (FK)
- **request_type**: enum (Access, Correction, Deletion)
- **status**: enum (Submitted, Reviewing, Completed)
- **submitted_at**: timestamp

### 11. files
- **id**: UUID (PK)
- **organization_id**: UUID (FK)
- **name**: string
- **mime_type**: string
- **path**: string
- **size**: biginteger

### 12. invoices (تصميم MVP-0C)
- **id**: UUID (PK)
- **organization_id**: UUID (FK)
- **invoice_number**: string (UNIQUE)
- **type**: enum (Standard, Simplified)
- **status**: enum (Draft, Issued, Cleared, Reported)
- **total_amount**: decimal(12,2) — ADR-006
- **tax_amount**: decimal(12,2)
- **zatca_uuid**: uuid (nullable)
- **zatca_qr**: text (nullable)
- **issue_date**: date

---

## الفهارس الحرجة (Performance)

| جدول | الفهرس |
|---|---|
| users | (organization_id, email) |
| persons | (organization_id, identification_number) |
| contacts | (person_id, type) |
| audit_logs | (organization_id, user_id, model_id) |
| invoices | (organization_id, invoice_number, status) |

---

## أمان و Compliance

✅ **Organization Scope Enforcement** — كل الاستعلامات مصفاة  
✅ **RBAC via Policies** — صلاحيات على الخادم  
✅ **Encryption** — البيانات الحساسة مشفرة  
✅ **Audit Trail** — كل التعديلات مسجلة  
✅ **Decimal Only** — لا Float (ADR-006)  
✅ **Soft Deletes** — للسجلات الحرجة

---

**المراجع:** Tech Lead, DBA  
**الاعتماد المطلوب:** قبل MVP-0A Migration
