<template>
  <div class="monitoring-expired-page">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div class="d-flex align-items-center">
        <div class="header-icon-box me-3">
          <i class="bi bi-clock-history fs-4 text-primary"></i>
        </div>
        <div>
          <h4 class="mb-0 fw-bold text-dark">Monitoring Expired</h4>
          <p class="text-muted small mb-0">Pemantauan Batch Kadaluarsa</p>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm rounded-15 mb-4">
      <div class="card-body p-4">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Dari Tanggal</label>
            <input type="date" class="form-control" v-model="filter.date_from" :max="filter.date_to" />
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Sampai Tanggal</label>
            <input type="date" class="form-control" v-model="filter.date_to" :min="filter.date_from" />
          </div>
          <div class="col-md-auto d-flex gap-2">
            <button class="btn btn-primary btn-modern" @click="applyFilter">
              <i class="bi bi-funnel me-1"></i> Filter
            </button>
            <button class="btn btn-outline-secondary btn-modern" @click="resetFilter">
              <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
            </button>
          </div>
        </div>
      </div>
    </div>

    <ul class="nav nav-pills custom-tabs mb-4 gap-2">
      <li class="nav-item">
        <button
          :class="['nav-link d-flex align-items-center gap-2 px-3 py-2 fw-semibold', activeTab === 'akanExpired' ? 'active' : '']"
          @click="activeTab = 'akanExpired'"
        >
          <i class="bi bi-exclamation-triangle"></i> Mendekati Expired
          <span class="badge bg-warning-subtle text-warning ms-1 rounded-pill">{{ akanExpiredData.length }}</span>
        </button>
      </li>
      <li class="nav-item">
        <button
          :class="['nav-link d-flex align-items-center gap-2 px-3 py-2 fw-semibold', activeTab === 'expiredToday' ? 'active' : '']"
          @click="activeTab = 'expiredToday'"
        >
          <i class="bi bi-clock"></i> Expired Hari Ini
          <span class="badge bg-info-subtle text-info ms-1 rounded-pill">{{ expiredTodayData.length }}</span>
        </button>
      </li>
      <li class="nav-item">
        <button
          :class="['nav-link d-flex align-items-center gap-2 px-3 py-2 fw-semibold', activeTab === 'sudahExpired' ? 'active' : '']"
          @click="activeTab = 'sudahExpired'"
        >
          <i class="bi bi-x-octagon"></i> Sudah Expired
          <span class="badge bg-danger-subtle text-danger ms-1 rounded-pill">{{ sudahExpiredData.length }}</span>
        </button>
      </li>
      <li class="nav-item">
        <button
          :class="['nav-link d-flex align-items-center gap-2 px-3 py-2 fw-semibold', activeTab === 'semua' ? 'active' : '']"
          @click="activeTab = 'semua'"
        >
          <i class="bi bi-list-ul"></i> Total Batch
          <span class="badge bg-primary-subtle text-primary ms-1 rounded-pill">{{ semuaData.length }}</span>
        </button>
      </li>
    </ul>

    <div class="card border-0 shadow-sm rounded-15 mb-4">
      <div class="card-body p-4">
        <PageErrorAlert :error="loadError" fallback="Gagal memuat data expired." @retry="loadAll" />

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase fs-7">
              <tr>
                <th class="py-3 ps-3">No</th>
                <th class="py-3">Produk</th>
                <th class="py-3">Batch No</th>
                <th class="py-3">Stok</th>
                <th class="py-3">Tanggal Expired</th>
                <th class="py-3 pe-3">Sisa Hari</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in paginatedData" :key="item.id || item.batchNumber">
                <td class="ps-3">{{ paginationStart + index }}</td>
                <td class="fw-semibold">{{ item.name }}</td>
                <td><code>{{ item.batchNumber }}</code></td>
                <td>{{ item.quantity }}</td>
                <td class="fw-semibold">{{ item.expiryDate }}</td>
                <td class="text-center pe-3">
                  <template v-if="activeTab === 'sudahExpired'">
                    <span class="text-muted">-</span>
                  </template>
                  <template v-else-if="activeTab === 'expiredToday'">
                    <span class="badge rounded-pill px-2 py-1 bg-info-subtle text-info">
                      <i class="bi bi-clock"></i> 0 hari
                    </span>
                  </template>
                  <StatusBadge v-else :status="daysTone(item.daysLeft)" class="rounded-pill px-2 py-1">
                    <i class="bi" :class="item.daysLeft <= criticalDays ? 'bi-exclamation-circle' : 'bi-clock'"></i>
                    {{ item.daysLeft }} hari
                  </StatusBadge>
                </td>
              </tr>
              <tr v-if="paginatedData.length === 0">
                <td colspan="6" class="text-center py-4 text-muted">Tidak ada data ditemukan.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
          <div class="text-muted small">
            Menampilkan {{ paginationStart }} sampai {{ paginationEnd }} dari {{ currentData.length }} entri
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
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import expiredService from "../../services/expired";
import { usePagination } from "../../composables/usePagination";
import { EXPIRED_THRESHOLD } from "../../constants/app";
import { normalizeExpiredItem } from "../../utils/expiredItems";
import BasePagination from "../../components/BasePagination.vue";
import PageErrorAlert from "../../components/PageErrorAlert.vue";
import StatusBadge from "../../components/StatusBadge.vue";

const activeTab = ref("akanExpired");
const akanExpiredData = ref([]);
const expiredTodayData = ref([]);
const sudahExpiredData = ref([]);
const semuaData = ref([]);
const loadError = ref(null);

const filter = ref({
  date_from: "",
  date_to: "",
});

const criticalDays = EXPIRED_THRESHOLD.CRITICAL_DAYS;

const daysTone = (daysLeft) => {
  if (daysLeft <= criticalDays) return "EXPIRED";
  if (daysLeft <= EXPIRED_THRESHOLD.WARNING_DAYS) return "WARNING";
  return "AKTIF";
};

const tabLoaders = {
  akanExpired: { target: akanExpiredData, fetch: () => expiredService.nearExpiry() },
  expiredToday: { target: expiredTodayData, fetch: () => expiredService.expiredToday() },
  sudahExpired: { target: sudahExpiredData, fetch: () => expiredService.getList({ status: "expired" }) },
  semua: {
    target: semuaData,
    fetch: () => {
      const params = {};
      if (filter.value.date_from) params.date_from = filter.value.date_from;
      if (filter.value.date_to) params.date_to = filter.value.date_to;
      return expiredService.getList(params);
    },
  },
};

const loadTab = async (tab) => {
  const loader = tabLoaders[tab];
  if (!loader) return;
  try {
    const res = await loader.fetch();
    if (!res.data?.status) throw new Error(res.data?.message || "Gagal memuat data expired");
    loader.target.value = res.data.data || [];
  } catch (err) {
    loader.target.value = [];
    if (!loadError.value) loadError.value = err;
  }
};

const loadAll = async () => {
  loadError.value = null;
  await Promise.all(Object.keys(tabLoaders).map(loadTab));
};

const applyFilter = () => {
  resetPage();
  loadTab("semua");
  activeTab.value = "semua";
};

const resetFilter = () => {
  filter.value.date_from = "";
  filter.value.date_to = "";
  resetPage();
  loadAll();
};

const currentData = computed(() => {
  if (activeTab.value === "akanExpired") return akanExpiredData.value;
  if (activeTab.value === "expiredToday") return expiredTodayData.value;
  if (activeTab.value === "sudahExpired") return sudahExpiredData.value;
  return semuaData.value;
});

const normalizedData = computed(() => currentData.value.map(normalizeExpiredItem));

const {
  currentPage,
  totalPages,
  visiblePages,
  paginatedItems: paginatedData,
  paginationStart,
  paginationEnd,
  changePage,
  resetPage,
} = usePagination(normalizedData);

watch(activeTab, resetPage);

onMounted(loadAll);
</script>

<style scoped>
.monitoring-expired-page {
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

.custom-tabs .nav-link {
  background-color: #ffffff;
  color: #64748b;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  transition: all 0.2s ease;
}

.custom-tabs .nav-link:hover {
  border-color: #2563eb;
  color: #2563eb;
}

.custom-tabs .nav-link.active {
  background-color: #2563eb;
  border-color: #2563eb;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.btn-modern {
  border-radius: 8px;
  padding: 8px 16px;
  font-weight: 500;
  transition: all 0.2s ease;
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

.form-control {
  border-radius: 8px;
  border-color: #cbd5e1;
  padding: 10px 14px;
}

.form-control:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.pagination .page-link {
  border-radius: 6px;
  margin: 0 2px;
  color: #2563eb;
  border-color: #e2e8f0;
}

.pagination .page-item.active .page-link {
  background-color: #2563eb;
  border-color: #2563eb;
  color: #fff;
}

.pagination .page-link:hover {
  background-color: #eff6ff;
  color: #1d4ed8;
}
</style>
