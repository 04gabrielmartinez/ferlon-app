import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './app/Views/**/*.php',
    './public/**/*.php',
    './resources/js/**/*.js',
  ],
  theme: {
    extend: {},
  },
  plugins: [forms],
};
