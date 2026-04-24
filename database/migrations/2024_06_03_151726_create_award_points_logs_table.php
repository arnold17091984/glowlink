<?php

use App\Models\Friend;
use App\Models\Referral;
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
        Schema::create('award_points_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Friend::class);
            $table->foreignIdFor(Referral::class)->nullable();

            $table->float('awarded_points');
            $table->string('type');
            $table->text('reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('award_points_logs');
    }
};
