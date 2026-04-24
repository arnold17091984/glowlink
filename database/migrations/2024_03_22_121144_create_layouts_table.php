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
        Schema::create('layouts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('rich_id');
            $table->string('rich_type');
            $table->float('x')->nullable();
            $table->float('y')->nullable();
            $table->string('width')->nullable();
            $table->string('height')->nullable();
            $table->string('offsetTop')->nullable();
            $table->string('offsetBottom')->nullable();
            $table->string('offsetStart')->nullable();
            $table->string('offsetEnd')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layout');
    }
};
