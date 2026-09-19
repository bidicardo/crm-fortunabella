<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Роль клиента — свободный текст, а не список вариантов.
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('role')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('role', 20)->nullable()->change();
        });
    }
};
