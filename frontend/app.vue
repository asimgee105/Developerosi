<template>
  <div>
    <NuxtPage />
    <CommandPalette />
  </div>
</template>

<style>
/* Global resets and typography */
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');

html, body {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Outfit', sans-serif;
  background-color: #09090b; /* Zinc 950 */
  color: #fafafa;
  overflow-x: hidden;
  transition: background-color 0.3s ease, color 0.3s ease;
}

*, *::before, *::after {
  box-sizing: inherit;
}
</style>
