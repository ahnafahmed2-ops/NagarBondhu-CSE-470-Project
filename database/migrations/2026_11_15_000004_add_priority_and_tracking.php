<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** F4 (tracking code) and F7 (priority) columns. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedTinyInteger('severity')->default(3); // 1 (minor) – 5 (dangerous)
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->string('tracking_code', 20)->nullable()->unique()->after('id');
            $table->unsignedTinyInteger('priority_score')->default(0)->after('status');
            $table->string('priority_level', 10)->default('Low')->index()->after('priority_score');
            $table->boolean('priority_overridden')->default(false)->after('priority_level');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropUnique(['tracking_code']);
            $table->dropIndex(['priority_level']);
            $table->dropColumn(['tracking_code', 'priority_score', 'priority_level', 'priority_overridden']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('severity');
        });
    }
};
