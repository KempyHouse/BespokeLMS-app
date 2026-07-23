/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/views/**/*.blade.php',
    './resources/views/platform/**/*.blade.php',
    './resources/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        teachhq: 'var(--color-teachhq, #0084ff)',
        slatecard: 'var(--color-slatecard, #1a202c)',
        paper: 'var(--color-paper, #f5f5f5)',
      },
      borderRadius: {
        control: 'var(--radius-control, 0.375rem)',
      },
    },
  },
  plugins: [],
}
