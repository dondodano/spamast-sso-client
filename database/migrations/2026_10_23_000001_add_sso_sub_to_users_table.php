<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The SPAMAST SSO hub's permanent id for the person ("sub"), remembered the
 * first time they arrive from the hub. After that the account is found by it
 * alone, so a changed email on either side cannot point a hub user at the
 * wrong PGSMS account.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'sso_sub')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('sso_sub')->nullable()->unique()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['sso_sub']);
            $table->dropColumn('sso_sub');
        });
    }
};
