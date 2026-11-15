<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** F5 – community votes on other citizens' reports. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // affected | still_there | fixed
            $table->timestamps();
            $table->unique(['report_id', 'user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_votes');
    }
};
