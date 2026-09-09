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
        Schema::create('dns_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('hostname');
            $table->string('ip_address');
            $table->enum('type', ['authoritative', 'recursive', 'both'])->default('authoritative');
            $table->enum('status', ['online', 'offline', 'warning'])->default('offline');
            $table->integer('port')->default(53);
            $table->string('api_url')->nullable();
            $table->text('api_key')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('last_check_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dns_servers');
    }
};
