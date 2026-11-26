<template>
  <div class="batch-page">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div class="d-flex align-items-center">
        <div class="header-icon-box me-3">
          <i class="bi bi-layers fs-4 text-primary"></i>
        </div>
        <div>
          <h4 class="mb-0 fw-bold text-dark">Batch</h4>
          <p class="text-muted small mb-0">Monitoring Batch & Kadaluarsa</p>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-xl-4 col-md-6">
        <div class="card stat-card border-0 shadow-sm rounded-15 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Total Batch</p>
              <h3 class="fw-bold mb-0 text-dark">{{ stats.total }}</h3>
            </div>
            <div class="stat-icon bg-primary-subtle text-primary">
              <i class="bi bi-layers fs-4"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-md-6">
        <div class="card stat-card border-0 shadow-sm rounded-15 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Batch Aktif</p>
              <h3 class="fw-bold mb-0 text-success">{{ stats.available }}</h3>
            </div>
            <div class="stat-icon bg-success-subtle text-success">
              <i class="bi bi-check-circle fs-4"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-md-6">
        <div class="card stat-card border-0 shadow-sm rounded-15 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Total Qty Tersedia</p>
              <h3 class="fw-bold mb-0 text-warning">{{ stats.total_jumlah }}</h3>
            </div>
            <div class="stat-icon bg-warning-subtle text-warning">
              <i class="bi bi-boxes fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm rounded-15 mb-4">
      <div class="card-body p-4">
        <PageErrorAlert :error="loadError" fallback="Gagal memuat data batch." @retry="loadAll" />
        <div class="row g-3 mb-4 justify-content-between align-items-center">
          <div class="col-md-4">
            <div class="input-group search-box">
              <span class="input-group-text bg-white border-end-0">
                <i class="bi bi-search text-muted"></i>
              </span>
              <input
                type="search"
                inputmode="text"
                autocomplete="off"
                class="form-control border-start-0 ps-0"
                placeholder="Cari Batch ID / Produk / No Batch..."
                v-model="searchQuery"
              />
            </div>
          </div>
          <div class="col-md-auto d-flex flex-wrap gap-2 align-items-center">
            <div class="d-flex align-items-center gap-2">
              <label class="small fw-semibold text-muted mb-0 text-nowrap"
                >Filter Produk:</label
              >
              <select
                class="form-select form-select-sm rounded-8"
                v-model.number="filterProduk"
                style="width: 160px"
              >
                <option value="">Semua Produk</option>
                <option
                  v-for="prod in uniqueProducts"
                  :key="prod.id"
                  :value="prod.id"
                >
                  {{ prod.name }}
                </option>
              </select>
            </div>
            <button
              class="btn btn-outline-secondary btn-modern d-flex align-items-center"
              @click="refreshData"
            >
              <i class="bi bi-arrow-clockwise me-1"></i> Refresh
            </button>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light fs-7">
              <tr>
                <th class="py-3 ps-3">No</th>
                <th class="py-3">Produk</th>
                <th class="py-3">Nomor Batch</th>
                <th class="py-3">Stok</th>
                <th class="py-3">Tanggal Kedaluwarsa</th>
                <th class="py-3">Sisa Hari</th>
                <th class="py-3">Lokasi Rak</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in paginatedBatches" :key="item.id_batch">
                <td class="ps-3">{{ paginationStart + index }}</td>
                <td>{{ item.product_name }}</td>
                <td>
                  <code>{{ item.batch_number }}</code>
                </td>
                <td class="fw-semibold">{{ item.quantity }}</td>
                <td>{{ formatDate(item.expiry_date) }}</td>
                <td>
                  <template v-if="item.days_remaining !== null && item.days_remaining !== undefined">
                    <StatusBadge
                      :status="remainingTone(item.days_remaining)"
                      class="rounded-pill px-2 py-1"
                    >
                      <i class="bi" :class="isCritical(item.days_remaining) ? 'bi-exclamation-circle' : 'bi-clock'"></i>
                      {{ item.days_remaining }} hari
                    </StatusBadge>
                  </template>
                  <span v-else class="text-muted">-</span>
                </td>
                <td>{{ item.rack_name || item.rack_code || '-' }}</td>
              </tr>
              <tr v-if="filteredBatches.length === 0">
                <td colspan="7" class="text-center py-4 text-muted">
                  Tidak ada data batch ditemukan.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
          <div class="text-muted small">
            Menampilkan {{ paginationStart }} sampai {{ paginationEnd }} dari
            {{ filteredBatches.length }} entri
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
import batchService from "../../services/batch";
import { usePagination } from "../../composables/usePagination";
import { EXPIRED_THRESHOLD } from "../../constants/app";
import { formatDate } from "../../utils/date";
import BasePagination from "../../components/BasePagination.vue";
import PageErrorAlert from "../../components/PageErrorAlert.vue";
import StatusBadge from "../../components/StatusBadge.vue";

const CRITICAL_DAYS = EXPIRED_THRESHOLD.CRITICAL_DAYS;

const searchQuery = ref("");
const filterProduk = ref("");
const loadError = ref(null);

const batches = ref([]);

const stats = ref({
  total: 0,
  available: 0,
  expired: 0,
  total_jumlah: 0,
});

const loadStats = async () => {
  try {
    const res = await batchService.statistic();
    if (res.data.status) {
      stats.value = res.data.data;
    }
  } catch (error) {
    loadError.value = error;
  }
};

const loadBatches = async () => {
  try {
    const res = await batchService.getBatches();
    if (res.data.status) {
      batches.value = res.data.data;
    }
  } catch (error) {
    batches.value = [];
    loadError.value = error;
  }
};

const loadAll = async () => {
  loadError.value = null;
  await Promise.all([loadStats(), loadBatches()]);
};

const isCritical = (days) => Number(days) <= CRITICAL_DAYS;

const remainingTone = (days) => {
  if (isCritical(days)) return "EXPIRED";
  if (Number(days) <= EXPIRED_THRESHOLD.WARNING_DAYS) return "WARNING";
  return "AKTIF";
};

const uniqueProducts = computed(() => {
  const map = new Map();
  batches.value.forEach((b) => {
    if (b.id_product && b.product_name && !map.has(b.id_product)) {
      map.set(b.id_product, { id: b.id_product, name: b.product_name });
    }
  });
  return Array.from(map.values());
});

const filteredBatches = computed(() => {
  let result = batches.value;

  if (filterProduk.value) {
    result = result.filter((b) => String(b.id_product) === String(filterProduk.value));
  }

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(
      (b) =>
        (b.batch_number || "").toLowerCase().includes(q) ||
        (b.product_name || "").toLowerCase().includes(q),
    );
  }

  return result;
});

const {
  currentPage,
  totalPages,
  visiblePages,
  paginatedItems: paginatedBatches,
  paginationStart,
  paginationEnd,
  changePage,
  resetPage,
} = usePagination(filteredBatches);

watch([searchQuery, filterProduk], resetPage);

onMounted(loadAll);

const refreshData = async () => {
  searchQuery.value = "";
  filterProduk.value = "";
  resetPage();
  await loadAll();
};
</script>

<style scoped>
.batch-page {
  background-color: #f8fafc;
  min-height: 100vh;
  padding: 24px;
}

.rounded-15 {
  border-radius: 15px !important;
}

.rounded-8 {
  border-radius: 8px !important;
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

.stat-card {
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
}

.stat-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
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
