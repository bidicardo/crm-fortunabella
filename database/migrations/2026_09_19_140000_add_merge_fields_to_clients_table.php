<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // История объединения дубля: куда влит, кем и когда.
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('merged_into_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('merged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('merged_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('merged_into_id');
            $table->dropConstrainedForeignId('merged_by');
            $table->dropColumn('merged_at');
        });
    }
};
