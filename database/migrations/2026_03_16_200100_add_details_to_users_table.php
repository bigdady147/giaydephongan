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
            $table->string('username')->unique()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->string('avatar')->nullable()->after('address');
            $table->enum('role', ['admin', 'staff', 'customer'])->default('customer')->after('password');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('role');
            $table->date('birthday')->nullable()->after('status');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('birthday');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'phone',
                'address',
                'avatar',
                'role',
                'status',
                'birthday',
                'gender'
            ]);
        });
    }
};
