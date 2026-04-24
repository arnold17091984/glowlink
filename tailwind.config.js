import preset from './vendor/filament/support/tailwind.config.preset'

/**
 * glowlink // Mission Control design tokens
 *
 * Dark-first command center aesthetic.
 *   - carbon: deep graphite surfaces (base, elevated, floating)
 *   - rule:   hairline borders (1px white at low opacity)
 *   - signal: brand green reserved for live/active states only
 *   - pulse:  high-contrast alert / warning
 *   - data:   muted tones for secondary text / grid rules
 */
export default {
    presets: [preset],
    darkMode: 'class',
    content: [
        './app/Filament/**/*.php',
        './resources/views/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './resources/views/filament/resources/individual-talk-resource/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                /* Surfaces — "carbon" inspired by spacecraft interiors */
                carbon: {
                    950: '#07080A',
                    900: '#0A0B0D',
                    850: '#0E0F12',
                    800: '#111214',
                    750: '#14151A',
                    700: '#17181D',
                    600: '#1D1F25',
                    500: '#24262D',
                    400: '#2E3038',
                    300: '#3A3D46',
                    200: '#51555F',
                    100: '#7A7E89',
                    50:  '#A8ACB4',
                },
                /* Foreground — neutral ink for dark surfaces */
                ink: {
                    950: '#FAFAFB',
                    900: '#EDEEF0',
                    800: '#D4D6D9',
                    700: '#B4B6BB',
                    600: '#8B8E95',
                    500: '#6C6F76',
                    400: '#52555C',
                    300: '#3A3D44',
                    200: '#262830',
                },
                /* Brand signal — used sparingly for live/active only */
                signal: {
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
                    glow: 'rgba(33, 213, 155, 0.45)',
                },
                pulse: {
                    red:    '#FF3B30',
                    amber:  '#FFD60A',
                    cyan:   '#32ADE6',
                    violet: '#BF5AF2',
                },
                /* Chat tokens (backward compat) */
                'chat-incoming': '#1C2B1C',
                'chat-outgoing': '#0F2236',
            },
            fontFamily: {
                sans: [
                    '"IBM Plex Sans JP"',
                    '"IBM Plex Sans"',
                    '"Geist Variable"',
                    '-apple-system',
                    'BlinkMacSystemFont',
                    'sans-serif',
                ],
                display: [
                    '"Geist Variable"',
                    '"IBM Plex Sans"',
                    '-apple-system',
                    'sans-serif',
                ],
                mono: [
                    '"IBM Plex Mono"',
                    '"JetBrains Mono"',
                    'ui-monospace',
                    'SFMono-Regular',
                    'monospace',
                ],
            },
            fontSize: {
                'hud-xs':  ['0.65rem',  { lineHeight: '1',    letterSpacing: '0.14em', fontWeight: '500' }],
                'hud-sm':  ['0.7rem',   { lineHeight: '1',    letterSpacing: '0.12em', fontWeight: '500' }],
                'hud':     ['0.8rem',   { lineHeight: '1.1',  letterSpacing: '0.1em',  fontWeight: '500' }],
                'metric':  ['2.25rem',  { lineHeight: '1',    letterSpacing: '-0.02em', fontWeight: '500' }],
                'metric-lg': ['3.5rem', { lineHeight: '0.95', letterSpacing: '-0.035em', fontWeight: '500' }],
            },
            boxShadow: {
                'hairline':    'inset 0 0 0 1px rgba(255,255,255,0.06)',
                'hairline-md': 'inset 0 0 0 1px rgba(255,255,255,0.09)',
                'glow-signal': '0 0 0 1px rgba(33,213,155,0.35), 0 0 24px -4px rgba(33,213,155,0.45)',
                'glow-pulse':  '0 0 18px 0 rgba(255, 59, 48, 0.35)',
                'elevated':    '0 1px 0 rgba(255,255,255,0.04) inset, 0 0 0 1px rgba(255,255,255,0.06), 0 12px 40px -12px rgba(0,0,0,0.6)',
            },
            borderRadius: {
                'none': '0',
                'xs':   '2px',
                'sm':   '3px',
                'DEFAULT': '4px',
                'md':   '6px',
                'lg':   '8px',
                'xl':   '10px',
            },
            backgroundImage: {
                'grid': "linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px)",
                'scan': "repeating-linear-gradient(0deg, rgba(255,255,255,0.015) 0 1px, transparent 1px 4px)",
                'hud-gradient': "linear-gradient(135deg, rgba(33,213,155,0.06) 0%, transparent 50%)",
            },
            backgroundSize: {
                'grid': '40px 40px',
            },
            keyframes: {
                'pulse-signal': {
                    '0%, 100%': { opacity: '1',   transform: 'scale(1)' },
                    '50%':      { opacity: '0.55', transform: 'scale(0.9)' },
                },
                'blink': {
                    '0%, 45%': { opacity: '1' },
                    '55%, 100%': { opacity: '0' },
                },
                'fade-in-up': {
                    '0%':   { opacity: '0', transform: 'translateY(8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'count-in': {
                    '0%':   { opacity: '0',  transform: 'translateY(4px)' },
                    '100%': { opacity: '1',  transform: 'translateY(0)' },
                },
                'scan-line': {
                    '0%':   { transform: 'translateY(-100%)' },
                    '100%': { transform: 'translateY(100%)' },
                },
            },
            animation: {
                'pulse-signal': 'pulse-signal 1.8s cubic-bezier(0.4,0,0.6,1) infinite',
                'blink':        'blink 1s steps(2, end) infinite',
                'fade-in-up':   'fade-in-up 0.4s cubic-bezier(0.2, 0, 0, 1) both',
                'count-in':     'count-in 0.5s cubic-bezier(0.2, 0, 0, 1) both',
                'scan-line':    'scan-line 8s linear infinite',
            },
        },
    },
}
