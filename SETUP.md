# 🚀 دليل الإعداد — بيت المصور MVP-0A

> **المرجع:** PRD 4.0 — Final / Build-Ready Edition

## المتطلبات الأساسية

- **Docker** 20.10+ و **Docker Compose** 2.0+
- أو **محلياً:**
  - PHP 8.2+ مع extensionsأساسية
  - Node.js 20 LTS
  - PostgreSQL 15+
  - Redis 7+
  - Git 2.40+

---

## 1. الإعداد السريع (Docker)

### 1.1 استنساخ المستودع
```bash
git clone https://github.com/zagweb101/bmcenter.git
cd bmcenter
```

### 1.2 نسخ متغيرات البيئة
```bash
cp .env.example .env
```

### 1.3 تشغيل Docker Compose
```bash
docker-compose up -d
```

### 1.4 تهيئة قاعدة البيانات
```bash
docker-compose exec api php artisan migrate --seed
```

### 1.5 إنشاء حساب Super Admin (مؤقت محلي)
```bash
docker-compose exec api php artisan tinker

# في الـ Tinker shell:
App\Models\Organization::create([
  'name' => 'Bayt Almoswer Academy',
  'vat_number' => '311111111100003',
  'legal_address' => 'Riyadh, SA'
]);

App\Models\User::create([
  'name' => 'Admin',
  'email' => 'admin@baytalmoswer.local',
  'password' => bcrypt('password'),
  'organization_id' => 1,
  'is_super_admin' => true
]);

exit
```

### 1.6 الدخول
- **لوحة الإدارة:** http://localhost:3000
- **API:** http://localhost:8000/api/v1
- **بريد إلكتروني:** admin@baytalmoswer.local
- **كلمة المرور:** password

---

## 2. الإعداد المحلي (بدون Docker)

### 2.1 Backend — Laravel

#### تثبيت المتطلبات
```bash
cd services/core-api
composer install
php artisan key:generate
```

#### إعداد قاعدة البيانات
```bash
# تحديث .env محلياً
cp .env.example .env
```

حرّر `.env` مع بيانات PostgreSQL و Redis:
```env
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=bayt_almoswer
DB_USERNAME=postgres
DB_PASSWORD=your_password

REDIS_HOST=localhost
REDIS_PORT=6379
```

#### تشغيل الترحيلات
```bash
php artisan migrate
php artisan seed --class=InitialDataSeeder
```

#### تشغيل الخادم
```bash
# في تاب منفصل:
php artisan serve

# في تاب آخر:
php artisan queue:listen
```

API متوفر على: http://localhost:8000/api/v1

---

### 2.2 Frontend — Next.js

#### تثبيت المتطلبات
```bash
cd apps/core-admin
npm install
```

#### ضبط متغيرات البيئة
```bash
# انسخ من جذر المشروع
cp ../../.env.example .env.local
```

حرّر `apps/core-admin/.env.local`:
```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
```

#### تشغيل خادم التطوير
```bash
npm run dev
```

الواجهة متوفرة على: http://localhost:3000

---

## 3. الأوامر الشائعة

### Docker Compose
```bash
# عرض السجلات
docker-compose logs -f api
docker-compose logs -f frontend

# إيقاف الخدمات
docker-compose down

# إعادة بناء الصور
docker-compose build --no-cache

# الدخول إلى الحاوية
docker-compose exec api sh
docker-compose exec frontend sh
```

### Laravel Artisan
```bash
# إنشاء نموذج جديد مع الترحيل والسيد
php artisan make:model Person -m

# إنشاء حاكم (Policy)
php artisan make:policy PersonPolicy -m Person

# تشغيل الاختبارات
php artisan test

# Tinker (REPL)
php artisan tinker
```

### Next.js
```bash
# بناء الإنتاج
npm run build

# اختبار الإنتاج محلياً
npm run build && npm start

# Storybook
npm run storybook

# اختبارات
npm run test
```

---

## 4. أول خطوات التطوير

### 4.1 فهم البنية
```
bayt-almoswer/
├── services/core-api/          # Backend Laravel
│   ├── app/Models/             # نماذج Eloquent
│   ├── app/Policies/           # RBAC Policies
│   └── routes/api.php          # توجيهات API
├── apps/core-admin/            # Frontend Next.js
│   ├── app/                    # App Router
│   ├── components/             # React Components
│   └── lib/api.ts              # عميل API
└── packages/
    ├── design-tokens/          # Tailwind + CSS Variables
    └── ui/                     # مكونات مشتركة
```

### 4.2 إضافة ميزة جديدة

#### المرحلة 1: Backend
```bash
cd services/core-api

# إنشاء نموذج جديد
php artisan make:model Course -m

# إضافة سياسة (Policy)
php artisan make:policy CoursePolicy -m Course

# إنشاء Controller
php artisan make:controller CourseController --api --model=Course
```

#### المرحلة 2: Frontend
```bash
cd apps/core-admin

# إنشاء مكون React
# باستخدام `components/CourseForm.tsx`

# تحديث عميل API
# في `lib/api.ts`
```

#### المرحلة 3: الاختبارات
```bash
# Backend
php artisan test --filter=CourseTest

# Frontend
npm run test -- CourseForm.test.tsx
```

---

## 5. الاختبارات

### تشغيل مجموعة الاختبارات

#### Backend (PHPUnit)
```bash
cd services/core-api
php artisan test
```

#### Frontend (Vitest)
```bash
cd apps/core-admin
npm run test
```

#### E2E (Playwright)
```bash
cd apps/core-admin
npm run test:e2e
```

---

## 6. المراقبة والتصحيح

### قاعدة البيانات
```bash
# الدخول إلى psql
psql -h localhost -U postgres -d bayt_almoswer

# الاستعلام عن الجداول
\dt

# الخروج
\q
```

### Redis
```bash
redis-cli
KEYS *
FLUSHDB  # تنظيف محلي فقط
```

### السجلات
```bash
# Laravel
tail -f storage/logs/laravel.log

# Next.js (من console في المتصفح)
```

---

## 7. النسخ الاحتياطية والاستعادة

### النسخ الاحتياطية
```bash
cd scripts
./backup.sh
```

### الاستعادة
```bash
cd scripts
./restore.sh storage/backups/bayt_almoswer_YYYYMMDD_HHMMSS.dump bayt_almoswer_restore
```

---

## 8. الإطلاق على الإنتاج

> ⚠️ **تحذير:** قبل الإطلاق، أكمل جميع القوائم من PRD §28 و §29.

### التحضيرات
- [ ] اعتماد نموذج البيانات
- [ ] اعتماد RBAC والصلاحيات
- [ ] اختبار Backup/Restore
- [ ] ربط ZATCA (Sandbox أولاً)
- [ ] ربط بوابة الدفع (Sandbox)
- [ ] إعداد Monitoring (Sentry)
- [ ] مراجعة الأمان (CORS, HTTPS, Headers)
- [ ] مراجعة الامتثال (PDPL, ZATCA)

### النشر
```bash
# على VPS أو Cloud
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# التحقق من الصحة
curl https://api.baytalmoswer.net/health
```

---

## 9. استكشاف الأخطاء

### الخطأ: "Connection refused"
```bash
# تحقق من حالة Docker
docker-compose ps

# أعد تشغيل الخدمات
docker-compose restart
```

### الخطأ: "SQLSTATE[08006]"
```bash
# تحقق من متغيرات DB في .env
docker-compose logs postgres
```

### الخطأ: "Cannot find module"
```bash
# أعد تثبيت العلاقات
npm install
npm ci  # إذا كان لديك package-lock.json
```

---

## 10. الموارد المفيدة

- **PRD 4.0:** `docs/product/PRD-4.0.md`
- **نموذج البيانات:** `docs/architecture/data-model.md`
- **API Documentation:** http://localhost:8000/docs (OpenAPI)
- **Storybook:** http://localhost:6006

---

**آخر تحديث:** 16 يونيو 2026  
**الإصدار:** MVP-0A — Foundation  
**الحالة:** قيد التطوير
