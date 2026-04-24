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
        Schema::table('rich_menu_sets', function (Blueprint $table) {
            //
            $table->string('reference')->after('id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rich_menu_sets', function (Blueprint $table) {
            //
            $table->dropColumn('reference');
        });
    }
};
