<?php

use App\Models\Coupon;
use App\Models\Friend;
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
        Schema::create('friend_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Friend::class);
            $table->foreignIdFor(Coupon::class);

            $table->string('status');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('friend_coupons');
    }
};
