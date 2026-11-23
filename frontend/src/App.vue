<template>
  <router-view />
</template>

<script setup></script>

<style>
html,
body,
#app {
  margin: 0;
  padding: 0;
  width: 100%;
  height: 100%;
}

body {
  font-family: "Segoe UI", sans-serif;
  background: #f4f6f9;
}

ul.pagination {
  flex-wrap: wrap;
  row-gap: 6px;
}
</style>
