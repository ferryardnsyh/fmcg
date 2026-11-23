<template>
  <footer class="app-footer">
    <div class="footer-right">
      <span class="footer-copyright">
        &copy; {{ currentYear }} <strong>CV. Putra Sumber Sari</strong>
      </span>
    </div>
  </footer>
</template>

<script setup>
const currentYear = new Date().getFullYear();
</script>

<style scoped>
.app-footer {
  height: 52px;

  background: #ffffff;

  border-top: 1px solid #e2e8f0;

  display: flex;

  justify-content: space-between;

  align-items: center;

  padding: 0 24px;

  box-shadow: 0 -2px 8px rgba(30, 41, 59, 0.03);
}

.footer-right {
  display: flex;

  align-items: center;

  gap: 16px;
}

.footer-copyright {
  font-size: 11px;

  color: #64748b;
}

.footer-copyright strong {
  color: #334155;

  font-weight: 600;
}
</style>
