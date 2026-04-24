<?php

use App\Models\RichMenuSet;
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
        Schema::create('rich_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(RichMenuSet::class);

            $table->string('reference')->unique()->nullable();
            $table->bigInteger('tab_no');
            $table->bigInteger('selected_layout');
            $table->string('name');
            $table->string('rich_menu_alias');
            $table->boolean('selected')->default(false);
            $table->string('chatbar_text')->default('Menu');
            $table->string('width')->default('1280');
            $table->string('height')->default('863');
            $table->json('areas');
            $table->json('actions');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rich_menus');
    }
};
