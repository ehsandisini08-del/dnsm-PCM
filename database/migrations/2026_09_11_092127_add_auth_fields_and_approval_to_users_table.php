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
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->after('customer_id');
            $table->string('otp_code')->nullable()->after('google_id');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('otp_expires_at');
            $table->timestamp('approved_at')->nullable()->after('approval_status');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'google_id',
                'otp_code',
                'otp_expires_at',
                'approval_status',
                'approved_at',
                'approved_by',
            ]);
        });
    }
};
