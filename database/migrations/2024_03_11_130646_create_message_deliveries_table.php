<?php

use App\Models\Message;
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
        Schema::create('message_deliveries', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('message_id');
            $table->string('message_type')->default(Message::class);
            $table->unsignedBigInteger('delivery_id');
            $table->string('delivery_type');
            $table->dateTime('delivery_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_deliveries');
    }
};
