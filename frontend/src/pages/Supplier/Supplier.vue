<template>
  <div class="supplier-page">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div class="d-flex align-items-center">
        <div class="header-icon-box me-3">
          <i class="bi bi-truck fs-4 text-primary"></i>
        </div>
        <div>
          <h4 class="mb-0 fw-bold text-dark">Supplier</h4>
          <p class="text-muted small mb-0">Master Data Mitra Supplier</p>
        </div>
      </div>
      <button v-if="isAdmin" class="btn btn-primary btn-modern" @click="openAddModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Supplier
      </button>
    </div>

    <!-- Statistik / Ringkasan Supplier -->
    <div class="row g-3 mb-4">
      <div class="col-xl-4 col-md-6">
        <div class="card stat-card border-0 shadow-sm rounded-15 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Total Supplier</p>
              <h3 class="fw-bold mb-0 text-dark">{{ stats.total }}</h3>
            </div>
            <div class="stat-icon bg-primary-subtle text-primary">
              <i class="bi bi-truck fs-4"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-md-6">
        <div class="card stat-card border-0 shadow-sm rounded-15 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Supplier Aktif</p>
              <h3 class="fw-bold mb-0 text-success">{{ stats.aktif }}</h3>
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
              <p class="text-muted small mb-1">Non Aktif</p>
              <h3 class="fw-bold mb-0 text-danger">{{ stats.nonAktif }}</h3>
            </div>
            <div class="stat-icon bg-danger-subtle text-danger">
              <i class="bi bi-x-circle fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Card Table & Search -->
    <div class="card border-0 shadow-sm rounded-15 mb-4">
      <div class="card-body p-4">
        <PageErrorAlert :error="loadError" fallback="Gagal memuat data supplier." @retry="loadSuppliers" />
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
                placeholder="Cari supplier..."
                v-model="searchQuery"
              />
            </div>
          </div>
          <div class="col-md-auto d-flex gap-2">
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
            <thead class="table-light fs-7">
              <tr>
                <th class="py-3 ps-3">No</th>
                <th class="py-3">Kode</th>
                <th class="py-3">Nama Supplier</th>
                <th class="py-3">Telepon</th>
                <th class="py-3">Alamat</th>
                <th class="py-3">Status</th>
                <th class="py-3 text-center pe-3">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in paginatedSuppliers" :key="item.id_supplier">
                <td class="ps-3">
                  {{ paginationStart + index }}
                </td>
                <td>
                  <code>{{ item.supplier_code }}</code>
                </td>
                <td class="fw-semibold">{{ item.name }}</td>
                <td>{{ item.phone }}</td>
                <td>{{ item.address || '-' }}</td>
                <td>
                  <StatusBadge
                    :status="item.status"
                    :label="item.status === 'AKTIF' ? 'Aktif' : 'Non Aktif'"
                    class="rounded-pill px-2 py-1"
                  />
                </td>
                <td class="text-center pe-3">
                  <button
                    v-if="isAdmin"
                    class="btn btn-sm btn-light text-warning action-btn me-1"
                    @click="openEditModal(item)"
                    title="Edit"
                  >
                    <i class="bi bi-pencil-fill"></i>
                  </button>
                  <button
                    v-if="isAdmin"
                    class="btn btn-sm btn-light text-danger action-btn"
                    @click="deleteSupplier(item)"
                    title="Delete"
                  >
                    <i class="bi bi-trash-fill"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="filteredSuppliers.length === 0">
                <td colspan="9" class="text-center py-4 text-muted">
                  Tidak ada data supplier ditemukan.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
          <div class="text-muted small">
            Menampilkan {{ paginationStart }} sampai {{ paginationEnd }} dari
            {{ filteredSuppliers.length }} entri
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

    <!-- Modal Form (Tambah / Edit Supplier) -->
    <div
      class="modal fade"
      id="supplierModal"
      tabindex="-1"
      aria-hidden="true"
      ref="supplierModalRef"
    >
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-15 shadow">
          <div class="modal-header border-0 pb-0 pt-4 px-4">
            <h5 class="modal-title fw-bold">
              {{ isEditMode ? "Edit Supplier" : "Tambah Supplier" }}
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="saveSupplier">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold"
                    >Kode Supplier</label
                  >
                  <input
                    type="text"
                    inputmode="text"
                    autocomplete="off"
                    class="form-control"
                    v-model="form.supplier_code"
                    placeholder="Contoh: SP006"
                    maxlength="50"
                    :pattern="CODE_PATTERN"
                    required
                  />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold"
                    >Nama Supplier</label
                  >
                  <input
                    type="text"
                    inputmode="text"
                    class="form-control"
                    v-model="form.name"
                    placeholder="Masukkan nama perusahaan supplier"
                    maxlength="200"
                    required
                  />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Telepon</label>
                  <input
                    type="tel"
                    inputmode="numeric"
                    autocomplete="tel"
                    class="form-control"
                    v-model="form.phone"
                    @input="form.phone = digitsOnly(form.phone)"
                    placeholder="Nomor telepon (angka saja)"
                    maxlength="50"
                    :pattern="PHONE_PATTERN"
                  />
                </div>
                <div class="col-md-12">
                  <label class="form-label small fw-semibold">Alamat</label>
                  <textarea
                    class="form-control"
                    rows="2"
                    v-model="form.address"
                    inputmode="text"
                    maxlength="65535"
                    placeholder="Alamat supplier"
                  ></textarea>
                </div>
                <div class="col-12">
                  <div class="form-check form-switch pt-2">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="statusAktif"
                      v-model="form.is_active"
                      :true-value="1"
                      :false-value="0"
                    />
                    <label
                      class="form-check-label fw-semibold small"
                      for="statusAktif"
                      >Status Aktif</label
                    >
                  </div>
                </div>
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
                <button type="submit" class="btn btn-primary btn-modern px-4" :disabled="saving">
                  {{ saving ? "Menyimpan..." : isEditMode ? "Update" : "Simpan" }}
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
import supplierService from "../../services/supplier";
import { getActionErrorMessage } from "../../services/apiNotifier";
import { SUPPLIER_PAGE_SIZE } from "../../constants/app";
import {
  CODE_PATTERN,
  PHONE_PATTERN,
  digitsOnly,
  isValidPhone,
  validateCode,
  validateText,
} from "../../utils/validation";
import { usePagination } from "../../composables/usePagination";
import { useConfirm } from "../../composables/useConfirm";
import { useAuth } from "../../stores/auth";
import BasePagination from "../../components/BasePagination.vue";
import PageErrorAlert from "../../components/PageErrorAlert.vue";
import StatusBadge from "../../components/StatusBadge.vue";

const { isAdmin } = useAuth();
const { confirm } = useConfirm();

const searchQuery = ref("");
const loadError = ref(null);

const isEditMode = ref(false);
const saving = ref(false);
const editId = ref(null);

const supplierModalRef = ref(null);

let modalInstance = null;

const suppliers = ref([]);

const form = ref({
  supplier_code: "",
  name: "",
  phone: "",
  address: "",
  is_active: 1,
});

const loadSuppliers = async () => {
  loadError.value = null;
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

const stats = computed(() => ({
  total: suppliers.value.length,
  aktif: suppliers.value.filter((s) => s.status === 'AKTIF').length,
  nonAktif: suppliers.value.filter((s) => s.status !== 'AKTIF').length,
}));

const filteredSuppliers = computed(() => {
  if (!searchQuery.value) return suppliers.value;

  const q = searchQuery.value.toLowerCase();

  return suppliers.value.filter(
    (s) =>
      (s.supplier_code || "").toLowerCase().includes(q) ||
      (s.name || "").toLowerCase().includes(q) ||
      (s.phone || "").toLowerCase().includes(q) ||
      (s.address || "").toLowerCase().includes(q),
  );
});

const {
  currentPage,
  totalPages,
  visiblePages,
  paginatedItems: paginatedSuppliers,
  paginationStart,
  paginationEnd,
  changePage,
  resetPage,
} = usePagination(filteredSuppliers, { perPage: SUPPLIER_PAGE_SIZE });

watch(searchQuery, resetPage);

onMounted(() => {
  modalInstance = new bootstrap.Modal(supplierModalRef.value);

  loadSuppliers();
});

const openAddModal = () => {
  isEditMode.value = false;
  editId.value = null;

  form.value = {
    supplier_code: "",
    name: "",
    phone: "",
    address: "",
    is_active: 1,
  };

  modalInstance.show();
};

const openEditModal = (item) => {
  isEditMode.value = true;
  editId.value = item.id_supplier;

  form.value = {
    supplier_code: item.supplier_code,
    name: item.name,
    phone: item.phone,
    address: item.address || "",
    is_active: item.status === 'AKTIF' ? 1 : 0,
  };

  modalInstance.show();
};

const saveSupplier = async () => {
  const codeError = validateCode(form.value.supplier_code, "Kode supplier", 50);
  if (codeError) {
    Swal.fire("Validasi", codeError, "warning");
    return;
  }
  const nameError = validateText(form.value.name, "Nama supplier", 200);
  if (nameError) {
    Swal.fire("Validasi", nameError, "warning");
    return;
  }
  if (!isValidPhone(form.value.phone, 50)) {
    Swal.fire("Validasi", "Nomor telepon hanya boleh angka dan maksimal 50 karakter.", "warning");
    return;
  }
  const addressError = validateText(form.value.address, "Alamat", 65535, false);
  if (addressError) {
    Swal.fire("Validasi", addressError, "warning");
    return;
  }
  saving.value = true;
  try {
    const payload = {
      supplier_code: form.value.supplier_code,
      name: form.value.name,
      phone: form.value.phone,
      address: form.value.address,
      is_active: form.value.is_active,
    };

    if (isEditMode.value) {
      await supplierService.update(editId.value, payload);
      Swal.fire("Berhasil!", "Supplier berhasil diupdate.", "success");
    } else {
      await supplierService.create(payload);
      Swal.fire("Berhasil!", "Supplier berhasil ditambahkan.", "success");
    }

    await loadSuppliers();
    modalInstance.hide();
  } catch (err) {
    const status = err.response?.status ? `(${err.response.status}) ` : "";
    const msg = getActionErrorMessage(err, status + (err.message || "Terjadi kesalahan"));
    Swal.fire("Gagal!", msg, "error");
  } finally {
    saving.value = false;
  }
};

const deleteSupplier = async (item) => {
  const confirmed = await confirm({
    title: "Hapus Supplier?",
    text: `Apakah Anda yakin ingin menghapus supplier ${item.name}?`,
    confirmButtonText: "Ya, Hapus!",
    cancelButtonText: "Batal",
    swal: { confirmButtonColor: "#e53e3e", cancelButtonColor: "#6c757d" },
  });
  if (!confirmed) return;

  try {
    await supplierService.delete(item.id_supplier);
    Swal.fire("Dihapus!", "Supplier berhasil dihapus.", "success");
    await loadSuppliers();
  } catch (err) {
    const status = err.response?.status ? `(${err.response.status}) ` : "";
    const msg = getActionErrorMessage(err, status + (err.message || "Gagal menghapus supplier."));
    Swal.fire("Gagal!", msg, "error");
  }
};

const refreshData = () => {
  searchQuery.value = "";
  resetPage();
  loadSuppliers();
};
</script>

<style scoped>
.supplier-page {
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
