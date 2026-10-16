<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settingsTable = config('mediaflow.tables.settings', config('mediaflow.table_prefix', 'mediaflow_') . 'settings');
        Schema::create($settingsTable, function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $settingsTable = config('mediaflow.tables.settings', config('mediaflow.table_prefix', 'mediaflow_') . 'settings');
        Schema::dropIfExists($settingsTable);
    }
};
