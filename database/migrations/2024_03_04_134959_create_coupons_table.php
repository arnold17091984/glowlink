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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();
            $table->dateTime('from');
            $table->dateTime('till');
            $table->text('description');
            $table->string('amount_type');
            $table->bigInteger('amount');
            $table->boolean('is_lottery');
            $table->bigInteger('win_rate')->nullable();
            $table->boolean('is_limited')->nullable();
            $table->bigInteger('no_of_users')->nullable();
            $table->boolean('unlimited');
            $table->boolean('is_edit_coupon');
            $table->string('coupon_code')->unique();
            $table->string('coupon_type');
            $table->string('required_points');
            $table->boolean('is_active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
