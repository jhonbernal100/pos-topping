<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('sede_id')->nullable()->after('tenant_id')
                  ->constrained('sedes')->nullOnDelete();
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('sede_id')->nullable()->after('tenant_id')
                  ->constrained('sedes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sede_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sede_id');
        });
    }
};