/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
            },
            screens: {
                xs: '480px',
            },
            colors: {
                backgroundPrincipal: 'var(--bg-principal)',
                textPrincipal: 'var(--text-principal)',
                brandColor: 'var(--color-brand)',
                surfaceColor: 'var(--color-surface)',
                'fb-border': 'var(--color-border)',
                'fb-muted': 'var(--color-muted)',
                'fb-link': 'var(--color-link)',
                primary: {
                    500: '#0891b2',
                    600: '#0db8de',
                },
            },
        },
    },
    plugins: [],
};
