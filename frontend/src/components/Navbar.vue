<template>
  <nav class="navbar">
    <div class="navbar-left">
      <button class="menu-toggle" @click="toggleSidebar" aria-label="Menu">
        <i class="bi bi-list"></i>
      </button>
      <div class="navbar-greeting">
        <h4>{{ greeting }}, {{ userName }}</h4>
        <small>{{ today }}</small>
      </div>
    </div>

    <div class="navbar-right">
      <div class="notif-box" ref="notifBox">
        <button class="bell-btn" @click="toggleNotifPanel" title="Notifikasi">
          <i class="bi bi-bell"></i>
          <span v-if="unreadCount > 0" class="notif-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
        </button>

        <div v-if="showNotifPanel" class="notif-panel" @click.stop>
          <div class="panel-header">
            <strong>Notifikasi</strong>
            <button v-if="unreadCount > 0" class="btn btn-sm btn-link" @click="markAllRead">Tandai semua</button>
          </div>
          <div v-if="notifError" class="panel-error">
            <i class="bi bi-exclamation-triangle"></i>
            <span>{{ notifError }}</span>
          </div>
          <div v-if="notifLoading" class="panel-loading">
            <div class="spinner"></div>
          </div>
          <div v-else-if="notifications.length === 0" class="panel-empty">
            <i class="bi bi-bell-slash"></i>
            <p>Tidak ada notifikasi</p>
          </div>
          <div v-else>
            <div v-for="n in notifList" :key="n.id" class="panel-item" :class="{ unread: !n.is_read }" @click="clickNotif(n)">
              <div class="panel-item-title">
                <span class="dot" :class="'pt-' + n.priority"></span>
                <span class="title-text">{{ n.title }}</span>
                <span v-if="!n.is_read" class="u-dot"></span>
              </div>
              <div class="panel-time">{{ formatTime(n.created_at) }}</div>
            </div>
            <button class="btn btn-sm btn-block view-all" @click="goToAll">Lihat Semua</button>
          </div>
        </div>
      </div>

      <div class="user-info">
        <img
          :src="'https://ui-avatars.com/api/?name=' + encodeURIComponent(user?.name || 'Admin') + '&background=1976D2&color=fff'"
          class="avatar"
        />
        <div>
          <strong>{{ user?.name }}</strong>
          <small>{{ user?.role }}</small>
        </div>
      </div>

      <button class="logout-btn" @click="handleLogout" title="Keluar">
        <i class="bi bi-box-arrow-right"></i>
      </button>
    </div>
  </nav>
</template>

<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import authService from "../services/auth";
import notificationService from "../services/notification";
import { toggleSidebar } from "../composables/useLayout";
import { authUser, clearAuthUser } from "../stores/auth";
import { POLLING } from "../constants/app";
import { formatDate, timeAgo } from "../utils/date";
import { notifyActionError } from "../services/apiNotifier";

defineProps({ title: { type: String, default: "Dashboard" } });

const router = useRouter();
const user = authUser;

const showNotifPanel = ref(false);
const unreadCount = ref(0);
const notifications = ref([]);
const notifLoading = ref(false);
const notifError = ref(null);
const notifBox = ref(null);

const notifList = computed(() => notifications.value.slice(0, 6));

let notifTimer;

onMounted(() => {
  loadNotifications();
  document.addEventListener("click", handleOutsideClick);
  notifTimer = setInterval(async () => {
    try {
      const res = await notificationService.getUnread();
      if (res.data?.status) unreadCount.value = res.data.total || 0;
    } catch (err) {
      notifError.value = err?.response?.data?.message || "Gagal memuat notifikasi";
    }
  }, POLLING.NOTIFICATION_MS);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleOutsideClick);
  if (notifTimer) clearInterval(notifTimer);
});

async function loadNotifications() {
  notifLoading.value = true;
  try {
    const res = await notificationService.getAll({ page: 1, limit: 10 });
    if (res.data?.status) notifications.value = res.data.data || [];
    const unr = await notificationService.getUnread();
    if (unr.data?.status) unreadCount.value = unr.data.total || 0;
    notifError.value = null;
  } catch (err) {
    notifError.value = err?.response?.data?.message || "Gagal memuat notifikasi";
  } finally {
    notifLoading.value = false;
  }
}

function toggleNotifPanel() {
  showNotifPanel.value = !showNotifPanel.value;
  if (showNotifPanel.value) loadNotifications();
}

function handleOutsideClick(e) {
  if (notifBox.value && !notifBox.value.contains(e.target)) {
    showNotifPanel.value = false;
  }
}

function formatTime(dateStr) {
  return timeAgo(dateStr);
}

async function clickNotif(n) {
  if (!n.is_read) {
    try {
      await notificationService.markRead(n.id);
      n.is_read = 1;
      unreadCount.value = Math.max(0, unreadCount.value - 1);
    } catch (err) {
      notifyActionError(err, "Gagal menandai notifikasi");
      return;
    }
  }
  showNotifPanel.value = false;
  if (n.reference_type) {
    const map = {
      inbound: "/barang-masuk", outbound: "/barang-keluar", product: "/produk",
      batch: "/batch", adjustment: "/stok", opname_session: "/stock-opname", user: "/user",
    };
    const path = map[n.reference_type];
    if (path) { router.push(path); return; }
  }
  router.push("/notifications");
}

async function markAllRead() {
  const previousRead = notifications.value.map((n) => n.is_read);
  const previousUnread = unreadCount.value;
  try {
    await notificationService.markAllRead();
    notifications.value.forEach((n) => (n.is_read = 1));
    unreadCount.value = 0;
  } catch (err) {
    notifications.value.forEach((n, index) => (n.is_read = previousRead[index]));
    unreadCount.value = previousUnread;
    notifyActionError(err, "Gagal menandai semua notifikasi");
  }
}

function goToAll() {
  showNotifPanel.value = false;
  router.push("/notifications");
}

const greeting = computed(() => {
  const h = new Date().getHours();
  if (h >= 5 && h < 12) return "Selamat Pagi";
  if (h >= 12 && h < 15) return "Selamat Siang";
  if (h >= 15 && h < 18) return "Selamat Sore";
  return "Selamat Malam";
});

const userName = computed(() => user.value?.name || "Super Administrator");

const today = computed(() => formatDate(new Date(), "id-ID", {
  weekday: "long", year: "numeric", month: "long", day: "numeric",
}));

const handleLogout = async () => {
  try {
    await authService.logout();
  } catch (err) {
    notifyActionError(err, "Gagal logout");
  }
  clearAuthUser();
  router.push("/login");
};
</script>

<style scoped>
.navbar {
  height: 70px;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(12px);
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 30px;
  border-bottom: 1px solid #e8edf4;
  position: relative;
  z-index: 1001;
}
.navbar-left h4 { margin: 0; color: #1e293b; font-weight: 700; }
.navbar-left small { color: #94a3b8; font-size: 13px; }
.navbar-left { display: flex; align-items: center; gap: 14px; }
.navbar-right { display: flex; align-items: center; gap: 20px; }

.menu-toggle {
  display: none;
  border: none;
  background: #f1f5f9;
  color: #475569;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  font-size: 20px;
  cursor: pointer;
  align-items: center;
  justify-content: center;
}
.menu-toggle:hover { background: #fef2f2; color: #dc2626; }

.user-info { display: flex; align-items: center; gap: 12px; }
.avatar { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; }
.user-info small { display: block; color: #94a3b8; font-size: 12px; }

.logout-btn {
  border: none;
  background: #fef2f2;
  color: #dc2626;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  font-size: 18px;
  display: flex;
  justify-content: center;
  align-items: center;
  cursor: pointer;
  transition: all 0.2s ease;
}
.logout-btn:hover { background: #dc2626; color: white; }

.notif-box { position: relative; }
.bell-btn {
  position: relative;
  border: none;
  background: #f1f5f9;
  color: #475569;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  font-size: 18px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}
.bell-btn:hover { background: #e2e8f0; color: #1e293b; }
.notif-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  background: #dc2626;
  color: white;
  font-size: 11px;
  font-weight: 700;
  min-width: 18px;
  height: 18px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
}

.notif-panel {
  position: absolute;
  right: 0;
  top: 52px;
  width: 340px;
  max-height: 460px;
  overflow: auto;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.15);
  z-index: 1100;
}
.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid #f1f5f9;
  position: sticky;
  top: 0;
  background: white;
}
.panel-header strong { color: #1e293b; }
.panel-header .btn-link { padding: 0; font-size: 13px; color: #1976d2; text-decoration: none; }
.panel-error { display: flex; gap: 8px; align-items: center; padding: 10px 14px; background: #fef2f2; color: #b91c1c; font-size: 12px; }

.panel-loading { padding: 40px; text-align: center; }
.panel-loading .spinner { width: 26px; height: 26px; border: 3px solid #e2e8f0; border-top-color: #1976d2; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto; }
@keyframes spin { to { transform: rotate(360deg); } }
.panel-empty { padding: 40px 16px; text-align: center; color: #94a3b8; }
.panel-empty i { font-size: 30px; }
.panel-empty p { margin-top: 8px; }
.panel-item {
  padding: 12px 16px;
  border-bottom: 1px solid #f8fafc;
  cursor: pointer;
}
.panel-item:hover { background: #f8fafc; }
.panel-item.unread { background: #f0f7ff; }
.panel-item-title { display: flex; align-items: center; gap: 8px; }
.title-text {
  flex: 1;
  font-size: 13px;
  color: #334155;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.pt-critical { background: #c53030; }
.pt-high { background: #d69e2e; }
.pt-medium { background: #1976d2; }
.pt-low { background: #a0aec0; }
.u-dot { width: 8px; height: 8px; border-radius: 50%; background: #1976d2; flex-shrink: 0; }
.panel-time { font-size: 11px; color: #94a3b8; margin-top: 4px; padding-left: 16px; }
.view-all { border-top: 1px solid #f1f5f9; border-radius: 0; color: #1976d2; font-weight: 600; }
.view-all:hover { background: #f8fafc; color: #1976d2; }

@media (max-width: 991.98px) {
  .menu-toggle { display: flex; }
  .navbar { padding: 0 16px; }
}

@media (max-width: 767.98px) {
  .navbar-left small { display: none; }
  .navbar-right { gap: 14px; }
}

@media (max-width: 575.98px) {
  .navbar-greeting { display: none; }
  .navbar-right { gap: 12px; }
  .user-info > div { display: none; }
}
</style>
