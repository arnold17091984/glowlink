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
        //
        Schema::table('rich_actions', function (Blueprint $table) {
            $table->dropColumn(['coupon_id', 'referral_id', 'auto_response_id']);

            $table->unsignedBigInteger('model_id')->nullable()->after('id');
            $table->string('model_type')->nullable()->after('model_id');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rich_actions', function (Blueprint $table) {
            $table->dropColumn(['model_id', 'model_type']);

            $table->unsignedBigInteger('coupon_id')->after('layout_id');
            $table->unsignedBigInteger('referral_id')->after('coupon_id');
            $table->unsignedBigInteger('auto_response_id')->after('referral_id');

        });
    }
};
