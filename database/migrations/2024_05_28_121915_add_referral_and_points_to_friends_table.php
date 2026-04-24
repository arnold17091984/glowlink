<?php

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
        Schema::table('friends', function (Blueprint $table) {
            //
            $table->foreignIdFor(Friend::class, 'referred_by')->nullable()->after('mark');
            $table->bigInteger('referral_count')->default(0)->after('referred_by');
            $table->float('points')->default(0)->after('referral_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('friends', function (Blueprint $table) {
            //
            $table->dropColumn(['referred_by', 'referral_count', 'points']);
        });
    }
};
