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
        // PowerDNS Domains (Zones) table
        Schema::create('domains', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255)->unique();
            $table->string('master', 128)->nullable();
            $table->integer('last_check')->nullable();
            $table->string('type', 8)->default('NATIVE');
            $table->unsignedInteger('notified_serial')->nullable();
            $table->string('account', 40)->nullable();
            $table->text('options')->nullable();
            $table->string('catalog', 255)->nullable()->index();

            // ISP DNS Manager Extensions
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('dns_server_id')->nullable()->constrained('dns_servers')->nullOnDelete();
            $table->enum('status', ['active', 'disabled', 'suspended'])->default('active');
            $table->enum('sync_status', ['synced', 'pending', 'error'])->default('synced');
            $table->text('sync_error')->nullable();
            $table->timestamps();
        });

        // PowerDNS Records table
        Schema::create('records', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('domain_id')->nullable();
            $table->string('name', 255)->nullable();
            $table->string('type', 10)->nullable();
            $table->text('content')->nullable();
            $table->integer('ttl')->default(3600);
            $table->integer('prio')->nullable();
            $table->boolean('disabled')->default(false);
            $table->string('ordername', 255)->nullable();
            $table->boolean('auth')->default(true);
            $table->timestamps();

            $table->foreign('domain_id')->references('id')->on('domains')->cascadeOnDelete();
            $table->index(['name', 'type']);
            $table->index('domain_id');
            $table->index('ordername');
        });

        // PowerDNS Comments table
        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('domain_id');
            $table->string('name', 255);
            $table->string('type', 10);
            $table->integer('modified_at');
            $table->string('account', 40)->nullable();
            $table->text('comment');

            $table->foreign('domain_id')->references('id')->on('domains')->cascadeOnDelete();
            $table->index(['name', 'type']);
            $table->index(['domain_id', 'modified_at']);
        });

        // PowerDNS Domain Metadata table
        Schema::create('domainmetadata', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('domain_id');
            $table->string('kind', 32);
            $table->text('content')->nullable();

            $table->foreign('domain_id')->references('id')->on('domains')->cascadeOnDelete();
            $table->index(['domain_id', 'kind']);
        });

        // PowerDNS Crypto Keys (DNSSEC) table
        Schema::create('cryptokeys', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('domain_id');
            $table->integer('flags');
            $table->boolean('active')->default(true);
            $table->boolean('published')->default(true);
            $table->text('content')->nullable();

            $table->foreign('domain_id')->references('id')->on('domains')->cascadeOnDelete();
            $table->index('domain_id');
        });

        // PowerDNS TSIG Keys table
        Schema::create('tsigkeys', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('algorithm', 50);
            $table->string('secret', 255);

            $table->unique(['name', 'algorithm']);
        });

        // PowerDNS Supermasters table
        Schema::create('supermasters', function (Blueprint $table) {
            $table->string('ip', 64);
            $table->string('nameserver', 255);
            $table->string('account', 40)->nullable();

            $table->primary(['ip', 'nameserver']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supermasters');
        Schema::dropIfExists('tsigkeys');
        Schema::dropIfExists('cryptokeys');
        Schema::dropIfExists('domainmetadata');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('records');
        Schema::dropIfExists('domains');
    }
};
