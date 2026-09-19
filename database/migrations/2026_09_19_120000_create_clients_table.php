<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Обычные индексы, не уникальные: дубли ищутся и сливаются вручную (задачи 17–18).
            $table->string('phone', 16)->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('social')->nullable();
            $table->string('legal_type', 20)->nullable();
            $table->string('role', 20)->nullable();
            $table->string('contact_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
