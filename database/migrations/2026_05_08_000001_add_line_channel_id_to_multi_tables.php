<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-tenancy 化のため、各種モデルへ line_channel_id を追加。
 *
 *   - rich_menu_sets: どのチャネル向けの Rich Menu かを明示
 *   - friends:        どのチャネル経由で friends になったか (Webhook 受信時に記録)
 *   - auto_responses: どのチャネル限定の自動返信か (NULL = 全チャネル共通)
 *   - broadcasts:     どのチャネルへ配信するか
 *   - scenario_deliveries: 同上
 *
 * すべて nullable + onDelete('set null') にして既存データは default channel
 * 解決にフォールバック可能にする。
 */
return new class extends Migration
{
    private array $targets = [
        'rich_menu_sets',
        'friends',
        'auto_responses',
        'broadcasts',
        'scenario_deliveries',
    ];

    public function up(): void
    {
        foreach ($this->targets as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (Schema::hasColumn($table, 'line_channel_id')) {
                    return;
                }
                $t->foreignId('line_channel_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('line_channels')
                    ->nullOnDelete();
                $t->index('line_channel_id', 'idx_'.$table.'_channel');
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->targets) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'line_channel_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropForeign([$table.'_line_channel_id_foreign']);
                $t->dropIndex('idx_'.$table.'_channel');
                $t->dropColumn('line_channel_id');
            });
        }
    }
};
