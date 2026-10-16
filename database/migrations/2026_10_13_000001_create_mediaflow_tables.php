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
        $prefix = config('mediaflow.table_prefix', 'mediaflow_');
        $foldersTable = config('mediaflow.tables.folders', $prefix . 'folders');
        $mediaTable = config('mediaflow.tables.media', $prefix . 'media');
        $variantsTable = config('mediaflow.tables.variants', $prefix . 'variants');
        $sessionsTable = config('mediaflow.tables.upload_sessions', $prefix . 'upload_sessions');

        // 1. Folders table
        Schema::create($foldersTable, function (Blueprint $table) use ($foldersTable) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained($foldersTable)->nullOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('path')->nullable();
            $table->string('owner_id')->nullable()->index();
            $table->string('tenant_id')->nullable()->index();
            $table->timestamps();

            $table->index(['parent_id', 'name']);
        });

        // 2. Media table
        Schema::create($mediaTable, function (Blueprint $table) use ($foldersTable) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('folder_id')->nullable()->constrained($foldersTable)->nullOnDelete();
            $table->string('owner_id')->nullable()->index();
            $table->string('tenant_id')->nullable()->index();
            $table->string('collection')->nullable()->default('default')->index();
            $table->string('original_name');
            $table->string('disk')->default('public');
            $table->string('storage_key')->unique();
            $table->string('mime_type');
            $table->string('extension', 32)->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->string('checksum', 64)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('visibility', 32)->default('public');
            $table->string('status', 32)->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->string('uploaded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['disk', 'status']);
            $table->index(['folder_id', 'created_at']);
        });

        // 3. Variants table
        Schema::create($variantsTable, function (Blueprint $table) use ($mediaTable) {
            $table->id();
            $table->foreignId('media_id')->constrained($mediaTable)->cascadeOnDelete();
            $table->string('preset', 64);
            $table->string('disk')->default('public');
            $table->string('storage_key');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->string('status', 32)->default('ready');
            $table->timestamps();

            $table->unique(['media_id', 'preset']);
        });

        // 4. Temporary upload sessions
        Schema::create($sessionsTable, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('original_name');
            $table->unsignedBigInteger('size_bytes');
            $table->string('mime_type');
            $table->string('temp_disk')->default('local');
            $table->string('temp_path');
            $table->string('status', 32)->default('uploading');
            $table->json('options')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = config('mediaflow.table_prefix', 'mediaflow_');
        Schema::dropIfExists(config('mediaflow.tables.upload_sessions', $prefix . 'upload_sessions'));
        Schema::dropIfExists(config('mediaflow.tables.variants', $prefix . 'variants'));
        Schema::dropIfExists(config('mediaflow.tables.media', $prefix . 'media'));
        Schema::dropIfExists(config('mediaflow.tables.folders', $prefix . 'folders'));
    }
};
