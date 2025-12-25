<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('user')->after('password');
            }

            if (!Schema::hasColumn('users', 'is_subscribed')) {
                $table->boolean('is_subscribed')->default(false)->after('subscription_plan');
            }

            if (!Schema::hasColumn('users', 'subscribed_until')) {
                $table->timestamp('subscribed_until')->nullable()->after('is_subscribed');
            }

            if (!Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->after('email');
            }

            if (!Schema::hasColumn('users', 'google_avatar')) {
                $table->string('google_avatar')->nullable()->after('google_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [];
            foreach (['role', 'is_subscribed', 'subscribed_until', 'google_id', 'google_avatar'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $columns[] = $column;
                }
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
