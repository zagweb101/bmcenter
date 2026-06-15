<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الشخص (Person) — كيان موحّد (Person 360). PRD §11.
 * قد يمتلك أدوارًا متعددة لاحقًا (User/Lead/Student/Trainer/...).
 * منع التكرار: Exact Match في 0A (جوال/بريد/هوية) — يُفرض عبر فهارس فريدة + خدمة المطابقة.
 * البيانات الحساسة (الهوية/البنكية) مشفّرة ومقنّعة افتراضيًا (§11 البيانات الحساسة).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('full_name')->nullable(); // مُشتق — مفيد للبحث pg_trgm لاحقًا

            // حقول التطبيع لمنع التكرار (Exact Match — 0A)
            $table->string('country_code', 4)->nullable();           // مفتاح الدولة (+966)
            $table->string('phone_national', 20)->nullable();        // الجوال بصيغة وطنية مطبّعة
            $table->string('phone_e164', 24)->nullable();            // الجوال بصيغة E.164 (مطابقة)
            $table->string('email')->nullable();                     // مطبّع lowercase
            $table->string('national_id_hash', 64)->nullable();      // hash للهوية (مطابقة بلا كشف)

            // حقول حساسة مشفّرة (PRD §11, §19.3 — تُجمع عند الحاجة فقط)
            $table->text('national_id_encrypted')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 16)->nullable();
            $table->string('nationality', 64)->nullable();

            // Person 360 / الدمج (PRD §11): لا يُحذف المدمج، تُحفظ خريطة المعرفات
            $table->foreignId('merged_into_person_id')->nullable()
                  ->constrained('persons')->nullOnDelete();
            $table->timestamp('merged_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Exact Match: فريد داخل المؤسسة (يمنع الإنشاء — §11)
            $table->unique(['organization_id', 'phone_e164']);
            $table->unique(['organization_id', 'email']);
            $table->unique(['organization_id', 'national_id_hash']);
            $table->index(['organization_id', 'full_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persons');
    }
};
