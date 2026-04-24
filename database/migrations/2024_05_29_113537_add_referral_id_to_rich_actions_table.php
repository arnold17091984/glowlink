<?php

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
        Schema::table('rich_actions', function (Blueprint $table) {
            //
            $table->foreignIdFor(Referral::class)->after('auto_response_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rich_actions', function (Blueprint $table) {
            //
            $table->dropColumn('referral_id');
        });
    }
};
