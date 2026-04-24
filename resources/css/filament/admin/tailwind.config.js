import preset from '../../../../vendor/filament/support/tailwind.config.preset'
import rootConfig from '../../../../tailwind.config.js'

/**
 * Filament admin 専用の Tailwind 設定。
 * ルートの tailwind.config.js と同じデザイントークンを共有し、
 * Filament プリセットに重ねて Filament 内でもブランドユーティリティが使えるようにする。
 */
export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './resources/views/filament/resources/individual-talk-resource/**/*.blade.php',
    ],
    theme: rootConfig.theme,
}
