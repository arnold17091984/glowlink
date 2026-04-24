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
        Schema::create('rich_actions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->foreignId('layout_id')->constrained('layouts');
            $table->string('type');
            $table->string('label')->nullable();
            $table->string('link')->nullable();
            $table->string('text')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rich_actions');
    }
};
