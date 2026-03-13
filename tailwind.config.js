/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.{php,html,js}",
    "./includes/**/*.{php,html,js}",
    "./assets/**/*.{php,html,js}"
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          light: '#fed7aa', // orange-200
          DEFAULT: '#f97316', // orange-500
          dark: '#c2410c', // orange-700
        }
      }
    },
  },
  plugins: [],
}
