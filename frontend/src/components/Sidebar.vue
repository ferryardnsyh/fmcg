<template>
  <aside class="sidebar" :class="{ open: sidebarOpen }">
    <button class="sidebar-close" @click="closeSidebar" aria-label="Tutup menu">
      <i class="bi bi-x-lg"></i>
    </button>
    <div class="sidebar-brand">
      <div class="brand-accent"></div>
      <div class="logo">
        <img src="../assets/logo.png" alt="Logo" />
        <h5>CV. Putra Sumber Sari</h5>
        <div class="logo-divider"></div>
        <small>Warehouse Management System</small>
      </div>
    </div>

    <ul class="menu">
      <li>
        <router-link to="/dashboard">
          <i class="bi bi-house-door-fill"></i>
          <span>Dashboard</span>
        </router-link>
      </li>

      <li class="menu-group-header">MASTER DATA</li>

      <li>
        <router-link to="/produk">
          <i class="bi bi-box-seam-fill"></i>
          <span>Produk</span>
        </router-link>
      </li>

      <li>
        <router-link to="/kategori">
          <i class="bi bi-tags-fill"></i>
          <span>Kategori</span>
        </router-link>
      </li>

      <li>
        <router-link to="/supplier">
          <i class="bi bi-truck"></i>
          <span>Supplier</span>
        </router-link>
      </li>

      <li>
        <router-link to="/rak">
          <i class="bi bi-grid-3x3-gap-fill"></i>
          <span>Rak / Lokasi</span>
        </router-link>
      </li>

      <li class="menu-group-header">TRANSAKSI</li>

      <li>
        <router-link to="/barang-masuk">
          <i class="bi bi-arrow-down-circle"></i>
          <span>Barang Masuk</span>
        </router-link>
      </li>

      <li>
        <router-link to="/barang-keluar">
          <i class="bi bi-arrow-up-circle"></i>
          <span>Barang Keluar</span>
        </router-link>
      </li>

      <li class="menu-group-header">MANAJEMEN STOK</li>

      <li>
        <router-link to="/stok">
          <i class="bi bi-boxes"></i>
          <span>Manajemen Stok</span>
        </router-link>
      </li>

      <li>
        <router-link to="/batch">
          <i class="bi bi-layers"></i>
          <span>Batch</span>
        </router-link>
      </li>

      <li>
        <router-link to="/stock-opname">
          <i class="bi bi-clipboard-data"></i>
          <span>Stock Opname</span>
        </router-link>
      </li>

      <li>
        <router-link to="/stock-adjustment">
          <i class="bi bi-sliders"></i>
          <span>Stock Adjustment</span>
        </router-link>
      </li>

      <li>
        <router-link to="/monitoring-expired">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <span>Monitoring Expired</span>
        </router-link>
      </li>

      <li class="menu-group-header">LAPORAN</li>

      <li>
        <router-link to="/laporan">
          <i class="bi bi-bar-chart-fill"></i>
          <span>Laporan</span>
        </router-link>
      </li>

      <li class="menu-group-header">LAINNYA</li>

      <li>
        <router-link to="/notifications">
          <i class="bi bi-bell-fill"></i>
          <span>Notifikasi</span>
        </router-link>
      </li>

      <li v-if="isAdmin">
        <router-link to="/user">
          <i class="bi bi-people-fill"></i>
          <span>User Management</span>
        </router-link>
      </li>
    </ul>

    <div class="sidebar-footer">
      <small>Version 1.0</small>
    </div>
  </aside>
</template>

<script setup>
import { watch } from "vue";
import { useRoute } from "vue-router";
import { sidebarOpen, closeSidebar } from "../composables/useLayout";
import { isAdmin } from "../stores/auth";

const route = useRoute();

watch(
  () => route.fullPath,
  () => closeSidebar()
);
</script>

<style scoped>
.sidebar {
  width: 250px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 50%, #f1f5f9 100%);
  color: #334155;
  height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: fixed;
  left: 0;
  top: 0;
  z-index: 100;
  box-shadow: 2px 0 20px rgba(0, 0, 0, 0.06);
}

/* ===========================
          BRAND / LOGO
=========================== */

.sidebar-brand {
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  border-bottom: 1px solid #e8edf4;
}

.brand-accent {
  height: 4px;
  background: linear-gradient(90deg, #dc2626 0%, #dc2626 50%, #1e293b 50%, #1e293b 100%);
}

.logo {
  text-align: center;
  padding: 22px 20px 18px;
}

.logo img {
  width: 58px;
  margin-bottom: 10px;
}

.logo h5 {
  margin: 0 0 8px;
  font-weight: 700;
  font-size: 17px;
  color: #1e293b;
  letter-spacing: 0.3px;
}

.logo-divider {
  width: 30px;
  height: 2px;
  background: linear-gradient(90deg, #dc2626 0%, #dc2626 50%, #1e293b 50%, #1e293b 100%);
  margin: 0 auto 8px;
  border-radius: 2px;
}

.logo small {
  display: block;
  color: #94a3b8;
  font-size: 11px;
  font-weight: 500;
  font-style: italic;
}

/* ===========================
            MENU
=========================== */

.menu {
  list-style: none;
  padding: 8px 0;
  margin: 0;
  flex: 1;
  overflow-y: auto;
  scrollbar-width: none;
}

.menu::-webkit-scrollbar {
  display: none;
}

.menu li {
  margin-bottom: 1px;
}

.menu a {
  display: flex;
  align-items: center;
  gap: 12px;
  color: #475569;
  text-decoration: none;
  padding: 10px 22px;
  transition: all 0.15s ease;
  font-size: 14px;
  font-weight: 500;
  border-left: 3px solid transparent;
}

.menu a:hover {
  background: #f1f5f9;
  color: #dc2626;
}

.menu .router-link-active {
  background: #fef2f2;
  color: #dc2626;
  font-weight: 600;
  border-left-color: #dc2626;
}

.menu i {
  font-size: 18px;
  width: 24px;
  text-align: center;
}

.menu-group-header {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1.2px;
  color: #94a3b8;
  padding: 20px 22px 4px;
  cursor: default;
}

/* ===========================
          FOOTER
=========================== */

.sidebar-footer {
  padding: 16px;
  border-top: 1px solid #e8edf4;
  text-align: center;
}

.sidebar-footer small {
  color: #94a3b8;
  font-size: 12px;
}

.sidebar-close {
  display: none;
  position: absolute;
  top: 16px;
  right: 12px;
  border: none;
  background: #f1f5f9;
  color: #475569;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  font-size: 16px;
  cursor: pointer;
  z-index: 5;
}

@media (max-width: 991.98px) {
  .sidebar {
    transform: translateX(-100%);
    transition: transform 0.3s ease;
    z-index: 1100;
    box-shadow: none;
  }

  .sidebar.open {
    transform: translateX(0);
    box-shadow: 2px 0 20px rgba(0, 0, 0, 0.15);
  }

  .sidebar-close {
    display: flex;
    align-items: center;
    justify-content: center;
  }
}
</style>
