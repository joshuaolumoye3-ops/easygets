/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      fontFamily: {
        'inter': ['Inter', 'sans-serif'],
      },
      colors: {
        'brand-green': {
          'DEFAULT': '#03624C',
          'light': '#E6F5F1',
          'medium': '#157A61',
        },
        'brand-gray': {
          'light': '#F9FAFB',
          'medium': '#E0E0E0',
          'dark': '#4F4F4F'
        }
      }
    },
  },
  plugins: [],
}