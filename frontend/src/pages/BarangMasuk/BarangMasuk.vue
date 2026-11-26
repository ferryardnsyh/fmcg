<template>
  <div class="barang-masuk-page">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div class="d-flex align-items-center">
        <div class="header-icon-box me-3">
          <i class="bi bi-box-arrow-in-right fs-4 text-primary"></i>
        </div>
        <div>
          <h4 class="mb-0 fw-bold text-dark">Barang Masuk</h4>
          <p class="text-muted small mb-0">Manajemen Penerimaan Barang</p>
        </div>
      </div>
      <button class="btn btn-primary btn-modern" @click="openAddModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Barang Masuk
      </button>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm rounded-15 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Total Penerimaan</p>
              <h3 class="fw-bold mb-0 text-dark">{{ stats.total }}</h3>
            </div>
            <div class="stat-icon bg-primary-subtle text-primary">
              <i class="bi bi-box-seam fs-4"></i>
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
              <i class="bi bi-calendar-check fs-4"></i>
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
      <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm rounded-15 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Selesai</p>
              <h3 class="fw-bold mb-0 text-success">{{ stats.completed }}</h3>
            </div>
            <div class="stat-icon bg-success-subtle text-success">
              <i class="bi bi-check-all fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm rounded-15 mb-4">
      <div class="card-body p-4">
        <PageErrorAlert :error="loadError" fallback="Gagal memuat data penerimaan." @retry="loadAll" />
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
                placeholder="Cari penerimaan..."
                v-model="searchQuery"
              />
            </div>
          </div>
          <div class="col-md-auto d-flex gap-2 align-items-center flex-wrap">
            <div class="d-flex align-items-center gap-2">
              <label class="text-muted small fw-semibold text-nowrap mb-0">Tampil</label>
              <select class="form-select form-select-sm" style="width:auto;min-width:100px" v-model.number="itemsPerPage" @change="resetPage()">
                <option v-for="size in PAGE_SIZE_OPTIONS" :key="size" :value="size">{{ size }} / Halaman</option>
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
                <th class="py-3">Nomor Penerimaan</th>
                <th class="py-3">Supplier</th>
                <th class="py-3">Tanggal</th>
                <th class="py-3">Tanggal Kedaluwarsa</th>
                <th class="py-3">Status</th>
                <th class="py-3 text-center pe-3">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in paginatedData" :key="item.id_inbound">
                <td class="ps-3">{{ (currentPage - 1) * itemsPerPage + index + 1 }}</td>
                <td class="fw-semibold text-primary">{{ item.reception_number }}</td>
                <td class="fw-semibold">{{ item.supplier_name || '-' }}</td>
                 <td>{{ formatDate(item.reception_date) }}</td>
                <td>{{ item.expiry_dates || '-' }}</td>
                <td>
                  <StatusBadge
                    :status="STATUS_BADGES[item.status] || item.status"
                    :label="statusLabel(item.status)"
                    class="rounded-pill px-3 py-1"
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
                    @click="completeInbound(item)"
                    title="Selesaikan"
                  >
                    <i class="bi bi-check-lg"></i>
                  </button>
                  <button
                    v-if="item.status === 'DRAFT'"
                    class="btn btn-sm btn-light text-danger action-btn"
                    @click="deleteInbound(item)"
                    title="Hapus"
                  >
                    <i class="bi bi-trash-fill"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="paginatedData.length === 0">
                <td colspan="7" class="text-center py-4 text-muted">Tidak ada data penerimaan ditemukan.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
          <div class="text-muted small">
            Menampilkan {{ paginationStart }} sampai {{ paginationEnd }} dari {{ filteredData.length }} entri
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

    <div
      class="modal fade"
      id="inboundModal"
      tabindex="-1"
      aria-hidden="true"
      ref="inboundModalRef"
    >
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-15 shadow">
          <div class="modal-header border-0 pb-0 pt-4 px-4">
            <h5 class="modal-title fw-bold">
              {{ isEditMode ? "Edit Barang Masuk" : "Tambah Barang Masuk" }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="saveInbound">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Nomor Penerimaan</label>
                  <input
                    type="text"
                    class="form-control"
                    :value="form.reception_number"
                    placeholder="Otomatis"
                    readonly
                  />
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Nomor PO <span class="text-danger">*</span></label>
                  <input
                    type="text"
                    inputmode="text"
                    autocomplete="off"
                    class="form-control"
                    v-model="form.po_number"
                    maxlength="50"
                    :pattern="DOCUMENT_PATTERN"
                    placeholder="Contoh: PO-001"
                    required
                  />
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Nomor Surat Jalan <span class="text-danger">*</span></label>
                  <input
                    type="text"
                    inputmode="text"
                    autocomplete="off"
                    class="form-control"
                    v-model="form.delivery_note_number"
                    maxlength="50"
                    :pattern="DOCUMENT_PATTERN"
                    placeholder="Contoh: SJ-001"
                    required
                  />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Supplier <span class="text-danger">*</span></label>
                  <select class="form-select" v-model.number="form.id_supplier" required>
                    <option value="">-- Pilih Supplier --</option>
                    <option v-for="s in suppliers" :key="s.id_supplier" :value="s.id_supplier">{{ s.name }}</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Tanggal Masuk <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" v-model="form.reception_date" :min="todayIso()" required />
                </div>
                <div class="col-12">
                  <label class="form-label small fw-semibold">Notes</label>
                  <textarea class="form-control" v-model="form.notes" rows="2" inputmode="text" maxlength="65535" placeholder="Catatan penerimaan"></textarea>
                </div>
              </div>

              <hr class="my-4" />
              <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h6 class="fw-bold mb-0">Detail Barang</h6>
                <button type="button" class="btn btn-sm btn-outline-primary btn-modern" @click="addDetailRow">
                  <i class="bi bi-plus-lg me-1"></i> Tambah Barang
                </button>
              </div>

              <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                  <thead class="table-light fs-7">
                    <tr>
                      <th class="py-2">No</th>
                      <th class="py-2">Produk <span class="text-danger">*</span></th>
                      <th class="py-2">Nomor Batch <span class="text-danger">*</span></th>
                      <th class="py-2" style="width:120px">Jumlah <span class="text-danger">*</span></th>
                      <th class="py-2" style="width:150px">Tanggal Kedaluwarsa <span class="text-danger">*</span></th>
                      <th class="py-2">Rak <span class="text-danger">*</span></th>
                      <th class="py-2 text-center" style="width:50px">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(detail, idx) in details" :key="detail.rowKey">
                      <td class="text-center">{{ idx + 1 }}</td>
                      <td>
                        <select class="form-select form-select-sm" v-model.number="detail.id_product" required :disabled="!form.id_supplier">
                          <option value="">-- Produk --</option>
                          <option v-for="p in products" :key="p.id_product" :value="p.id_product">{{ p.name }}</option>
                        </select>
                      </td>
                      <td>
                        <input type="text" inputmode="text" autocomplete="off" maxlength="100" :pattern="DOCUMENT_PATTERN" class="form-control form-control-sm" v-model="detail.batch_number" placeholder="Nomor batch/lot" required />
                      </td>
                      <td>
                        <input type="number" class="form-control form-control-sm" v-model.number="detail.quantity" min="1" :max="MAX_QTY" step="1" inputmode="numeric" required />
                      </td>
                      <td>
                        <input type="date" class="form-control form-control-sm" v-model="detail.expiry_date" :min="todayIso()" required />
                      </td>
                      <td>
                        <select class="form-select form-select-sm" v-model.number="detail.id_rack" required>
                          <option value="">-- Rak --</option>
                          <option v-for="r in rackLocations" :key="r.id_rack" :value="r.id_rack">{{ r.rack_code }}{{ r.name ? ' - ' + r.name : '' }}</option>
                        </select>
                      </td>
                      <td class="text-center">
                        <button
                          type="button"
                          class="btn btn-sm btn-light text-danger action-btn"
                          @click="removeDetailRow(idx)"
                          title="Hapus"
                        >
                          <i class="bi bi-trash-fill"></i>
                        </button>
                      </td>
                    </tr>
                    <tr v-if="details.length === 0">
                      <td colspan="7" class="text-center py-3 text-muted">
                        Belum ada detail barang. Klik "Tambah Barang" untuk menambahkan.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
                <button
                  type="button"
                  class="btn btn-secondary btn-modern px-4"
                  data-bs-dismiss="modal"
                >
                  Batal
                </button>
                <button type="submit" class="btn btn-primary btn-modern px-4" :disabled="saving">
                  <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
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
import inboundService from "../../services/inbound";
import supplierService from "../../services/supplier";
import masterService from "../../services/master";
import { getActionErrorMessage } from "../../services/apiNotifier";
import { usePagination } from "../../composables/usePagination";
import { useConfirm } from "../../composables/useConfirm";
import {
  buildInboundPayload,
  createInboundDetailRow,
  validateInboundForm,
} from "../../composables/useInboundForm";
import { MAX_QTY, PAGE_SIZE_OPTIONS } from "../../constants/app";
import { formatDate, todayIso } from "../../utils/date";
import { DOCUMENT_PATTERN } from "../../utils/validation";
import { STATUS_BADGES, STATUS_LABELS } from "../../utils/status";
import BasePagination from "../../components/BasePagination.vue";
import PageErrorAlert from "../../components/PageErrorAlert.vue";
import StatusBadge from "../../components/StatusBadge.vue";

const { confirm } = useConfirm();
const searchQuery = ref("");
const loadError = ref(null);

const isEditMode = ref(false);
const saving = ref(false);
const editId = ref(null);

const inboundModalRef = ref(null);
let modalInstance = null;

const inboundList = ref([]);
const suppliers = ref([]);
const products = ref([]);
const rackLocations = ref([]);

const form = ref({
  reception_number: "",
  po_number: "",
  delivery_note_number: "",
  id_supplier: "",
  reception_date: todayIso(),
  notes: "",
});

const details = ref([]);

const statusLabel = (status) => STATUS_LABELS[status] || status;

const stats = computed(() => {
  const today = todayIso();
  return {
    total: inboundList.value.length,
    hariIni: inboundList.value.filter((i) => i.reception_date === today).length,
    draft: inboundList.value.filter((i) => i.status === "DRAFT").length,
    completed: inboundList.value.filter((i) => i.status === "COMPLETED").length,
  };
});

const filteredData = computed(() => {
  if (!searchQuery.value) return inboundList.value;
  const q = searchQuery.value.toLowerCase();
  return inboundList.value.filter(
    (i) =>
      i.reception_number?.toLowerCase().includes(q) ||
      i.supplier_name?.toLowerCase().includes(q) ||
      i.status?.toLowerCase().includes(q),
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

const loadInbound = async () => {
  try {
    const response = await inboundService.getList();
    if (response.data.status) {
      inboundList.value = response.data.data;
    }
  } catch (err) {
    inboundList.value = [];
    loadError.value = err;
  }
};

const loadSuppliers = async () => {
  try {
    const response = await supplierService.getSuppliers();
    if (response.data.status) {
      suppliers.value = response.data.data;
    }
  } catch (err) {
    suppliers.value = [];
    loadError.value = err;
  }
};

const loadProducts = async (supplierId) => {
  if (!supplierId) {
    products.value = [];
    return;
  }
  try {
    const response = await inboundService.getProductsBySupplier(supplierId);
    if (response.data.status) {
      products.value = response.data.data;
    }
  } catch (err) {
    products.value = [];
    loadError.value = err;
  }
};

const loadRackLocations = async () => {
  try {
    const response = await masterService.getRackLocations();
    if (response.data.status) {
      rackLocations.value = response.data.data;
    }
  } catch (err) {
    rackLocations.value = [];
    loadError.value = err;
  }
};

const loadAll = () => {
  loadError.value = null;
  return Promise.all([loadInbound(), loadSuppliers(), loadRackLocations()]);
};

onMounted(() => {
  modalInstance = new bootstrap.Modal(inboundModalRef.value);
  loadAll();
});

watch(() => form.value.id_supplier, (newVal) => {
  products.value = [];
  details.value.forEach((d) => {
    d.id_product = "";
  });
  if (newVal) {
    loadProducts(newVal);
  }
});

const resetForm = () => {
  form.value = {
    reception_number: "",
    po_number: "",
    delivery_note_number: "",
    id_supplier: "",
    reception_date: todayIso(),
    notes: "",
  };
  details.value = [];
  products.value = [];
};

const addDetailRow = () => {
  details.value.push(createInboundDetailRow());
};

const removeDetailRow = (idx) => {
  details.value.splice(idx, 1);
};

const openAddModal = () => {
  isEditMode.value = false;
  editId.value = null;
  resetForm();
  addDetailRow();
  modalInstance.show();
};

const openEditModal = async (item) => {
  isEditMode.value = true;
  editId.value = item.id_inbound;
  try {
    const response = await inboundService.getById(item.id_inbound);
    if (response.data.status) {
      const data = response.data.data;
      form.value = {
        reception_number: data.reception_number || "",
        po_number: data.po_number || "",
        delivery_note_number: data.delivery_note_number || "",
        id_supplier: data.id_supplier || "",
        reception_date: data.reception_date || "",
        notes: data.notes || "",
      };
      if (data.id_supplier) {
        await loadProducts(data.id_supplier);
      }
      details.value = (data.details || []).map((d) => createInboundDetailRow({
        id_inbound_detail: d.id_inbound_detail || null,
        id_product: d.id_product || "",
        batch_number: d.batch_number || "",
        quantity: d.quantity || 1,
        expiry_date: d.expiry_date || "",
        id_rack: d.id_rack || "",
      }));
    }
  } catch (err) {
    Swal.fire("Gagal!", "Gagal memuat data penerimaan.", "error");
    return;
  }
  modalInstance.show();
};

const validateForm = () => {
  const message = validateInboundForm(form.value, details.value);
  if (!message) return true;
  Swal.fire("Validasi", message, "warning");
  return false;
};

const saveInbound = async () => {
  if (!validateForm()) return;
  saving.value = true;
  try {
    const payload = buildInboundPayload(form.value, details.value);

    if (isEditMode.value) {
      await inboundService.update(editId.value, payload);
      Swal.fire("Berhasil!", "Penerimaan barang berhasil diupdate.", "success");
    } else {
      await inboundService.create(payload);
      Swal.fire("Berhasil!", "Penerimaan barang berhasil ditambahkan.", "success");
    }

    await loadInbound();
    modalInstance.hide();
  } catch (err) {
    loadError.value = err;
    const msg = getActionErrorMessage(err, "Terjadi kesalahan");
    Swal.fire("Gagal!", msg, "error");
  } finally {
    saving.value = false;
  }
};

const completeInbound = async (item) => {
  const confirmed = await confirm({
    title: "Selesaikan Penerimaan?",
    text: `Terima barang masuk nomor ${item.reception_number}?`,
    icon: "question",
    confirmButtonText: "Ya, Selesaikan!",
    cancelButtonText: "Batal",
    swal: { confirmButtonColor: "#28a745", cancelButtonColor: "#6c757d" },
  });
  if (!confirmed) return;

  try {
    await inboundService.complete(item.id_inbound);
    Swal.fire("Selesai!", "Status penerimaan berubah menjadi Selesai.", "success");
    await loadInbound();
  } catch (err) {
    loadError.value = err;
    const msg = getActionErrorMessage(err, "Gagal menyelesaikan penerimaan.");
    Swal.fire("Gagal!", msg, "error");
  }
};

const deleteInbound = async (item) => {
  const confirmed = await confirm({
    title: "Hapus Penerimaan?",
    text: `Apakah Anda yakin ingin menghapus penerimaan ${item.reception_number}?`,
    confirmButtonText: "Ya, Hapus!",
    cancelButtonText: "Batal",
    swal: { confirmButtonColor: "#e53e3e", cancelButtonColor: "#6c757d" },
  });
  if (!confirmed) return;

  try {
    await inboundService.delete(item.id_inbound);
    Swal.fire("Dihapus!", "Penerimaan berhasil dihapus.", "success");
    await loadInbound();
  } catch (err) {
    loadError.value = err;
    const msg = getActionErrorMessage(err, "Gagal menghapus penerimaan.");
    Swal.fire("Gagal!", msg, "error");
  }
};

const refreshData = () => {
  searchQuery.value = "";
  resetPage();
  loadInbound();
};
</script>

<style scoped>
.barang-masuk-page {
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
  transition: transform 0.2s ease, box-shadow 0.2s ease;
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
