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
        Schema::create('server_health_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dns_server_id')->constrained('dns_servers')->cascadeOnDelete();
            $table->enum('status', ['online', 'offline', 'warning']);
            $table->decimal('latency_ms', 8, 2)->nullable();
            $table->boolean('port_53_tcp')->default(false);
            $table->boolean('port_53_udp')->default(false);
            $table->boolean('api_status')->nullable();
            $table->boolean('dns_query_status')->default(false);
            $table->json('response_summary')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['dns_server_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_health_checks');
    }
};
