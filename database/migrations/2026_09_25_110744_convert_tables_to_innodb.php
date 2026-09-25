<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tablas = DB::select(
            "SELECT TABLE_NAME AS tabla FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ? AND ENGINE = 'MyISAM'",
            [DB::getDatabaseName()]
        );

        foreach ($tablas as $fila) {
            DB::statement("ALTER TABLE `{$fila->tabla}` ENGINE=InnoDB");
        }
    }

    public function down(): void
    {
        // No se revierte: InnoDB es el motor correcto para transacciones
    }
};