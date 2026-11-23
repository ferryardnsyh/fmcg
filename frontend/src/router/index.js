import { createRouter, createWebHistory } from "vue-router";

import Login from "../pages/Auth/Login.vue";

import AdminLayout from "../layouts/AdminLayout.vue";

import Dashboard from "../pages/Dashboard/Dashboard.vue";

import Produk from "../pages/Produk/Produk.vue";
import { authUser, can } from "../stores/auth";

const routes = [
  {
    path: "/login",
    component: Login,
  },

  {
    path: "/",
    component: AdminLayout,
    children: [
      {
        path: "",
        redirect: "/dashboard",
      },
      {
        path: "dashboard",
        component: Dashboard,
      },
      {
        path: "produk",
        component: Produk,
      },
      {
        path: "kategori",
        component: () => import("../pages/Kategori/Kategori.vue"),
      },
      {
        path: "supplier",
        component: () => import("../pages/Supplier/Supplier.vue"),
      },
      {
        path: "rak",
        component: () => import("../pages/RakLokasi/RakLokasi.vue"),
      },
      {
        path: "batch",
        component: () => import("../pages/Batch/Batch.vue"),
      },
      {
        path: "barang-masuk",
        component: () => import("../pages/BarangMasuk/BarangMasuk.vue"),
      },
      {
        path: "barang-keluar",
        component: () => import("../pages/BarangKeluar/BarangKeluar.vue"),
      },
      {
        path: "stok",
        component: () => import("../pages/Stok/Stok.vue"),
      },
      {
        path: "stock-opname",
        component: () => import("../pages/StockOpname/StockOpname.vue"),
      },
      {
        path: "stock-adjustment",
        component: () => import("../pages/StockAdjustment/StockAdjustment.vue"),
      },
      {
        path: "monitoring-expired",
        component: () => import("../pages/MonitoringExpired/MonitoringExpired.vue"),
      },
      {
        path: "laporan",
        component: () => import("../pages/Laporan/Laporan.vue"),
      },
      {
        path: "user",
        component: () => import("../pages/User/User.vue"),
        meta: { roles: ["SUPERADMIN", "ADMIN"] },
      },
      {
        path: "notifications",
        component: () => import("../pages/Notifications/NotificationHistory.vue"),
      },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, from, next) => {
  const user = authUser.value;

  if (to.path !== "/login" && !user) {
    next("/login");
    return;
  }

  if (to.path === "/login" && user) {
    next("/dashboard");
    return;
  }

  if (to.meta.roles && !can(to.meta.roles)) {
    next("/dashboard");
    return;
  }

  next();
});

export default router;
