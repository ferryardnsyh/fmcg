<template>
  <div class="kategori-page">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div class="d-flex align-items-center">
        <div class="header-icon-box me-3">
          <i class="bi bi-tag fs-4 text-primary"></i>
        </div>
        <div>
          <h4 class="mb-0 fw-bold text-dark">Kategori</h4>
          <p class="text-muted small mb-0">Master Data Kategori Produk</p>
        </div>
      </div>
      <button v-if="isAdmin" class="btn btn-primary btn-modern d-flex align-items-center" @click="openAddModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
      </button>
    </div>

    <div class="card border-0 shadow-sm rounded-15 mb-4">
      <div class="card-body p-4">
        <PageErrorAlert :error="loadError" fallback="Gagal memuat data kategori." @retry="loadCategories" />
        <div class="d-flex flex-wrap gap-3 mb-4 align-items-center">
          <div class="input-group search-box" style="max-width: 320px; flex: 1 1 200px;">
            <span class="input-group-text bg-white border-end-0">
              <i class="bi bi-search text-muted"></i>
            </span>
            <input
              type="search"
              inputmode="text"
              autocomplete="off"
              class="form-control border-start-0 ps-0"
              placeholder="Cari kode atau nama kategori..."
              v-model="searchQuery"
              @input="onSearchInput"
            />
          </div>
          <select class="form-select" style="width: auto; min-width: 140px;" v-model="filterStatus" @change="loadCategories">
            <option value="">Semua Status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Non Aktif</option>
          </select>
          <button class="btn btn-outline-secondary btn-modern d-flex align-items-center" @click="refreshData">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
          </button>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase fs-7">
              <tr>
                <th class="py-3 ps-3" style="width: 60px">No</th>
                <th class="py-3" style="width: 110px" @click="toggleSort('category_code')" role="button">
                  Kode
                  <i v-if="sortField === 'category_code'" :class="sortOrder === 'ASC' ? 'bi bi-caret-up-fill' : 'bi bi-caret-down-fill'"></i>
                </th>
                <th class="py-3" @click="toggleSort('name')" role="button">
                  Nama Kategori
                  <i v-if="sortField === 'name'" :class="sortOrder === 'ASC' ? 'bi bi-caret-up-fill' : 'bi bi-caret-down-fill'"></i>
                </th>
                <th class="py-3 text-center" style="width: 100px">Total Produk</th>
                <th class="py-3 text-center" style="width: 100px">Status</th>
                <th class="py-3" style="width: 150px">Tanggal Dibuat</th>
                <th class="py-3 text-center" style="width: 130px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="7" class="text-center py-5">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                  <p class="text-muted mt-2 mb-0">Memuat data...</p>
                </td>
              </tr>
              <tr v-else-if="categories.length === 0">
                <td colspan="7" class="text-center py-5">
                  <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                  <p class="text-muted mb-0">Tidak ada data kategori ditemukan.</p>
                </td>
              </tr>
              <tr v-for="(item, index) in categories" :key="item.id_category">
                <td class="ps-3 text-muted">{{ (page - 1) * itemsPerPage + index + 1 }}</td>
                <td><span class="badge bg-secondary-subtle text-secondary px-2 py-1">{{ item.category_code }}</span></td>
                <td class="fw-semibold">{{ item.name }}</td>
                <td class="text-center">
                  <span class="badge bg-info-subtle text-info px-2 py-1">{{ item.total_products }} Produk</span>
                </td>
                <td class="text-center">
                  <span :class="['badge rounded-pill px-2 py-1', item.status === 'AKTIF' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger']">
                    {{ item.status === 'AKTIF' ? 'Aktif' : 'Non Aktif' }}
                  </span>
                </td>
                <td class="small text-muted">{{ formatDate(item.created_at) }}</td>
                <td class="text-center">
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
                    @click="deleteCategory(item)"
                    title="Hapus"
                  >
                    <i class="bi bi-trash-fill"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2" v-if="!loading">
          <div class="text-muted small">
            Menampilkan {{ paginationStart }} sampai {{ paginationEnd }} dari {{ total }} entri
          </div>
          <nav>
            <ul class="pagination mb-0">
              <li :class="['page-item', { disabled: page === 1 }]">
                <button class="page-link" @click="changePage(page - 1)">Previous</button>
              </li>
              <li
                v-for="p in totalPages"
                :key="p"
                :class="['page-item', { active: page === p }]"
              >
                <button class="page-link" @click="changePage(p)">{{ p }}</button>
              </li>
              <li :class="['page-item', { disabled: page === totalPages || totalPages === 0 }]">
                <button class="page-link" @click="changePage(page + 1)">Next</button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <div class="modal fade" ref="kategoriModalRef" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ isEditMode ? 'Edit Kategori' : 'Tambah Kategori' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <form @submit.prevent="saveCategory">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Kode Kategori</label>
                <input type="text" inputmode="text" autocomplete="off" maxlength="10" class="form-control" v-model="form.category_code" :readonly="isEditMode" />
                <small class="text-muted" v-if="!isEditMode">Otomatis jika dikosongkan</small>
              </div>
              <div class="mb-3">
                <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.name"
                  placeholder="Masukkan nama kategori"
                  maxlength="200"
                  required
                />
              </div>
              <div class="mb-3" v-if="isEditMode">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="statusSwitch" v-model="form.status" :true-value="'AKTIF'" :false-value="'NONAKTIF'" />
                  <label class="form-check-label" for="statusSwitch">
                    {{ form.status === 'AKTIF' ? 'Aktif' : 'Non Aktif' }}
                  </label>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-primary" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                {{ saving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import * as bootstrap from "bootstrap";
import Swal from "sweetalert2";
import categoryService from "../../services/category";
import { getActionErrorMessage } from "../../services/apiNotifier";
import { useAuth } from "../../stores/auth";
import { DEBOUNCE } from "../../constants/app";
import { formatDate } from "../../utils/date";
import { paginationRange as getPaginationRange } from "../../utils/pagination";
import PageErrorAlert from "../../components/PageErrorAlert.vue";

const { isAdmin } = useAuth();

const searchQuery = ref("");
const searchTimer = ref(null);
const loadError = ref(null);
const filterStatus = ref("");
const page = ref(1);
const itemsPerPage = ref(10);
const sortField = ref("id_category");
const sortOrder = ref("DESC");
const loading = ref(false);
const saving = ref(false);

const kategoriModalRef = ref(null);

const categories = ref([]);
const total = ref(0);

const isEditMode = ref(false);
const form = ref({ id_category: null, category_code: "", name: "", status: "AKTIF" });
let modalInstance = null;

const totalPages = computed(() => {
  if (!total.value) return 0;
  return Math.ceil(total.value / itemsPerPage.value);
});

const range = computed(() => getPaginationRange(total.value, page.value, itemsPerPage.value));

const paginationStart = computed(() => range.value.start);

const paginationEnd = computed(() => range.value.end);

const loadCategories = async () => {
  loading.value = true;
  loadError.value = null;
  try {
    const params = {
      page: page.value,
      limit: itemsPerPage.value,
      sort: sortField.value,
      order: sortOrder.value,
    };
    if (searchQuery.value.trim()) params.search = searchQuery.value.trim();
    if (filterStatus.value) params.status = filterStatus.value;

    const response = await categoryService.all(params);
    if (response.data.status) {
      categories.value = response.data.data.items;
      total.value = response.data.data.total;
    }
  } catch (err) {
    categories.value = [];
    total.value = 0;
    loadError.value = err;
  } finally {
    loading.value = false;
  }
};

const onSearchInput = () => {
  clearTimeout(searchTimer.value);
  searchTimer.value = setTimeout(() => {
    page.value = 1;
    loadCategories();
  }, DEBOUNCE.SEARCH_MS);
};

const toggleSort = (field) => {
  if (sortField.value === field) {
    sortOrder.value = sortOrder.value === "ASC" ? "DESC" : "ASC";
  } else {
    sortField.value = field;
    sortOrder.value = field === "id_category" ? "DESC" : "ASC";
  }
  loadCategories();
};

const openAddModal = async () => {
  isEditMode.value = false;
  form.value = { id_category: null, category_code: "", name: "", status: "AKTIF" };
  try {
    const res = await categoryService.getNextCode();
    if (res.data.status) {
      form.value.category_code = res.data.data.category_code;
    }
  } catch (err) {
    const msg = getActionErrorMessage(err, "Kode otomatis gagal dimuat. Silakan isi kode kategori secara manual.");
    Swal.fire("Perhatian", msg, "warning");
  }
  modalInstance.show();
};

const openEditModal = (item) => {
  isEditMode.value = true;
  form.value = {
    id_category: item.id_category,
    category_code: item.category_code,
    name: item.name,
    status: item.status === 'AKTIF' ? 'AKTIF' : 'NONAKTIF',
  };
  modalInstance.show();
};

const saveCategory = async () => {
  if (!form.value.name.trim()) {
    Swal.fire("Validasi", "Nama kategori wajib diisi.", "warning");
    return;
  }
  if (!isEditMode.value && !form.value.category_code.trim()) {
    Swal.fire("Validasi", "Kode kategori wajib diisi.", "warning");
    return;
  }
  saving.value = true;
  try {
    const payload = {
      name: form.value.name.trim(),
      status: form.value.status,
    };
    if (!isEditMode.value) {
      payload.category_code = form.value.category_code.trim();
      await categoryService.create(payload);
      Swal.fire("Berhasil", "Kategori berhasil ditambahkan", "success");
    } else {
      await categoryService.update(form.value.id_category, payload);
      Swal.fire("Berhasil", "Kategori berhasil diupdate", "success");
    }
    modalInstance.hide();
    await loadCategories();
  } catch (err) {
    const msg = getActionErrorMessage(err, "Terjadi kesalahan");
    Swal.fire("Gagal", msg, "error");
  } finally {
    saving.value = false;
  }
};

const deleteCategory = (item) => {
  Swal.fire({
    title: "Hapus Kategori?",
    text: `Apakah Anda yakin ingin menghapus kategori ${item.name}?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#e53e3e",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Ya, Hapus!",
    cancelButtonText: "Batal",
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await categoryService.delete(item.id_category);
        Swal.fire("Berhasil", "Kategori berhasil dihapus", "success");
        await loadCategories();
      } catch (err) {
        const msg = getActionErrorMessage(err, "Terjadi kesalahan");
        Swal.fire("Gagal", msg, "error");
      }
    }
  });
};

const refreshData = () => {
  searchQuery.value = "";
  filterStatus.value = "";
  page.value = 1;
  loadCategories();
};

const changePage = (p) => {
  if (p >= 1 && p <= totalPages.value) {
    page.value = p;
    loadCategories();
  }
};

onMounted(() => {
  modalInstance = new bootstrap.Modal(kategoriModalRef.value);
  loadCategories();
});
</script>

<style scoped>
.kategori-page {
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

.table {
  color: #334155;
}

.table-hover tbody tr {
  transition: background-color 0.15s ease;
}

.table-hover tbody tr:hover {
  background-color: #f1f5f9;
}

.table thead th {
  font-weight: 700;
  font-size: 0.8rem;
  letter-spacing: 0.02em;
  border-bottom: 2px solid #e2e8f0;
  user-select: none;
}

.table thead th[role="button"]:hover {
  color: #2563eb;
}

.form-control, .form-select {
  border-radius: 8px;
  border-color: #cbd5e1;
  padding: 10px 14px;
}

.form-control:focus, .form-select:focus {
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

.action-btn {
  width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
}

.form-check-input:checked {
  background-color: #2563eb;
  border-color: #2563eb;
}
</style>
