<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('usuario') || Schema::hasColumn('usuario', 'remember_token')) {
            return;
        }

        Schema::table('usuario', function (Blueprint $table) {
            $table->string('remember_token', 100)->nullable()->after('passwordhash');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('usuario') || ! Schema::hasColumn('usuario', 'remember_token')) {
            return;
        }

        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn('remember_token');
        });
    }
};
