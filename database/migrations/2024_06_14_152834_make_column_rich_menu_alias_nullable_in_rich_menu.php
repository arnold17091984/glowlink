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
        Schema::table('rich_menus', function (Blueprint $table) {
            //
            $table->string('rich_menu_alias')->nullable()->change();
            $table->string('tab_no')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rich_menus', function (Blueprint $table) {
            //
            $table->string('rich_menu_alias')->nullable(false)->change();
            $table->bigInteger('tab_no')->change();
        });
    }
};
