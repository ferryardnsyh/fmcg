<template>
  <div class="layout">
    <div class="no-print">
      <Sidebar />
    </div>

    <div v-if="sidebarOpen" class="sidebar-overlay" @click="closeSidebar"></div>

    <!-- Content -->
    <div class="main-content">
      <div class="no-print">
        <Navbar :title="pageTitle" />
      </div>

      <main class="content">
        <router-view />
      </main>

      <div class="no-print">
        <Footer />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { useRoute } from "vue-router";
import Sidebar from "../components/Sidebar.vue";
import Navbar from "../components/Navbar.vue";
import Footer from "../components/Footer.vue";
import { sidebarOpen, closeSidebar } from "../composables/useLayout";

const route = useRoute();

const pageTitles = {
  "/dashboard": "Dashboard",
  "/produk": "Produk",
  "/kategori": "Kategori",
  "/batch": "Batch",
  "/rak": "Rak / Lokasi",
  "/supplier": "Supplier",
  "/barang-masuk": "Barang Masuk",
  "/barang-keluar": "Barang Keluar",
  "/stok": "Manajemen Stok",
  "/stock-opname": "Stock Opname",
  "/stock-adjustment": "Stock Adjustment",
  "/monitoring-expired": "Monitoring Expired",
  "/laporan": "Laporan",
  "/user": "User Management",
};

const pageTitle = computed(() => {
  return pageTitles[route.path] || "Dashboard";
});
</script>

<style scoped>
.layout {
  display: flex;
  background: linear-gradient(135deg, #f8fafc 0%, #eef2f6 100%);
  min-height: 100vh;
}

.main-content {
  margin-left: 250px;
  width: calc(100% - 250px);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.content {
  flex: 1;
  padding: 25px;
}

.sidebar-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  z-index: 1050;
}

@media (max-width: 991.98px) {
  .main-content {
    margin-left: 0;
    width: 100%;
  }

  .content {
    padding: 16px;
  }

  .sidebar-overlay {
    display: block;
  }
}

@media print {
  .no-print {
    display: none !important;
  }

  .main-content {
    margin-left: 0 !important;
    width: 100% !important;
  }
}
</style>
