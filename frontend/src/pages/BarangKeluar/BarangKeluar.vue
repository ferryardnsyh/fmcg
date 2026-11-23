<template>
  <div class="barang-keluar-page">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div class="d-flex align-items-center">
        <div class="header-icon-box me-3">
          <i class="bi bi-box-arrow-right fs-4 text-primary"></i>
        </div>
        <div>
          <h4 class="mb-0 fw-bold text-dark">Barang Keluar</h4>
          <p class="text-muted small mb-0">Manajemen Pengeluaran Barang</p>
        </div>
      </div>
      <button class="btn btn-primary btn-modern" @click="openAddModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Pengeluaran
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
      <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm rounded-15 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Total Pengeluaran</p>
              <h3 class="fw-bold mb-0 text-dark">{{ stats.total }}</h3>
            </div>
            <div class="stat-icon bg-primary-subtle text-primary">
              <i class="bi bi-box-arrow-right fs-4"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm rounded-15 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Hari Ini</p>
              <h3 class="fw-bold mb-0 text-info">{{ stats.hariIni }}</h3>
            </div>
            <div class="stat-icon bg-info-subtle text-info">
              <i class="bi bi-calendar-day fs-4"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm rounded-15 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Selesai</p>
              <h3 class="fw-bold mb-0 text-success">{{ stats.completed }}</h3>
            </div>
            <div class="stat-icon bg-success-subtle text-success">
              <i class="bi bi-check-circle fs-4"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm rounded-15 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Draft</p>
              <h3 class="fw-bold mb-0 text-warning">{{ stats.draft }}</h3>
            </div>
            <div class="stat-icon bg-warning-subtle text-warning">
              <i class="bi bi-pencil-square fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Card Table -->
    <div class="card border-0 shadow-sm rounded-15 mb-4">
      <div class="card-body p-4">
        <PageErrorAlert :error="loadError" fallback="Gagal memuat data pengeluaran." @retry="loadAll" />
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
                placeholder="Cari pengiriman..."
                v-model="searchQuery"
              />
            </div>
          </div>
          <div class="col-md-auto d-flex gap-2 align-items-center flex-wrap">
            <div class="d-flex align-items-center gap-2">
              <label class="text-muted small fw-semibold text-nowrap mb-0">Tampil</label>
              <select class="form-select form-select-sm" style="width:auto;min-width:100px" v-model.number="itemsPerPage" @change="resetPage()">
                <option :value="10">10 / Halaman</option>
                <option :value="20">20 / Halaman</option>
                <option :value="50">50 / Halaman</option>
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

        <!-- Table -->
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase fs-7">
              <tr>
                <th class="py-3 ps-3">No</th>
                <th class="py-3">Nomor Pengiriman</th>
                <th class="py-3">Tanggal Keluar</th>
                <th class="py-3">Status</th>
                <th class="py-3 text-center pe-3">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in paginatedData" :key="item.id_outbound">
                <td class="ps-3">
                  {{ (currentPage - 1) * itemsPerPage + index + 1 }}
                </td>
                <td class="fw-semibold text-primary">
                  {{ item.delivery_number }}
                </td>
                <td>{{ formatDate(item.delivery_date) }}</td>
                <td>
                  <StatusBadge
                    :status="
                      item.status === 'COMPLETED'
                        ? 'SELESAI'
                        : item.status === 'DRAFT'
                        ? 'PENDING'
                        : item.status === 'CANCELLED'
                        ? 'EXPIRED'
                        : item.status
                    "
                    :label="statusLabel(item.status)"
                    class="rounded-pill px-2 py-1"
                  />
                </td>
                <td class="text-center pe-3">
                  <button
                    v-if="item.status === 'DRAFT'"
                    class="btn btn-sm btn-light text-warning action-btn me-1"
                    @click="openEditModal(item)"
                    title="Edit"
                  >
                    <i class="bi bi-pencil-fill"></i>
                  </button>
                  <button
                    v-if="item.status === 'DRAFT'"
                    class="btn btn-sm btn-light text-success action-btn me-1"
                    @click="completeOutbound(item)"
                    title="Selesaikan"
                  >
                    <i class="bi bi-check-lg"></i>
                  </button>
                  <button
                    v-if="item.status === 'DRAFT'"
                    class="btn btn-sm btn-light text-danger action-btn"
                    @click="deleteOutbound(item)"
                    title="Hapus"
                  >
                    <i class="bi bi-trash-fill"></i>
                  </button>
                  <span v-else class="text-muted small">-</span>
                </td>
              </tr>
              <tr v-if="filteredData.length === 0">
                <td colspan="6" class="text-center py-4 text-muted">
                  Tidak ada data pengeluaran ditemukan.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
          <div class="text-muted small">
            Menampilkan {{ paginationStart }} sampai {{ paginationEnd }} dari
            {{ filteredData.length }} entri
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

    <!-- Modal Form (Tambah / Edit) -->
    <div
      class="modal fade"
      id="outboundModal"
      tabindex="-1"
      aria-hidden="true"
      ref="outboundModalRef"
    >
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-15 shadow">
          <div class="modal-header border-0 pb-0 pt-4 px-4">
            <h5 class="modal-title fw-bold">
              {{ isEditMode ? "Edit Pengeluaran" : "Tambah Pengeluaran" }}
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="saveOutbound">
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold"
                    >Nomor Pengiriman</label
                  >
                  <input
                    type="text"
                    class="form-control"
                    v-model="form.delivery_number"
                    placeholder="Otomatis"
                    readonly
                  />                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold"
                    >Tanggal Keluar <span class="text-danger">*</span></label
                  >
                  <input
                    type="date"
                    class="form-control"
                    v-model="form.delivery_date"
                    :min="todayIso()"
                    required
                  />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Notes</label>
                  <textarea
                    class="form-control"
                    v-model="form.notes"
                    rows="2"
                    inputmode="text"
                    maxlength="65535"
                    placeholder="Catatan pengiriman..."
                  ></textarea>
                </div>
              </div>

              <hr />
              <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <label class="form-label small fw-semibold mb-0"
                  >Detail Barang</label
                >
                <button
                  type="button"
                  class="btn btn-sm btn-outline-primary btn-modern"
                  @click="addDetailRow"
                >
                  <i class="bi bi-plus-lg me-1"></i> Tambah Barang
                </button>
              </div>

              <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                  <thead class="table-light text-uppercase fs-7">
                    <tr>
                      <th class="py-2" style="width: 30%">Produk</th>
                      <th class="py-2" style="width: 30%">Batch No</th>
                      <th class="py-2" style="width: 20%">Jumlah</th>
                      <th class="py-2 text-center" style="width: 50px">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, idx) in details" :key="row.rowKey">
                      <td>
                        <select
                          class="form-select form-select-sm"
                          v-model.number="row.id_product"
                          @change="onProductChange(idx)"
                          required
                        >
                          <option value="" disabled>Pilih Produk</option>
                          <option
                            v-for="p in products"
                            :key="p.id_product"
                            :value="p.id_product"
                          >
                            {{ p.name }}
                          </option>
                        </select>
                      </td>
                      <td>
                        <select
                          class="form-select form-select-sm"
                          v-model.number="row.id_batch"
                          @change="onBatchChange(idx)"
                          required
                          :disabled="!row.id_product"
                        >
                          <option value="" disabled>
                            {{ row.id_product ? 'Pilih Batch' : 'Pilih produk dulu' }}
                          </option>
                          <option
                            v-for="b in row.availableBatches"
                            :key="b.id_batch"
                            :value="b.id_batch"
                          >
                            {{ b.batch_number || 'Batch #' + b.id_batch }}
                             (Stok: {{ toNumericQuantity(b.quantity) }})
                          </option>
                        </select>
                      </td>
                      <td>
                        <input
                          type="number"
                          class="form-control form-control-sm"
                          v-model.number="row.quantity"
                          min="1"
                          :max="quantityLimit(row)"
                          step="1"
                          inputmode="numeric"
                          required
                          :class="{ 'is-invalid': stockQuantityError(row) }"
                        />
                        <div
                          v-if="stockQuantityError(row)"
                          class="invalid-feedback d-block"
                        >
                          {{ stockQuantityError(row) }}
                        </div>
                      </td>
                      <td class="text-center">
                        <button
                          type="button"
                          class="btn btn-sm btn-light text-danger action-btn"
                          @click="removeDetailRow(idx)"
                        >
                          <i class="bi bi-trash-fill"></i>
                        </button>
                      </td>
                    </tr>
                    <tr v-if="details.length === 0">
                      <td colspan="4" class="text-center py-3 text-muted">
                        Belum ada barang. Klik "Tambah Barang" untuk menambahkan.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div
                class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top"
              >
                <button
                  type="button"
                  class="btn btn-secondary btn-modern px-4"
                  data-bs-dismiss="modal"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  class="btn btn-primary btn-modern px-4"
                  :disabled="saving"
                >
                  <span
                    v-if="saving"
                    class="spinner-border spinner-border-sm me-1"
                  ></span>
                  {{ isEditMode ? "Update" : "Simpan" }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import * as bootstrap from "bootstrap";
import Swal from "sweetalert2";
import outboundService from "../../services/outbound";
import productService from "../../services/product";
import batchService from "../../services/batch";
import { usePagination } from "../../composables/usePagination";
import { useConfirm } from "../../composables/useConfirm";
import {
  availableBatchesFor,
  buildOutboundPayload,
  createOutboundDetailRow,
  getStockLimit,
  stockQuantityError,
  toNumericQuantity,
  validateOutboundDetails,
} from "../../composables/useOutboundForm";
import { MAX_QTY } from "../../constants/app";
import { formatDate, todayIso } from "../../utils/date";
import BasePagination from "../../components/BasePagination.vue";
import PageErrorAlert from "../../components/PageErrorAlert.vue";
import StatusBadge from "../../components/StatusBadge.vue";

const quantityLimit = (row) => Math.min(MAX_QTY, getStockLimit(row));

const { confirm } = useConfirm();
const searchQuery = ref("");
const loadError = ref(null);

const isEditMode = ref(false);
const saving = ref(false);
const editId = ref(null);

const outboundModalRef = ref(null);
let modalInstance = null;

const outbounds = ref([]);
const products = ref([]);
const allBatches = ref([]);

const form = ref({
  delivery_number: "",
  delivery_date: "",
  notes: "",
});

const details = ref([]);

const stats = computed(() => {
  const items = outbounds.value;
  const today = todayIso();
  return {
    total: items.length,
    hariIni: items.filter(
      (o) => o.delivery_date && o.delivery_date.startsWith(today)
    ).length,
    completed: items.filter((o) => o.status === "COMPLETED").length,
    draft: items.filter((o) => o.status === "DRAFT").length,
  };
});

const filteredData = computed(() => {
  if (!searchQuery.value) return outbounds.value;
  const q = searchQuery.value.toLowerCase();
  return outbounds.value.filter(
    (o) =>
      (o.delivery_number && o.delivery_number.toLowerCase().includes(q))
  );
});

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
} = usePagination(filteredData);

watch(searchQuery, resetPage);

const statusLabel = (status) => {
  const map = {
    DRAFT: "Draft",
    COMPLETED: "Selesai",
    CANCELLED: "Dibatalkan",
  };
  return map[status] || status;
};

const loadOutbounds = async () => {
  try {
    const response = await outboundService.getList();
    if (response.data.status) {
      outbounds.value = response.data.data;
    }
  } catch (err) {
    outbounds.value = [];
    loadError.value = err;
  }
};

const loadProducts = async () => {
  try {
    const response = await productService.getProducts();
    if (response.data.status) {
      products.value = response.data.data;
    }
  } catch (err) {
    products.value = [];
    loadError.value = err;
  }
};

const loadBatches = async () => {
  try {
    const response = await batchService.getBatches();
    if (response.data.status) {
      allBatches.value = response.data.data;
    }
  } catch (err) {
    allBatches.value = [];
    loadError.value = err;
  }
};

const loadAll = () => {
  loadError.value = null;
  return Promise.all([loadOutbounds(), loadProducts(), loadBatches()]);
};

onMounted(() => {
  modalInstance = new bootstrap.Modal(outboundModalRef.value);
  loadAll();
});

const createDetailRow = () => createOutboundDetailRow();

const addDetailRow = () => {
  details.value.push(createDetailRow());
};

const removeDetailRow = (idx) => {
  details.value.splice(idx, 1);
};

const onProductChange = (idx) => {
  const row = details.value[idx];
  row.id_batch = "";
  row.quantity = 1;
  row.maxQuantity = 0;

  if (!row.id_product) {
    row.availableBatches = [];
    return;
  }

  row.availableBatches = availableBatchesFor(allBatches.value, row.id_product);
};

const onBatchChange = (idx) => {
  const row = details.value[idx];
  if (!row.id_batch) {
    row.maxQuantity = 0;
    row.quantity = 1;
    return;
  }
  const batch = row.availableBatches.find(
    (b) => String(b.id_batch) === String(row.id_batch)
  );
  row.maxQuantity = batch ? toNumericQuantity(batch.quantity) : 0;
  if (row.quantity > row.maxQuantity) {
    row.quantity = row.maxQuantity;
  }
};

const openAddModal = () => {
  isEditMode.value = false;
  editId.value = null;

  form.value = {
    delivery_number: "",
    delivery_date: todayIso(),
    notes: "",
  };
  details.value = [];

  modalInstance.show();
};

const openEditModal = async (item) => {
  isEditMode.value = true;
  editId.value = item.id_outbound;

  form.value = {
    delivery_number: item.delivery_number || "",
    delivery_date: item.delivery_date
      ? item.delivery_date.split("T")[0]
      : "",
    notes: item.notes || "",
  };

  details.value = [];

  try {
    const response = await outboundService.getById(item.id_outbound);
    const data = response.data?.data || item;
    const itemDetails = data.details || [];
    if (itemDetails.length > 0) {
      details.value = itemDetails.map((d) => {
        const sourceDetail = {
          id_product: d.id_product,
          id_batch: d.id_batch,
          quantity: d.quantity,
        };
        const row = createOutboundDetailRow(sourceDetail);
        const match = allBatches.value.find(
          (b) => String(b.id_batch) === String(d.id_batch)
        );
        row.maxQuantity = match ? toNumericQuantity(match.quantity) : 0;
        row.availableBatches = availableBatchesFor(allBatches.value, row.id_product);
        return row;
      });
    }
  } catch (err) {
    Swal.fire("Gagal!", "Gagal memuat data pengiriman.", "error");
    return;
  }

  modalInstance.show();
};

const saveOutbound = async () => {
  const validationMessage = validateOutboundDetails(details.value);
  if (validationMessage) {
    Swal.fire("Validasi", validationMessage, "warning");
    return;
  }

  saving.value = true;
  try {
    const payload = buildOutboundPayload(form.value, details.value);

    if (isEditMode.value) {
      await outboundService.update(editId.value, payload);
      Swal.fire("Berhasil!", "Pengeluaran berhasil diupdate.", "success");
    } else {
      await outboundService.create(payload);
      Swal.fire("Berhasil!", "Pengeluaran berhasil ditambahkan.", "success");
    }

    await loadOutbounds();
    modalInstance.hide();
  } catch (err) {
    loadError.value = err;
    const msg = err.response?.data?.message || "Terjadi kesalahan";
    Swal.fire("Gagal!", msg, "error");
  } finally {
    saving.value = false;
  }
};

const completeOutbound = async (item) => {
  const confirmed = await confirm({
    title: "Selesaikan Pengeluaran?",
    text: `Apakah Anda yakin ingin menyelesaikan pengiriman ${item.delivery_number}?`,
    icon: "question",
    confirmButtonText: "Ya, Selesaikan!",
    cancelButtonText: "Batal",
    swal: { confirmButtonColor: "#10b981", cancelButtonColor: "#6c757d" },
  });
  if (!confirmed) return;

  try {
    await outboundService.complete(item.id_outbound);
    Swal.fire("Selesai!", "Status pengeluaran berhasil diupdate.", "success");
    await loadOutbounds();
  } catch (err) {
    loadError.value = err;
    const msg = err.response?.data?.message || "Gagal complete pengeluaran.";
    Swal.fire("Gagal!", msg, "error");
  }
};

const deleteOutbound = async (item) => {
  const confirmed = await confirm({
    title: "Hapus Pengeluaran?",
    text: `Apakah Anda yakin ingin menghapus ${item.delivery_number}?`,
    confirmButtonText: "Ya, Hapus!",
    cancelButtonText: "Batal",
    swal: { confirmButtonColor: "#e53e3e", cancelButtonColor: "#6c757d" },
  });
  if (!confirmed) return;

  try {
    await outboundService.delete(item.id_outbound);
    Swal.fire("Dihapus!", "Pengeluaran berhasil dihapus.", "success");
    await loadOutbounds();
  } catch (err) {
    loadError.value = err;
    const msg = err.response?.data?.message || "Gagal menghapus pengeluaran.";
    Swal.fire("Gagal!", msg, "error");
  }
};

const refreshData = () => {
  searchQuery.value = "";
  resetPage();
  loadOutbounds();
};
</script>

<style scoped>
.barang-keluar-page {
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

.action-btn {
  border-radius: 6px;
  width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.action-btn:hover {
  transform: scale(1.08);
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

.form-select-sm {
  padding: 6px 10px;
  font-size: 0.875rem;
}

.form-control-sm {
  padding: 6px 10px;
  font-size: 0.875rem;
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
