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
        Schema::table('referrals', function (Blueprint $table) {
            $table->float('referral_acceptance_points')->default(0)->after('awarded_points');

            $table->renameColumn('awarded_points', 'referrer_awarded_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropColumn('referral_acceptance_points');

            $table->renameColumn('referrer_awarded_points', 'awarded_points');
        });
    }
};
