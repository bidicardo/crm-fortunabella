<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            // Полиморфная связь: клиент, а позже сделка и контрагент. morphs() создаёт индекс (subject_type, subject_id).
            $table->morphs('subject');
            // Кто изменил; пусто для системных действий (например, заявка с лендинга).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 32);
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
