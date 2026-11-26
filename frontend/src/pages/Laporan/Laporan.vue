<template>
  <div class="laporan-page">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print flex-wrap gap-2">
      <div class="d-flex align-items-center">
        <div class="header-icon-box me-3">
          <i class="bi bi-bar-chart-fill fs-4 text-primary"></i>
        </div>
        <div>
          <h4 class="mb-0 fw-bold text-dark">Laporan</h4>
          <p class="text-muted small mb-0">Pusat Laporan Transaksi & Stok Gudang</p>
        </div>
      </div>
    </div>

    <div class="print-watermark">
      <img src="../../assets/logo.png" alt="Logo" />
      <span>CV. Putra Sumber Sari</span>
    </div>

    <ul class="nav nav-pills custom-tabs mb-4 gap-2 no-print">
      <li class="nav-item" v-for="tab in tabs" :key="tab.key">
        <button
          :class="['nav-link d-flex align-items-center gap-2 px-3 py-2 fw-semibold', activeTab === tab.key ? 'active' : '']"
          @click="switchTab(tab.key)"
        >
          <i :class="tab.icon"></i> {{ tab.label }}
        </button>
      </li>
    </ul>

    <div class="card border-0 shadow-sm rounded-15 mb-4">
      <div class="card-body p-4">
        <PageErrorAlert :error="loadError" fallback="Gagal memuat data laporan." @retry="retryLoad" />

        <div v-if="showFilter" class="row g-3 align-items-end mb-4 no-print">
          <div class="col-md-3" v-if="activeTab !== 'stokOpname'">
            <label class="form-label small fw-semibold">Dari Tanggal</label>
            <input type="date" class="form-control" v-model="filter.date_from" :max="filter.date_to" />
          </div>
          <div class="col-md-3" v-if="activeTab !== 'stokOpname'">
            <label class="form-label small fw-semibold">Sampai Tanggal</label>
            <input type="date" class="form-control" v-model="filter.date_to" :min="filter.date_from" />
          </div>
          <div class="col-md-3" v-if="activeTab === 'stokOpname'">
            <label class="form-label small fw-semibold">Pilih Sesi Opname</label>
            <select class="form-select" v-model.number="filter.session_id" @change="loadData">
              <option value="" disabled>-- Pilih Sesi --</option>
              <option v-for="s in sessions" :key="s.id_opname" :value="s.id_opname">
                {{ s.opname_number || s.id }} ({{ formatDate(s.opname_date) }})
              </option>
            </select>
          </div>
          <div class="col-md-auto d-flex gap-2">
            <button class="btn btn-primary btn-modern" @click="loadData">
              <i class="bi bi-funnel me-1"></i> Filter
            </button>
            <button class="btn btn-outline-secondary btn-modern" @click="resetFilter">
              <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
            </button>
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-3 no-print">
          <div class="d-flex align-items-center gap-2 me-auto">
            <label class="text-muted small fw-semibold text-nowrap mb-0">Tampil</label>
            <select class="form-select form-select-sm" style="width:auto;min-width:100px" v-model.number="itemsPerPage" @change="resetPage">
              <option :value="10">10 / Halaman</option>
              <option :value="20">20 / Halaman</option>
              <option :value="50">50 / Halaman</option>
            </select>
          </div>
          <button class="btn btn-outline-primary btn-modern" @click="printReport">
            <i class="bi bi-printer me-1"></i> Print
          </button>
          <button class="btn btn-outline-danger btn-modern" @click="exportPdf">
            <i class="bi bi-filetype-pdf me-1"></i> Export PDF
          </button>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light fs-7">
              <tr class="print-table-header">
                <th colspan="100" class="py-2 text-center fw-bold" style="font-size:14px;text-transform:none;letter-spacing:0">
                  <div class="d-flex flex-column align-items-center">
                    <span>{{ tabTitle }}</span>
                    <small style="font-size:11px;font-weight:400;color:#666">Dicetak pada: {{ printDate }}</small>
                  </div>
                </th>
              </tr>
              <tr>
                <th class="py-3 ps-3">No</th>
                <th v-for="col in columns" :key="col.key" class="py-3" :class="col.align ? `text-${col.align} pe-3` : ''">
                  {{ col.label }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in paginatedData" :key="item.id || index">
                <td class="ps-3">{{ (currentPage - 1) * itemsPerPage + index + 1 }}</td>
                <td v-for="col in columns" :key="col.key" :class="col.align ? `text-${col.align} pe-3` : ''">
                  <template v-if="col.key === 'status' || col.key === 'status_label'">
                    <StatusBadge
                      :status="statusTone(item[col.key])"
                      :label="String(item[col.key] ?? '-')"
                      class="rounded-pill px-2 py-1"
                    />
                  </template>
                  <template v-else-if="col.key === 'selisih' || col.key === 'difference'">
                    <span :class="['fw-bold', Number(item[col.key]) < 0 ? 'text-danger' : 'text-success']">
                      {{ item[col.key] }}
                    </span>
                  </template>
                  <template v-else>
                    {{ displayValue(col, item[col.key]) }}
                  </template>
                </td>
              </tr>
              <tr v-if="paginatedData.length === 0">
                <td :colspan="columns.length + 1" class="text-center py-4 text-muted">Tidak ada data ditemukan.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4 no-print flex-wrap gap-2">
          <div class="text-muted small">
            Menampilkan {{ paginationStart }} sampai {{ paginationEnd }} dari {{ dataList.length }} entri
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
import { ref, computed, onMounted } from "vue";
import laporanService from "../../services/laporan";
import opnameService from "../../services/opname";
import { usePagination } from "../../composables/usePagination";
import { formatDate } from "../../utils/date";
import { exportReportPdf } from "../../utils/reportPdf";
import BasePagination from "../../components/BasePagination.vue";
import PageErrorAlert from "../../components/PageErrorAlert.vue";
import StatusBadge from "../../components/StatusBadge.vue";

const activeTab = ref("barangMasuk");
const dataList = ref([]);
const sessions = ref([]);
const loadError = ref(null);

const filter = ref({
  date_from: "",
  date_to: "",
  session_id: "",
});

const tabs = [
  { key: "barangMasuk", label: "Barang Masuk", icon: "bi bi-box-arrow-in-right" },
  { key: "barangKeluar", label: "Barang Keluar", icon: "bi bi-box-arrow-right" },
  { key: "stok", label: "Stok", icon: "bi bi-box-seam" },
  { key: "stokOpname", label: "Stock Opname", icon: "bi bi-clipboard-data" },
  { key: "barangExpired", label: "Barang Expired", icon: "bi bi-clock-history" },
];

const tabLabels = {
  barangMasuk: "Laporan Barang Masuk",
  barangKeluar: "Laporan Barang Keluar",
  stok: "Laporan Stok",
  stokOpname: "Laporan Stock Opname",
  barangExpired: "Laporan Barang Expired",
};

const tabTitle = computed(() => tabLabels[activeTab.value] || "Laporan");
const printDate = computed(() => formatDate(new Date(), "id-ID", { day: "2-digit", month: "long", year: "numeric" }));

const columnConfig = {
  barangMasuk: [
    { key: "reception_number", label: "Nomor Penerimaan" },
    { key: "reception_date", label: "Tanggal" },
    { key: "supplier_name", label: "Supplier" },
    { key: "total_quantity", label: "Jumlah Item", align: "end" },
    { key: "status", label: "Status", align: "center" },
  ],
  barangKeluar: [
    { key: "delivery_number", label: "Nomor Pengiriman" },
    { key: "delivery_date", label: "Tanggal" },
    { key: "total_quantity", label: "Jumlah Item", align: "end" },
    { key: "status", label: "Status", align: "center" },
  ],
  stok: [
    { key: "name", label: "Produk" },
    { key: "sku_code", label: "Kode Produk" },
    { key: "total_available", label: "Stok Tersedia", align: "end" },
    { key: "unit", label: "Satuan" },
    { key: "minimum_stock", label: "Stok Minimum", align: "end" },
  ],
  stokOpname: [
    { key: "product_name", label: "Produk" },
    { key: "batch_number", label: "Nomor Batch" },
    { key: "system_quantity", label: "Stok Sistem", align: "end" },
    { key: "physical_quantity", label: "Stok Fisik", align: "end" },
    { key: "difference", label: "Selisih", align: "end" },
  ],
  barangExpired: [
    { key: "product_name", label: "Produk" },
    { key: "batch_number", label: "Nomor Batch" },
    { key: "quantity", label: "Stok", align: "end" },
    { key: "expiry_date", label: "Tanggal Kedaluwarsa" },
    { key: "days_left", label: "Sisa Hari", align: "end" },
    { key: "status_label", label: "Status", align: "center" },
  ],
};

const columns = computed(() => columnConfig[activeTab.value] || []);
const showFilter = computed(() => activeTab.value !== "stok");

const DATE_COLUMNS = new Set(["reception_date", "delivery_date", "expiry_date"]);

const displayValue = (column, value) => (DATE_COLUMNS.has(column.key) ? formatDate(value) : value);

const statusTone = (value) => {
  const normalized = String(value ?? "").trim().toUpperCase();
  if (["COMPLETED", "AVAILABLE", "AKTIF", "OK", "SELESAI"].includes(normalized)) return "AKTIF";
  if (["AKAN EXPIRED", "NEAR EXPIRY", "PENDING", "WARNING"].includes(normalized)) return "WARNING";
  if (normalized === "DRAFT") return "DRAFT";
  return "EXPIRED";
};

const tabLoaders = {
  barangMasuk: { fetch: laporanService.barangMasuk, pick: (data) => data.headers || [], withDates: true },
  barangKeluar: { fetch: laporanService.barangKeluar, pick: (data) => data.headers || [], withDates: true },
  stok: { fetch: laporanService.stok, pick: (data) => data.products || [] },
  stokOpname: { fetch: laporanService.stokOpname, pick: (data) => data.details || [], withSession: true },
  barangExpired: { fetch: laporanService.expired, pick: (data) => data.items || [], withDates: true },
};

const loadSessions = async () => {
  try {
    const res = await opnameService.getSessions();
    if (!res.data?.status) throw new Error(res.data?.message || "Gagal memuat sesi opname");
    sessions.value = res.data.data || [];
    if (sessions.value.length > 0 && !filter.value.session_id) {
      filter.value.session_id = sessions.value[0].id_opname;
    }
  } catch (err) {
    sessions.value = [];
    loadError.value = err;
  }
};

const loadData = async () => {
  resetPage();
  loadError.value = null;
  const loader = tabLoaders[activeTab.value];
  if (!loader) {
    dataList.value = [];
    return;
  }
  const params = {};
  if (loader.withDates) {
    if (filter.value.date_from) params.date_from = filter.value.date_from;
    if (filter.value.date_to) params.date_to = filter.value.date_to;
  }
  if (loader.withSession) {
    if (!filter.value.session_id) {
      dataList.value = [];
      return;
    }
    params.id_opname = filter.value.session_id;
  }
  try {
    const res = await loader.fetch(params);
    if (!res.data?.status) throw new Error(res.data?.message || "Gagal memuat data laporan");
    dataList.value = loader.pick(res.data.data || {});
  } catch (err) {
    dataList.value = [];
    loadError.value = err;
  }
};

const switchTab = (tab) => {
  activeTab.value = tab;
  resetPage();
  dataList.value = [];
  if (tab === "stokOpname") loadSessions();
  loadData();
};

const resetFilter = () => {
  filter.value.date_from = "";
  filter.value.date_to = "";
  filter.value.session_id = sessions.value[0]?.id_opname || "";
  loadData();
};

const retryLoad = () => {
  if (activeTab.value === "stokOpname") loadSessions();
  loadData();
};

const printReport = () => {
  window.print();
};

const exportPdf = async () => {
  try {
    await exportReportPdf({
      rows: [...dataList.value],
      columns: columns.value,
      title: tabTitle.value,
      dateLabel: printDate.value,
      reportKey: activeTab.value,
    });
  } catch (err) {
    loadError.value = err;
  }
};

const {
  currentPage,
  itemsPerPage,
  totalPages,
  visiblePages,
  paginatedItems: paginatedData,
  paginationStart,
  paginationEnd,
  changePage,
  resetPage,
} = usePagination(dataList);

onMounted(() => {
  loadData();
});
</script>

<style scoped>
.laporan-page {
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

.form-select {
  border-radius: 8px;
  border-color: #cbd5e1;
  padding: 10px 14px;
}

.form-control:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

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

.print-table-header {
  display: none;
}

.print-watermark {
  display: none;
}

@media print {
  @page {
    size: landscape;
    margin: 0;
  }

  body {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  .print-table-header {
    display: table-row !important;
  }

  .print-table-header th {
    border-bottom: 2px solid #333 !important;
  }

  .no-print {
    display: none !important;
  }

  .print-watermark {
    display: flex !important;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 999;
    opacity: 0.08;
  }

  .print-watermark img {
    width: 250px;
    height: auto;
    margin-bottom: 10px;
  }

  .print-watermark span {
    font-size: 28px;
    font-weight: 700;
    color: #000;
  }

  .laporan-page {
    padding: 0 !important;
    background: #fff !important;
    min-height: auto !important;
  }

  .card {
    box-shadow: none !important;
    border: none !important;
  }

  .card-body {
    padding: 0 !important;
  }

  .table {
    color: #000 !important;
    font-size: 12px;
  }

  .table th {
    background: #f0f0f0 !important;
    color: #000 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  .badge {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
}
</style>
