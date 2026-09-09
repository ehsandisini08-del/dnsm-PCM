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
        Schema::create('dns_template_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dns_template_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SRV', 'CAA', 'PTR', 'SOA']);
            $table->text('value');
            $table->integer('ttl')->default(3600);
            $table->integer('priority')->nullable();
            $table->integer('weight')->nullable();
            $table->integer('port')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dns_template_records');
    }
};
