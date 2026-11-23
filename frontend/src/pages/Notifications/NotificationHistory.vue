<template>
  <div class="notif-page">
    <div class="page-header">
      <div>
        <h3>Riwayat Notifikasi</h3>
        <small>{{ total }} notifikasi</small>
      </div>
      <div class="header-actions">
        <select v-model="filterType" @change="loadPage(1)" class="form-select">
          <option value="">Semua Tipe</option>
          <option value="out_of_stock">Stok Habis</option>
          <option value="low_stock">Stok Menipis</option>
          <option value="expired">Kadaluarsa</option>
          <option value="near_expiry">Akan Kadaluarsa</option>
          <option value="pending_approval">Butuh Approval</option>
          <option value="inbound_completed">Barang Masuk</option>
          <option value="outbound_completed">Barang Keluar</option>
          <option value="opname_selisih">Opname</option>
          <option value="stock_adjustment">Adjustment</option>
          <option value="user_activity">Aktivitas User</option>
        </select>
        <select v-model="filterRead" @change="loadPage(1)" class="form-select">
          <option value="">Semua Status</option>
          <option value="0">Belum Dibaca</option>
          <option value="1">Sudah Dibaca</option>
        </select>
        <button v-if="isAdmin" class="btn btn-sm btn-outline-danger" @click="handleGenerate">
          Generate
        </button>
        <button v-if="unreadCount > 0" class="btn btn-sm btn-primary" @click="handleMarkAllRead">
          Tandai Semua Dibaca
        </button>
      </div>
    </div>

    <PageErrorAlert :error="error" fallback="Gagal memuat notifikasi." @retry="retryLoad" />

    <div class="search-bar">
      <i class="bi bi-search"></i>
      <input
        v-model="search"
        type="search"
        inputmode="text"
        autocomplete="off"
        placeholder="Cari notifikasi..."
        @input="debounceSearch"
        class="form-control"
      />
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Memuat notifikasi...</p>
    </div>

    <div v-else-if="notifications.length === 0" class="empty-state">
      <i class="bi bi-inbox"></i>
      <h5>Tidak ada notifikasi</h5>
      <p>Belum ada notifikasi yang sesuai dengan filter.</p>
    </div>

    <div v-else class="notif-list">
      <div
        v-for="n in notifications"
        :key="n.id"
        class="notif-card"
        :class="{ unread: !n.is_read }"
        @click="handleClick(n)"
      >
        <div class="notif-icon" :class="'notif-' + n.type">
          <i :class="notificationIcon(n.type)"></i>
        </div>
        <div class="notif-content">
          <div class="notif-row">
            <span class="notif-type-badge" :class="'badge-' + n.type">{{ notificationLabel(n.type) }}</span>
            <span class="notif-priority" :class="'priority-' + n.priority">{{ notificationPriorityLabel(n.priority) }}</span>
          </div>
          <div class="notif-title">{{ n.title }}</div>
          <div v-if="n.message" class="notif-message">{{ n.message }}</div>
          <div class="notif-footer">
            <span class="notif-time">{{ formatNotificationTime(n.created_at) }}</span>
            <span v-if="n.is_read" class="read-badge">Dibaca</span>
          </div>
        </div>
        <div v-if="!n.is_read" class="unread-dot"></div>
      </div>
    </div>

    <div v-if="pages > 1" class="pagination-bar">
      <button :disabled="page <= 1" @click="loadPage(page - 1)" class="btn btn-outline-secondary btn-sm">Sebelumnya</button>
      <span>Halaman {{ page }} dari {{ pages }}</span>
      <button :disabled="page >= pages" @click="loadPage(page + 1)" class="btn btn-outline-secondary btn-sm">Selanjutnya</button>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from "vue";
import { useRouter } from "vue-router";
import Swal from "sweetalert2";
import { useNotificationList } from "../../composables/useNotificationList";
import { isAdmin } from "../../stores/auth";
import {
  formatNotificationTime,
  notificationIcon,
  notificationLabel,
  notificationPath,
  notificationPriorityLabel,
} from "../../utils/notification";
import PageErrorAlert from "../../components/PageErrorAlert.vue";

const router = useRouter();

const {
  notifications,
  page,
  pages,
  total,
  unreadCount,
  loading,
  error,
  search,
  filterType,
  filterRead,
  loadPage,
  debounceSearch,
  markRead,
  markAllRead,
  generate,
} = useNotificationList();

const retryLoad = () => loadPage(page.value);

async function handleClick(n) {
  await markRead(n);
  const path = notificationPath(n.reference_type);
  if (path) router.push(path);
}

async function handleMarkAllRead() {
  if (await markAllRead()) {
    Swal.fire({ icon: "success", title: "Semua dibaca", timer: 1500, showConfirmButton: false });
  }
}

async function handleGenerate() {
  const message = await generate();
  if (!message) {
    Swal.fire({ icon: "error", title: "Gagal", text: "Gagal menghasilkan notifikasi" });
    return;
  }
  Swal.fire({ icon: "success", title: "Berhasil", text: message, timer: 1800, showConfirmButton: false });
  loadPage(1);
}

onMounted(() => loadPage(1));
</script>

<style scoped>
.notif-page { padding: 24px; max-width: 900px; margin: 0 auto; }

.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.page-header h3 { margin: 0; color: #233142; }
.page-header small { color: #888; }

.header-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.header-actions .form-select { width: auto; min-width: 150px; font-size: 13px; padding: 6px 10px; }

.search-bar { position: relative; margin-bottom: 20px; }
.search-bar i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #aaa; }
.search-bar input { padding-left: 36px; border-radius: 8px; border: 1px solid #ddd; font-size: 14px; }

.loading-state, .empty-state { text-align: center; padding: 60px 20px; color: #999; }
.loading-state .spinner { width: 36px; height: 36px; border: 3px solid #e0e0e0; border-top-color: #1976d2; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 12px; }
@keyframes spin { to { transform: rotate(360deg); } }
.empty-state i { font-size: 48px; display: block; margin-bottom: 12px; }
.empty-state h5 { color: #555; }
.empty-state p { margin: 0; }

.notif-list { display: flex; flex-direction: column; gap: 8px; }

.notif-card {
  display: flex;
  gap: 14px;
  padding: 16px 20px;
  background: white;
  border-radius: 10px;
  border: 1px solid #eee;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}
.notif-card:hover { border-color: #1976d2; box-shadow: 0 2px 12px rgba(25, 118, 210, 0.1); }
.notif-card.unread { background: #f8fbff; border-left: 3px solid #1976d2; }

.notif-icon {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 18px;
  color: white;
}
.notif-out_of_stock, .notif-expired { background: #c53030; }
.notif-low_stock { background: #e53e3e; }
.notif-near_expiry { background: #d69e2e; }
.notif-pending_approval { background: #1976d2; }
.notif-inbound_completed { background: #38a169; }
.notif-outbound_completed { background: #dd6b20; }
.notif-opname_selisih { background: #805ad5; }
.notif-stock_adjustment { background: #e53e3e; }
.notif-user_activity { background: #718096; }

.notif-content { flex: 1; min-width: 0; }
.notif-row { display: flex; gap: 8px; align-items: center; margin-bottom: 4px; }

.notif-type-badge {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 4px;
  font-weight: 600;
  text-transform: uppercase;
}
.badge-out_of_stock { background: #fff5f5; color: #c53030; }
.badge-expired { background: #fff5f5; color: #c53030; }
.badge-low_stock { background: #fff5f5; color: #e53e3e; }
.badge-near_expiry { background: #fffff0; color: #d69e2e; }
.badge-pending_approval { background: #ebf8ff; color: #1976d2; }
.badge-inbound_completed { background: #f0fff4; color: #38a169; }
.badge-outbound_completed { background: #fffaf0; color: #dd6b20; }
.badge-opname_selisih { background: #faf5ff; color: #805ad5; }
.badge-stock_adjustment { background: #fff5f5; color: #e53e3e; }
.badge-user_activity { background: #f7fafc; color: #718096; }

.notif-priority {
  font-size: 10px;
  padding: 1px 6px;
  border-radius: 3px;
  font-weight: 700;
  text-transform: uppercase;
}
.priority-critical { background: #c53030; color: white; }
.priority-high { background: #d69e2e; color: white; }
.priority-medium { background: #1976d2; color: white; }
.priority-low { background: #a0aec0; color: white; }

.notif-title { font-size: 14px; font-weight: 600; color: #233142; margin-bottom: 4px; }
.notif-message { font-size: 13px; color: #555; line-height: 1.5; white-space: pre-line; margin-bottom: 6px; }

.notif-footer { display: flex; gap: 12px; align-items: center; }
.notif-time { font-size: 12px; color: #999; }
.read-badge { font-size: 11px; color: #38a169; font-weight: 600; }

.unread-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #1976d2;
  flex-shrink: 0;
  margin-top: 6px;
}

.pagination-bar {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
  color: #666;
  font-size: 14px;
}
</style>
