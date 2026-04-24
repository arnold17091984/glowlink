<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * line_channels — 複数の LINE 公式アカウントを管理画面から登録するためのテーブル。
 *
 * 各チャネルには:
 *   - slug (URL-safe) が発行され、Webhook URL は https://{domain}/messages/{slug}
 *   - channel_secret / channel_access_token は Laravel の encrypted cast で保管
 *   - is_default = true のチャネルが旧 /messages ルートの送信先
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('line_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);                      // 公式アカウント表示名
            $table->string('slug', 80)->unique();             // Webhook URL identifier
            $table->string('basic_id', 40)->nullable();       // @xxxx のベーシック ID
            $table->string('channel_id', 50);                 // LINE Channel ID
            $table->text('channel_secret');                   // encrypted
            $table->text('channel_access_token');             // encrypted (long-lived JWT)
            $table->string('liff_id', 100)->nullable();       // 紐づく LIFF ID (クーポンウォレット等)
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_connected_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index(['is_default', 'is_active'], 'idx_default_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_channels');
    }
};
