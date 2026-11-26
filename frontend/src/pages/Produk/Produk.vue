<template>
  <div class="produk-page">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div class="d-flex align-items-center">
        <div class="header-icon-box me-3">
          <i class="bi bi-box-seam fs-4 text-primary"></i>
        </div>
        <div>
          <h4 class="mb-0 fw-bold text-dark">Produk</h4>
          <p class="text-muted small mb-0">Master Data Produk</p>
        </div>
      </div>
      <button v-if="isAdmin" class="btn btn-primary btn-modern" @click="openAddModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Produk
      </button>
    </div>

    <!-- Statistik -->
    <div class="row g-3 mb-4">
      <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm rounded-15 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Total Produk</p>
              <h3 class="fw-bold mb-0 text-dark">{{ stats.total }}</h3>
            </div>
            <div class="stat-icon bg-primary-subtle text-primary">
              <i class="bi bi-box fs-4"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm rounded-15 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Produk Tersedia</p>
              <h3 class="fw-bold mb-0 text-success">{{ stats.aktif }}</h3>
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
              <p class="text-muted small mb-1">Minimum Stock</p>
              <h3 class="fw-bold mb-0 text-warning">{{ stats.minStock }}</h3>
            </div>
            <div class="stat-icon bg-warning-subtle text-warning">
              <i class="bi bi-exclamation-triangle fs-4"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm rounded-15 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Tidak Tersedia</p>
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
        <PageErrorAlert :error="error" fallback="Gagal memuat data produk." @retry="loadAll" />

        <div class="row g-3 mb-4 align-items-center">
          <div class="col-md-5">
            <div class="input-group search-box">
              <span class="input-group-text bg-white border-end-0">
                <i class="bi bi-search text-muted"></i>
              </span>
              <input
                type="search"
                inputmode="text"
                autocomplete="off"
                class="form-control border-start-0 ps-0"
                placeholder="Cari produk..."
                v-model="searchQuery"
              />
            </div>
          </div>
          <div class="col-md-7">
            <div class="d-flex gap-2 justify-content-md-end align-items-center flex-wrap">
              <div class="d-flex align-items-center gap-2">
                <label class="text-muted small fw-semibold text-nowrap mb-0">Kategori</label>
                <select class="form-select form-select-sm" style="width:auto;min-width:150px" v-model="selectedCategory">
                  <option value="">Semua</option>
                  <option v-for="c in masterData.categories" :key="c.id_category" :value="c.name">
                    {{ c.name }}
                  </option>
                </select>
              </div>
              <div class="d-flex align-items-center gap-2">
                <label class="text-muted small fw-semibold text-nowrap mb-0">Tampil</label>
                <select class="form-select form-select-sm" style="width:auto;min-width:100px" v-model.number="itemsPerPage" @change="resetPage">
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
        </div>

        <!-- Table -->
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light fs-7">
              <tr>
                <th class="py-3 ps-3">No</th>
                <th class="py-3">Kode Produk</th>
                <th class="py-3">Nama Produk</th>
                <th class="py-3">Satuan</th>
                <th class="py-3">Supplier</th>
                <th class="py-3">Kategori</th>
                <th class="py-3">Tingkat Stok</th>
                <th class="py-3">Status</th>
                <th class="py-3 text-center pe-3">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in paginatedProducts" :key="item.id_product">
                 <td class="ps-3">
                  {{ paginationStart + index }}
                </td>
                <td class="fw-semibold text-primary">{{ item.sku_code }}</td>
                <td>{{ item.name }}</td>
                <td>{{ item.unit }}</td>
                <td>{{ item.supplier || '-' }}</td>
                <td>{{ item.category_name || item.category || '-' }}</td>
                <td class="text-center fw-semibold">{{ item.minimum_stock }}</td>
                <td>
                  <StatusBadge
                    :status="item.status === 'AKTIF' ? 'AKTIF' : 'EXPIRED'"
                    :label="item.status === 'AKTIF' ? 'Tersedia' : 'Tidak Tersedia'"
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
                    @click="deleteProduct(item)"
                    title="Delete"
                  >
                    <i class="bi bi-trash-fill"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="filteredProducts.length === 0">
                <td colspan="9" class="text-center py-4 text-muted">
                  Tidak ada data produk ditemukan.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
          <div class="text-muted small">
            Menampilkan {{ paginationStart }} sampai {{ paginationEnd }} dari
            {{ filteredProducts.length }} entri
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
      id="productModal"
      tabindex="-1"
      aria-hidden="true"
      ref="productModalRef"
    >
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-15 shadow">
          <div class="modal-header border-0 pb-0 pt-4 px-4">
            <h5 class="modal-title fw-bold">
              {{ isEditMode ? "Edit Produk" : "Tambah Produk" }}
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body p-4">
            <PageErrorAlert :error="validationError" :retry="false" fallback="Periksa kembali input produk." />
            <form @submit.prevent="saveProduct">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Nama Produk</label>
                  <input type="text" inputmode="text" autocomplete="off" maxlength="200" class="form-control" v-model="form.name" placeholder="Masukkan nama produk" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">SKU Code</label>
                  <input type="text" inputmode="text" autocomplete="off" maxlength="50" class="form-control" v-model="form.sku_code" :pattern="CODE_PATTERN" placeholder="Masukkan SKU code" required />
                </div>
              </div>

              <hr class="my-3">

              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Supplier</label>
                  <select class="form-select" v-model.number="form.id_supplier" required>
                    <option disabled value="">Pilih Supplier</option>
                    <option v-for="s in masterData.suppliers" :key="s.id_supplier" :value="s.id_supplier">{{ s.name }}</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Brand</label>
                  <input type="text" inputmode="text" maxlength="200" class="form-control" v-model="form.brand_name" placeholder="Contoh: Indofood" />
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Kategori</label>
                  <select class="form-select" v-model.number="form.id_category" required>
                    <option disabled value="">Pilih Kategori</option>
                    <option v-for="c in masterData.categories" :key="c.id_category" :value="c.id_category">{{ c.name }}</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Satuan</label>
                  <input type="text" inputmode="text" maxlength="50" class="form-control" v-model="form.unit" placeholder="Contoh: Karton" required />
                </div>
              </div>

              <hr class="my-3">

              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Minimum Stock</label>
                  <input type="number" class="form-control" v-model.number="form.minimum_stock" min="0" :max="MAX_QTY" step="1" inputmode="numeric" placeholder="0" />
                </div>
                <div class="col-md-4">

                  <div class="form-check form-switch pt-4">
                    <input class="form-check-input" type="checkbox" id="statusAktif" v-model="form.status" :true-value="'AKTIF'" :false-value="'NONAKTIF'" />
                    <label class="form-check-label fw-semibold small" for="statusAktif">Status Tersedia</label>
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
                <button
                  type="submit"
                  class="btn btn-primary btn-modern px-4"
                  :disabled="saving"
                >
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
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import * as bootstrap from "bootstrap";
import productService from "../../services/product";
import masterService from "../../services/master";
import { getActionErrorMessage } from "../../services/apiNotifier";
import Swal from "sweetalert2";
import { usePagination } from "../../composables/usePagination";
import { useConfirm } from "../../composables/useConfirm";
import { useAuth } from "../../stores/auth";
import { DEBOUNCE, MAX_QTY, PAGE_SIZE_OPTIONS } from "../../constants/app";
import { coerceFiniteNumber } from "../../utils/number";
import { CODE_PATTERN, validateCode, validateText } from "../../utils/validation";
import BasePagination from "../../components/BasePagination.vue";
import PageErrorAlert from "../../components/PageErrorAlert.vue";
import StatusBadge from "../../components/StatusBadge.vue";

const { isAdmin } = useAuth();
const { confirm } = useConfirm();

const searchQuery = ref("");
const selectedCategory = ref("");
const isEditMode = ref(false);
const productModalRef = ref(null);
const saving = ref(false);
const editId = ref(null);
const products = ref([]);
const error = ref(null);
const validationError = ref(null);
let modalInstance = null;
let searchDebounce = null;

const masterData = ref({
  categories: [],
  suppliers: [],
});

const stats = ref({
  total: 0,
  aktif: 0,
  minStock: 0,
  nonAktif: 0,
});

const form = ref({
  sku_code: "",
  name: "",
  brand_name: "",
  id_supplier: "",
  id_category: "",
  unit: "",
  minimum_stock: 10,
  status: "AKTIF",
});

const setError = (err) => {
  if (!error.value) error.value = err;
};

const loadMasterData = async () => {
  try {
    const [catsRes, suppRes] = await Promise.all([
      masterService.getCategories(),
      masterService.getSuppliers(),
    ]);
    if (!catsRes.data?.status) throw new Error(catsRes.data?.message || "Gagal memuat kategori");
    if (!suppRes.data?.status) throw new Error(suppRes.data?.message || "Gagal memuat supplier");
    masterData.value.categories = catsRes.data.data || [];
    masterData.value.suppliers = suppRes.data.data || [];
  } catch (err) {
    setError(err);
  }
};

const loadStatistic = async () => {
  try {
    const response = await productService.statistic();
    if (!response.data?.status) throw new Error(response.data?.message || "Gagal memuat statistik produk");
    const data = response.data.data || {};
    stats.value = {
      total: data.total_produk || 0,
      aktif: data.produk_aktif || 0,
      minStock: data.minimum_stock || 0,
      nonAktif: data.non_aktif || 0,
    };
  } catch (err) {
    setError(err);
  }
};

const filteredProducts = computed(() => {
  if (!selectedCategory.value) return products.value;
  return products.value.filter(
    (product) => (product.category_name || product.category || "") === selectedCategory.value,
  );
});

const {
  currentPage,
  itemsPerPage,
  totalPages,
  visiblePages,
  paginatedItems: paginatedProducts,
  paginationStart,
  paginationEnd,
  changePage,
  resetPage,
} = usePagination(filteredProducts);

const loadProducts = async () => {
  resetPage();
  try {
    const params = searchQuery.value ? { search: searchQuery.value } : {};
    const response = await productService.getProducts(params);
    if (!response.data?.status) throw new Error(response.data?.message || "Gagal memuat data produk");
    products.value = response.data.data || [];
  } catch (err) {
    products.value = [];
    setError(err);
  }
};

const loadAll = async () => {
  error.value = null;
  await Promise.all([loadMasterData(), loadStatistic(), loadProducts()]);
};

watch(searchQuery, () => {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(loadProducts, DEBOUNCE.SEARCH_MS);
});

onMounted(() => {
  modalInstance = new bootstrap.Modal(productModalRef.value);
  loadAll();
});

onBeforeUnmount(() => clearTimeout(searchDebounce));

const validateForm = () => {
  const nameError = validateText(form.value.name, "Nama produk", 200);
  if (nameError) return nameError;
  const skuError = validateCode(form.value.sku_code, "SKU code", 50);
  if (skuError) return skuError;
  const brandError = validateText(form.value.brand_name, "Brand", 200, false);
  if (brandError) return brandError;
  if (!form.value.id_supplier) return "Supplier wajib dipilih.";
  if (!form.value.id_category) return "Kategori wajib dipilih.";
  const unitError = validateText(form.value.unit, "Satuan", 50);
  if (unitError) return unitError;
  const minimumStock = coerceFiniteNumber(form.value.minimum_stock, Number.NaN);
  if (!Number.isFinite(minimumStock) || minimumStock < 0) return "Minimum stock harus berupa angka non-negatif.";
  if (minimumStock > MAX_QTY) return `Minimum stock tidak boleh lebih dari ${MAX_QTY}.`;
  return "";
};

const openAddModal = () => {
  isEditMode.value = false;
  editId.value = null;
  validationError.value = null;
  form.value = {
    sku_code: "",
    name: "",
    brand_name: "",
    id_supplier: "",
    id_category: "",
    unit: "",
    minimum_stock: 10,
    status: "AKTIF",
  };
  modalInstance.show();
};

const openEditModal = (item) => {
  isEditMode.value = true;
  editId.value = item.id_product;
  validationError.value = null;
  form.value = {
    sku_code: item.sku_code,
    name: item.name,
    brand_name: item.brand_name || item.brand || "",
    id_supplier: item.id_supplier || "",
    id_category: item.id_category || "",
    unit: item.unit || "",
    minimum_stock: item.minimum_stock,
    status: item.status,
  };
  modalInstance.show();
};

const saveProduct = async () => {
  validationError.value = null;
  const message = validateForm();
  if (message) {
    validationError.value = message;
    return;
  }
  saving.value = true;
  try {
    const payload = {
      sku_code: form.value.sku_code,
      name: form.value.name,
      brand_name: form.value.brand_name,
      id_supplier: form.value.id_supplier,
      id_category: form.value.id_category,
      unit: form.value.unit,
      minimum_stock: Number(form.value.minimum_stock),
      status: form.value.status,
    };

    if (isEditMode.value) {
      await productService.update(editId.value, payload);
      Swal.fire("Berhasil!", "Produk berhasil diupdate.", "success");
    } else {
      await productService.create(payload);
      Swal.fire("Berhasil!", "Produk berhasil ditambahkan.", "success");
    }

    modalInstance.hide();
    await Promise.all([loadStatistic(), loadProducts()]);
  } catch (err) {
    setError(err);
    Swal.fire("Gagal!", getActionErrorMessage(err, "Terjadi kesalahan"), "error");
  } finally {
    saving.value = false;
  }
};

const deleteProduct = async (item) => {
  const confirmed = await confirm({
    title: "Hapus Produk?",
    text: `Apakah Anda yakin ingin menghapus produk ${item.name}?`,
    confirmButtonText: "Ya, Hapus!",
    cancelButtonText: "Batal",
    swal: { confirmButtonColor: "#e53e3e", cancelButtonColor: "#6c757d" },
  });

  if (!confirmed) return;
  try {
    await productService.delete(item.id_product);
    Swal.fire("Dihapus!", "Produk berhasil dihapus.", "success");
    await Promise.all([loadStatistic(), loadProducts()]);
  } catch (err) {
    setError(err);
    Swal.fire("Gagal!", getActionErrorMessage(err, "Gagal menghapus produk."), "error");
  }
};

const refreshData = async () => {
  searchQuery.value = "";
  selectedCategory.value = "";
  error.value = null;
  await Promise.all([loadStatistic(), loadProducts()]);
};
</script>

<style scoped>
.produk-page {
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
  table-layout: auto;
}

.table th,
.table td {
  white-space: nowrap;
  padding-right: 8px;
}

.table th:nth-child(8),
.table td:nth-child(8) {
  min-width: 90px;
}

.table th:nth-child(9),
.table td:nth-child(9) {
  min-width: 50px;
  text-align: center;
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
