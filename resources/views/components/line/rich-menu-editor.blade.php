{{--
    LINE Rich Menu WYSIWYG エディタ プロトタイプ。

    使い方:
      <x-line.rich-menu-editor
          :image-url="$record?->getFirstMediaUrl('rich_menus')"
          :layout-no="$record->selected_layout"
          :actions="$record->actions"
      />

    引数:
      - imageUrl  : アップロード済みリッチメニュー画像の URL (2500x1686 または 2500x843 想定)
      - layoutNo  : 1..7 (LINE 標準レイアウト番号)
      - actions   : [['label' => 'A', 'type' => 'uri|message|postback', 'data' => '...'], ...]

    特徴:
      - CSS Grid の grid-template-areas を layoutNo に応じて切り替え
      - 各セルは絶対位置の div で背景画像の上にオーバーレイ
      - クリックすると Livewire の setSelectedArea() を呼び、Repeater フォームがフォーカスされる
      - レイヤーハイライト・アルファベットラベル (A,B,C...) を表示
      - ダークモード対応 (border/color は CSS 変数)
--}}

@props([
    'imageUrl' => null,
    'layoutNo' => 1,
    'actions' => [],
    'aspectRatio' => '2500 / 1686',
])

@php
    // LINE 公式 7 レイアウトの grid-template-areas 定義
    // 座標は LINE Rich Menu のピクセル仕様 (2500x1686 または 2500x843) に対応
    $layouts = [
        1 => ['rows' => 2, 'cols' => 3, 'areas' => "'A B C' 'D E F'", 'cells' => 6, 'ratio' => '2500 / 1686'],
        2 => ['rows' => 2, 'cols' => 2, 'areas' => "'A B' 'C D'",     'cells' => 4, 'ratio' => '2500 / 1686'],
        3 => ['rows' => 1, 'cols' => 3, 'areas' => "'A B C'",         'cells' => 3, 'ratio' => '2500 / 1686'],
        4 => ['rows' => 1, 'cols' => 2, 'areas' => "'A B'",           'cells' => 2, 'ratio' => '2500 / 1686'],
        5 => ['rows' => 1, 'cols' => 1, 'areas' => "'A'",             'cells' => 1, 'ratio' => '2500 / 1686'],
        6 => ['rows' => 1, 'cols' => 3, 'areas' => "'A B C'",         'cells' => 3, 'ratio' => '2500 / 843'],
        7 => ['rows' => 1, 'cols' => 2, 'areas' => "'A B'",           'cells' => 2, 'ratio' => '2500 / 843'],
    ];

    $layout = $layouts[$layoutNo] ?? $layouts[1];
    $cellLabels = ['A', 'B', 'C', 'D', 'E', 'F'];
@endphp

<div
    x-data="{
        selectedCell: null,
        selectCell(index) {
            this.selectedCell = index;
            this.$dispatch('rich-menu-cell-selected', { index });
        }
    }"
    class="rich-menu-editor"
    style="
        display: grid;
        gap: 0.5rem;
        grid-template-columns: 2fr 1fr;
        width: 100%;
    "
>
    {{-- 左: プレビューキャンバス --}}
    <div style="position: relative; width: 100%; max-width: 720px; aspect-ratio: {{ $layout['ratio'] }}; background: var(--gray-100, #f3f4f6); border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.1);">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="Rich Menu"
                 style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover;">
        @else
            <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: var(--gray-500, #6b7280); font-size: 0.875rem;">
                画像をアップロードするとプレビューが表示されます
            </div>
        @endif

        {{-- タップ領域オーバーレイ --}}
        <div style="position: absolute; inset: 0; display: grid;
                    grid-template-rows: repeat({{ $layout['rows'] }}, 1fr);
                    grid-template-columns: repeat({{ $layout['cols'] }}, 1fr);
                    grid-template-areas: {{ $layout['areas'] }};
                    gap: 2px; padding: 2px;">
            @for ($i = 0; $i < $layout['cells']; $i++)
                @php $label = $cellLabels[$i]; @endphp
                <button type="button"
                        x-on:click="selectCell({{ $i }})"
                        x-bind:class="selectedCell === {{ $i }} ? 'rm-cell-active' : 'rm-cell'"
                        style="grid-area: {{ $label }};
                               background: rgba(33, 213, 155, 0.08);
                               border: 2px dashed rgba(33, 213, 155, 0.6);
                               color: white;
                               font-weight: 700;
                               font-size: 2rem;
                               text-shadow: 0 2px 4px rgba(0,0,0,.5);
                               cursor: pointer;
                               transition: all .15s ease;
                               display: flex; align-items: center; justify-content: center;">
                    {{ $label }}
                </button>
            @endfor
        </div>
    </div>

    {{-- 右: 選択セルのアクション編集フォーム --}}
    <div style="padding: 1rem; border: 1px solid var(--gray-200, #e5e7eb); border-radius: 12px; background: var(--white, #fff);">
        <h3 style="font-weight: 600; font-size: 0.95rem; margin-bottom: .75rem;">タップ領域の設定</h3>

        <template x-if="selectedCell === null">
            <p style="color: var(--gray-500, #6b7280); font-size: 0.875rem;">
                左のプレビューでタップ領域 (A〜{{ end($cellLabels) }}) を選択すると、ここにアクション設定が表示されます。
            </p>
        </template>

        <template x-if="selectedCell !== null">
            <div>
                <p style="font-size: 0.875rem; margin-bottom: .5rem;">
                    選択中の領域: <strong x-text="'ABCDEF'[selectedCell]"></strong>
                </p>
                <p style="color: var(--gray-500, #6b7280); font-size: 0.75rem; margin-bottom: .75rem;">
                    この領域に対応する Repeater のアクション項目を下の詳細フォームで編集してください。
                </p>

                {{-- 実運用ではここに Filament Forms の action_type / action_label / action_data を $wire.$entangle で双方向バインド。
                     プロトタイプでは既存の actions 配列からサマリーを表示する。 --}}
                @if (!empty($actions))
                    <ul style="font-size: 0.875rem; list-style: none; padding: 0; margin: 0;">
                        @foreach ($actions as $i => $action)
                            <li x-show="selectedCell === {{ $i }}"
                                style="padding: .5rem; background: var(--gray-50, #f9fafb); border-radius: 6px; margin-bottom: .25rem;">
                                <div><strong>種別:</strong> {{ $action['type'] ?? '—' }}</div>
                                <div><strong>内容:</strong> {{ $action['data'] ?? '—' }}</div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </template>
    </div>
</div>

<style>
    .rm-cell:hover {
        background: rgba(33, 213, 155, 0.2) !important;
    }
    .rm-cell-active {
        background: rgba(33, 213, 155, 0.35) !important;
        border-style: solid !important;
    }
    @media (prefers-color-scheme: dark) {
        .rich-menu-editor > div:last-child {
            background: #1f2937 !important;
            color: #f3f4f6 !important;
        }
    }
</style>
