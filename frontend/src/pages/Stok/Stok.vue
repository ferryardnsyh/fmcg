<template>
  <div class="stok-page">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div class="d-flex align-items-center">
        <div class="header-icon-box me-3">
          <i class="bi bi-box-seam fs-4 text-primary"></i>
        </div>
        <div>
          <h4 class="mb-0 fw-bold text-dark">Manajemen Stok</h4>
          <p class="text-muted small mb-0">Monitoring dan Pengelolaan Stok Barang</p>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm rounded-15 mb-4">
      <div class="card-body p-0">
        <ul class="nav nav-tabs border-0 px-4 pt-3" style="border-bottom: 2px solid #e2e8f0;">
          <li class="nav-item" v-for="(tab, idx) in tabs" :key="idx">
            <a
              class="nav-link fw-semibold small"
              :class="{ active: activeTab === idx }"
              href="#"
              @click.prevent="activeTab = idx"
              style="border: none; border-bottom: 2px solid transparent; margin-bottom: -2px; color: #64748b;"
              :style="activeTab === idx && { color: '#2563eb', borderBottomColor: '#2563eb', backgroundColor: 'transparent' }"
            >
              <i :class="tab.icon" class="me-1"></i> {{ tab.label }}
            </a>
          </li>
        </ul>

        <div class="p-4">
          <PageErrorAlert :error="loadError" fallback="Gagal memuat data stok." @retry="loadAll" />
          <!-- Tab 1: Stok Real-time -->
          <div v-if="activeTab === 0">
            <div class="row g-3 mb-4 justify-content-between align-items-center">
              <div class="col-md-4">
                <div class="input-group search-box">
                  <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                  </span>
                  <input type="search" inputmode="text" autocomplete="off" class="form-control border-start-0 ps-0" placeholder="Cari produk..." v-model="searchRealtime" />
                </div>
              </div>
              <div class="col-md-auto d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary fw-semibold fs-7">
                  {{ filteredRealtime.length }} produk
                </span>
                <button class="btn btn-outline-secondary btn-modern d-flex align-items-center" @click="loadRealtime">
                  <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                </button>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light fs-7">
                  <tr>
                    <th class="py-3 ps-3">No</th>
                    <th class="py-3">Kode Produk</th>
                    <th class="py-3">Nama Produk</th>
                    <th class="py-3">Total Stok</th>
                    <th class="py-3">Tingkat Stok</th>
                    <th class="py-3 pe-3">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in paginatedRows" :key="item.id || index">
                    <td class="ps-3">{{ paginationStart + index }}</td>
                    <td><code>{{ item.sku_code || item.sku || item.code }}</code></td>
                    <td class="fw-semibold">{{ item.name || item.nama_produk || item.product_name }}</td>
                    <td>{{ val(item, 'total_available', 'total_stock', 'total_stok', 'stock') }}</td>
                    <td>{{ val(item, 'minimum_stock', 'min_stock', 'min_stok') }}</td>
                    <td class="pe-3">
                      <StatusBadge
                        :status="stockStatus(item).status"
                        :label="stockStatus(item).label"
                        class="rounded-pill px-2 py-1"
                      />
                    </td>
                  </tr>
                  <tr v-if="activeRows.length === 0">
                    <td colspan="6" class="text-center py-4 text-muted">Tidak ada data stok.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Tab 2: Transaksi Stok -->
          <div v-if="activeTab === 1">
            <div class="d-flex justify-content-end mb-3">
              <button class="btn btn-outline-secondary btn-modern d-flex align-items-center" @click="loadMutations">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
              </button>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light fs-7">
                  <tr>
                    <th class="py-3 ps-3">No</th>
                    <th class="py-3">Tanggal</th>
                    <th class="py-3">Tipe</th>
                    <th class="py-3">Nomor Dokumen</th>
                    <th class="py-3 pe-3">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in paginatedRows" :key="item.id || index">
                    <td class="ps-3">{{ paginationStart + index }}</td>
                    <td>{{ formatDate(item.mutation_date || item.created_at || item.tanggal || item.date) }}</td>
                    <td>
                      <StatusBadge
                        :status="typeStatus(item)"
                        :label="typeLabel(item.type || item.tipe)"
                        class="rounded-pill px-2 py-1"
                      />
                    </td>
                    <td class="fw-semibold"><code>{{ item.document_number || item.no_dokumen || '-' }}</code></td>
                    <td class="pe-3">
                      <StatusBadge
                        :status="statusTone(item.status)"
                        :label="statusLabel(item.status)"
                        class="rounded-pill px-2 py-1"
                      />
                    </td>
                  </tr>
                  <tr v-if="activeRows.length === 0">
                    <td colspan="5" class="text-center py-4 text-muted">Tidak ada data mutasi.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Tab 3: Stok Menipis -->
          <div v-if="activeTab === 2">
            <div class="d-flex justify-content-end mb-3">
              <button class="btn btn-outline-secondary btn-modern d-flex align-items-center" @click="loadLowStock">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
              </button>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light fs-7">
                  <tr>
                    <th class="py-3 ps-3">No</th>
                    <th class="py-3">Kode Produk</th>
                    <th class="py-3">Nama Produk</th>
                    <th class="py-3">Stok Saat Ini</th>
                    <th class="py-3">Tingkat Stok</th>
                    <th class="py-3 pe-3">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in paginatedRows" :key="item.id || index">
                    <td class="ps-3">{{ paginationStart + index }}</td>
                    <td><code>{{ item.sku_code || item.sku || item.code }}</code></td>
                    <td class="fw-semibold">{{ item.name || item.nama_produk || item.product_name }}</td>
                    <td class="fw-bold">{{ val(item, 'total_available', 'current_stock', 'stock', 'total_stok') }}</td>
                    <td>{{ val(item, 'minimum_stock', 'min_stock', 'min_stok') }}</td>
                    <td class="pe-3">
                      <StatusBadge
                        :status="stockStatus(item).status"
                        :label="stockStatus(item).label"
                        class="rounded-pill px-2 py-1"
                      />
                    </td>
                  </tr>
                  <tr v-if="activeRows.length === 0">
                    <td colspan="6" class="text-center py-4 text-muted">Semua stok dalam kondisi aman.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-if="activeRows.length" class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
            <div class="text-muted small">
              Menampilkan {{ paginationStart }} sampai {{ paginationEnd }} dari
              {{ activeRows.length }} entri
            </div>
            <BasePagination
              :current-page="currentPage"
              :total-pages="totalPages"
              :visible-pages="visiblePages"
              @change="changePage"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import stockService from "../../services/stock";
import { usePagination } from "../../composables/usePagination";
import { formatDate } from "../../utils/date";
import { STATUS_BADGES, STATUS_LABELS } from "../../utils/status";
import BasePagination from "../../components/BasePagination.vue";
import PageErrorAlert from "../../components/PageErrorAlert.vue";
import StatusBadge from "../../components/StatusBadge.vue";

const activeTab = ref(0);
const tabs = [
  { label: "Stok Real-time", icon: "bi bi-eye" },
  { label: "Transaksi Stok", icon: "bi bi-arrow-left-right" },
  { label: "Stok Menipis", icon: "bi bi-exclamation-triangle" },
];

const searchRealtime = ref("");
const loadError = ref(null);
const realtimeData = ref([]);
const mutations = ref([]);
const lowStock = ref([]);

const filteredRealtime = computed(() => {
  if (!searchRealtime.value) return realtimeData.value;
  const q = searchRealtime.value.toLowerCase();
  return realtimeData.value.filter(
    (item) =>
      (item.sku_code || item.sku || item.code || "").toLowerCase().includes(q) ||
      (item.name || item.nama_produk || item.product_name || "").toLowerCase().includes(q)
  );
});

const toArray = (data) => (Array.isArray(data) ? data : []);
const val = (item, ...keys) => { for (const k of keys) { const v = item[k]; if (v !== null && v !== undefined) return Number(v); } return 0; };

const reorderPoint = (item) => {
  return val(item, 'minimum_stock', 'min_stock', 'min_stok');
};

const stockStatus = (item) => {
  const stok = val(item, 'total_available', 'total_stock', 'total_stok', 'stock');
  const reorder = reorderPoint(item);
  const minimum = val(item, 'minimum_stock', 'min_stock', 'min_stok');
  if (stok > reorder) return { label: 'Aman', status: 'AKTIF' };
  if (stok <= minimum) return { label: 'Kritis', status: 'EXPIRED' };
  return { label: 'Menipis', status: 'WARNING' };
};

const statusTone = (status) => {
  const up = String(status || "").toUpperCase();
  if (up === "COMPLETED") return STATUS_BADGES.COMPLETED;
  if (["APPROVED", "AVAILABLE"].includes(up)) return "SELESAI";
  if (up === "CANCELLED") return "NONAKTIF";
  return up;
};

const statusLabel = (status) => {
  const up = (status || "").toUpperCase();
  const map = {
    ...STATUS_LABELS,
    PENDING: "Menunggu",
    APPROVED: "Disetujui",
    REJECTED: "Ditolak",
    AVAILABLE: "Tersedia",
    EXPIRED: "Kedaluwarsa",
  };
  return map[up] || status || "-";
};

const typeStatus = (item) => {
  const up = String(item.type || item.tipe || "").toUpperCase();
  if (up === "INBOUND" || up === "IN") return "AKTIF";
  if (up === "OUTBOUND" || up === "OUT") return "EXPIRED";
  return "WARNING";
};

const typeLabel = (type) => {
  const up = (type || "").toUpperCase();
  const map = {
    INBOUND: "Barang Masuk",
    OUTBOUND: "Barang Keluar",
    IN: "Masuk",
    OUT: "Keluar",
  };
  return map[up] || type || "-";
};

const activeRows = computed(() => {
  if (activeTab.value === 0) return filteredRealtime.value;
  if (activeTab.value === 1) return mutations.value;
  return lowStock.value;
});

const {
  currentPage,
  totalPages,
  visiblePages,
  paginatedItems: paginatedRows,
  paginationStart,
  paginationEnd,
  changePage,
  resetPage,
} = usePagination(activeRows);

watch(activeTab, resetPage);
watch(searchRealtime, resetPage);

const setLoadError = (err) => {
  if (!loadError.value) loadError.value = err;
};

const loadRealtime = async () => {
  try {
    const res = await stockService.realtime();
    realtimeData.value = toArray(res.data?.status ? res.data.data : res.data);
  } catch (err) {
    realtimeData.value = [];
    setLoadError(err);
  }
};

const loadMutations = async () => {
  try {
    const res = await stockService.mutations();
    mutations.value = toArray(res.data?.status ? res.data.data : res.data);
  } catch (err) {
    mutations.value = [];
    setLoadError(err);
  }
};

const loadLowStock = async () => {
  try {
    const res = await stockService.lowStock();
    lowStock.value = toArray(res.data?.status ? res.data.data : res.data);
  } catch (err) {
    lowStock.value = [];
    setLoadError(err);
  }
};

const loadAll = async () => {
  loadError.value = null;
  await Promise.all([loadRealtime(), loadMutations(), loadLowStock()]);
};

onMounted(loadAll);
</script>

<style scoped>
.stok-page {
  background-color: #f8fafc;
  min-height: 100vh;
  padding: 24px;
}

.rounded-15 {
  border-radius: 15px !important;
}

.header-icon-box {
  width: 48px;
  height: 48px;
  background-color: #eff6ff;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.nav-tabs .nav-link {
  transition: all 0.2s ease;
}

.nav-tabs .nav-link:hover {
  color: #2563eb !important;
}

.search-box .input-group-text,
.search-box .form-control {
  background-color: #fff;
  border-color: #e2e8f0;
}

.search-box .form-control:focus {
  box-shadow: none;
  border-color: #2563eb;
}

.btn-modern {
  border-radius: 8px;
  padding: 8px 16px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.btn-primary {
  background-color: #2563eb;
  border-color: #2563eb;
}

.btn-primary:hover {
  background-color: #1d4ed8;
  border-color: #1d4ed8;
}

.table {
  color: #334155;
}

.table-hover tbody tr {
  transition: background-color 0.15s ease;
}

.table-hover tbody tr:hover {
  background-color: #f1f5f9;
}

.modal-content {
  background-color: #ffffff;
}

.form-control,
.form-select {
  border-radius: 8px;
  border-color: #cbd5e1;
  padding: 10px 14px;
}

.form-control:focus,
.form-select:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}
</style>
