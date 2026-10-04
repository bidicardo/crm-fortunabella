<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            // restrict: клиента и контрагента со сделками удалить нельзя (RecordDeleter проверяет раньше, БД — страховка).
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('counterparty_id')->nullable()->constrained()->restrictOnDelete();
            // Деньги — целые рубли.
            $table->unsignedInteger('amount')->nullable();
            $table->date('event_date')->nullable();
            $table->time('event_time')->nullable();
            // Типы столов — список значений TableType.
            $table->json('table_types')->nullable();
            $table->unsignedTinyInteger('duration_hours')->nullable();
            // Характер мероприятия — свободный текст.
            $table->text('event_kind')->nullable();
            $table->unsignedInteger('guests')->nullable();
            $table->string('address')->nullable();
            $table->string('lead_source', 20)->nullable();
            $table->string('lead_source_other')->nullable();
            $table->unsignedInteger('prepayment_amount')->nullable();
            $table->boolean('prepayment_paid')->default(false);
            $table->foreignId('prepayment_holder_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sold_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('stage', 20)->default('new');
            // Порядок карточки в колонке канбана.
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            // Колонка канбана; занятая дата и календарь — по event_date.
            $table->index(['stage', 'position']);
            $table->index('event_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
