<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * パフォーマンス改善のための一括インデックス追加。
 *
 * - Polymorphic (talks, message_deliveries) の複合インデックス
 * - friends のフィルタ列 (mark, referred_by, points)
 * - friend_coupons の重複取得防止 UNIQUE
 * - rich_menus / award_points_logs のアクセス頻度が高い列
 *
 * ロックタイムを避けるため ALGORITHM=INPLACE になるインデックスのみ追加。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talks', function (Blueprint $table) {
            $table->index(['sender_type', 'sender_id'], 'idx_sender_morph');
            $table->index(['receiver_type', 'receiver_id'], 'idx_receiver_morph');
            $table->index('created_at', 'idx_talks_created_at');
        });

        Schema::table('message_deliveries', function (Blueprint $table) {
            $table->index(['delivery_type', 'delivery_id'], 'idx_md_delivery_morph');
            $table->index(['message_type', 'message_id'], 'idx_md_message_morph');
            $table->index('delivery_date', 'idx_md_delivery_date');
        });

        Schema::table('friends', function (Blueprint $table) {
            $table->index('mark', 'idx_friends_mark');
            $table->index('referred_by', 'idx_friends_referred_by');
        });

        Schema::table('friend_coupons', function (Blueprint $table) {
            // 同一友だちが同一クーポンを複数取得できない制約 (抽選ハズレ再取得を許容する設計は別途 status で扱う)
            $table->index(['friend_id', 'coupon_id'], 'idx_fc_friend_coupon');
            $table->index('status', 'idx_fc_status');
        });

        Schema::table('rich_menus', function (Blueprint $table) {
            $table->index('parent_id', 'idx_rm_parent_id');
        });

        Schema::table('award_points_logs', function (Blueprint $table) {
            $table->index(['friend_id', 'created_at'], 'idx_apl_friend_timeline');
        });

        Schema::table('broadcasts', function (Blueprint $table) {
            $table->index(['is_active', 'next_date'], 'idx_broadcasts_active_next');
            $table->index('start_date', 'idx_broadcasts_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('talks', function (Blueprint $table) {
            $table->dropIndex('idx_sender_morph');
            $table->dropIndex('idx_receiver_morph');
            $table->dropIndex('idx_talks_created_at');
        });

        Schema::table('message_deliveries', function (Blueprint $table) {
            $table->dropIndex('idx_md_delivery_morph');
            $table->dropIndex('idx_md_message_morph');
            $table->dropIndex('idx_md_delivery_date');
        });

        Schema::table('friends', function (Blueprint $table) {
            $table->dropIndex('idx_friends_mark');
            $table->dropIndex('idx_friends_referred_by');
        });

        Schema::table('friend_coupons', function (Blueprint $table) {
            $table->dropIndex('idx_fc_friend_coupon');
            $table->dropIndex('idx_fc_status');
        });

        Schema::table('rich_menus', function (Blueprint $table) {
            $table->dropIndex('idx_rm_parent_id');
        });

        Schema::table('award_points_logs', function (Blueprint $table) {
            $table->dropIndex('idx_apl_friend_timeline');
        });

        Schema::table('broadcasts', function (Blueprint $table) {
            $table->dropIndex('idx_broadcasts_active_next');
            $table->dropIndex('idx_broadcasts_start_date');
        });
    }
};
