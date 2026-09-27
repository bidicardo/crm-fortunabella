<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counterparties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Тип — свободный текст (ивент-агентство, тамада, ресторан…).
            $table->string('type')->nullable();
            $table->string('phone', 16)->nullable();
            $table->string('email')->nullable();
            $table->string('social')->nullable();
            $table->text('cooperation_terms')->nullable();
            $table->date('cooperation_started_at')->nullable();
            $table->string('website')->nullable();
            $table->string('telegram')->nullable();
            $table->string('address')->nullable();
            $table->string('stage', 20)->default('first_contact');
            // Порядок карточки в колонке канбана.
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            // Колонка канбана: фильтр по этапу и сортировка по порядку.
            $table->index(['stage', 'position']);
        });

        Schema::create('counterparty_contacts', function (Blueprint $table) {
            $table->id();
            // Контактные лица удаляются только вместе с контрагентом.
            $table->foreignId('counterparty_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('position_title')->nullable();
            $table->string('phone', 16)->nullable();
            $table->string('email')->nullable();
            $table->string('social')->nullable();
            $table->string('contact_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counterparty_contacts');
        Schema::dropIfExists('counterparties');
    }
};
