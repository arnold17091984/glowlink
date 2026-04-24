import preset from './vendor/filament/support/tailwind.config.preset'

/**
 * glowlink design system tokens — "紙と墨と和茶"
 *
 * - brand:   LINE inspired green (existing #21D59B) extended to a full 50-950 scale
 * - ink:     warm near-black, slight charcoal hue
 * - paper:   cream whites, reduce eye fatigue on long admin sessions
 * - sun:     warm amber for warnings / seasonal accents
 * - coral:   muted red, Japanese 朱 inspired
 * - surface: container backgrounds with warmer undertone than pure grey
 */
export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './resources/views/filament/resources/individual-talk-resource/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    50:  '#EEFCF6',
                    100: '#D3F8E8',
                    200: '#A4F0D2',
                    300: '#6DE4B7',
                    400: '#3FDAA4',
                    500: '#21D59B',
                    600: '#15B584',
                    700: '#0E8F6A',
                    800: '#0B7055',
                    900: '#095C46',
                    950: '#04382B',
                },
                ink: {
                    50:  '#F4F3F1',
                    100: '#E3E1DC',
                    200: '#C6C1B8',
                    300: '#9A948A',
                    400: '#6B665D',
                    500: '#3B3834',
                    600: '#2A2826',
                    700: '#201F1D',
                    800: '#1C1B1A',
                    900: '#141312',
                    950: '#0A0A09',
                },
                paper: {
                    50:  '#FCFBF8',
                    100: '#FAF9F6',
                    200: '#F4F2EC',
                    300: '#EBE7DD',
                    400: '#DDD7C9',
                },
                sun:   '#D97706',
                coral: '#C2410C',
                /* Chat bubble tokens (replaces messages.blade inline styles) */
                'chat-incoming': '#DCF8C6',
                'chat-outgoing': '#CFE7FF',
            },
            fontFamily: {
                sans: [
                    '"Noto Sans JP"',
                    '"Inter"',
                    '-apple-system',
                    'BlinkMacSystemFont',
                    '"Hiragino Kaku Gothic ProN"',
                    '"Hiragino Sans"',
                    'Meiryo',
                    'sans-serif',
                ],
                display: [
                    '"Shippori Mincho B1"',
                    '"Hiragino Mincho ProN"',
                    '"Yu Mincho"',
                    'YuMincho',
                    'serif',
                ],
                mono: [
                    '"Geist Mono"',
                    'ui-monospace',
                    'SFMono-Regular',
                    'monospace',
                ],
            },
            fontSize: {
                'display-lg': ['3.5rem', { lineHeight: '1.05', letterSpacing: '-0.02em', fontWeight: '500' }],
                'display':    ['2.5rem', { lineHeight: '1.1',  letterSpacing: '-0.015em', fontWeight: '500' }],
                'hero':       ['1.875rem', { lineHeight: '1.25', letterSpacing: '-0.01em', fontWeight: '600' }],
            },
            boxShadow: {
                'paper':    '0 1px 2px rgba(28,27,26,0.04), 0 1px 3px rgba(28,27,26,0.06)',
                'card':     '0 1px 2px rgba(28,27,26,0.05), 0 4px 12px rgba(28,27,26,0.04)',
                'elevated': '0 2px 4px rgba(28,27,26,0.06), 0 12px 24px -8px rgba(28,27,26,0.08)',
                'ring-brand': '0 0 0 3px rgba(33,213,155,0.20)',
            },
            borderRadius: {
                'xl2': '1rem',
                'xl3': '1.25rem',
            },
            backgroundImage: {
                'grain': "url(\"data:image/svg+xml;utf8,<svg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/><feColorMatrix values='0 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 0 0.06 0'/></filter><rect width='100%25' height='100%25' filter='url(%23n)'/></svg>\")",
            },
            keyframes: {
                'fade-in-up': {
                    '0%':   { opacity: '0', transform: 'translateY(12px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'reveal': {
                    '0%':   { opacity: '0' },
                    '100%': { opacity: '1' },
                },
            },
            animation: {
                'fade-in-up': 'fade-in-up 0.5s cubic-bezier(0.25, 0.1, 0.25, 1) both',
                'reveal':     'reveal 0.8s ease-out both',
            },
        },
    },
}
