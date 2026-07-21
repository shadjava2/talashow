<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'admin_login_failures')) {
                $table->unsignedSmallInteger('admin_login_failures')->default(0);
            }
            if (! Schema::hasColumn('users', 'admin_locked_until')) {
                $table->timestamp('admin_locked_until')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'admin_locked_until')) {
                $table->dropIndex(['admin_locked_until']);
                $table->dropColumn('admin_locked_until');
            }
            if (Schema::hasColumn('users', 'admin_login_failures')) {
                $table->dropColumn('admin_login_failures');
            }
        });
    }
};
