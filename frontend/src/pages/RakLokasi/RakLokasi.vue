<template>
  <div class="rak-page">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div class="d-flex align-items-center">
        <div class="header-icon-box me-3">
          <i class="bi bi-grid-3x3-gap-fill fs-4 text-primary"></i>
        </div>
        <div>
          <h4 class="mb-0 fw-bold text-dark">Rak / Lokasi</h4>
          <p class="text-muted small mb-0">Master Data Rak &amp; Lokasi Penyimpanan</p>
        </div>
      </div>
      <button v-if="isAdmin" class="btn btn-primary btn-modern" @click="openAddModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Rak
      </button>
    </div>

    <!-- Statistik / Ringkasan Rak -->
    <div class="row g-3 mb-4">
      <div class="col-xl-4 col-md-6">
        <div class="card stat-card border-0 shadow-sm rounded-15 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Total Rak</p>
              <h3 class="fw-bold mb-0 text-dark">{{ stats.total }}</h3>
            </div>
            <div class="stat-icon bg-primary-subtle text-primary">
              <i class="bi bi-grid-3x3-gap-fill fs-4"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-md-6">
        <div class="card stat-card border-0 shadow-sm rounded-15 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Aktif</p>
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
        <PageErrorAlert :error="loadError" fallback="Gagal memuat data rak." @retry="loadRacks" />
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
                placeholder="Cari rak..."
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
                <th class="py-3">Kode Rak</th>
                <th class="py-3">Nama</th>
                <th class="py-3">Status</th>
                <th class="py-3 text-center pe-3">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in racks" :key="item.id_rack">
                <td class="ps-3">
                  {{ paginationStart + index }}
                </td>
                <td>
                  <code>{{ item.rack_code }}</code>
                </td>
                <td class="fw-semibold">{{ item.name || "-" }}</td>
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
                    @click="deleteRak(item)"
                    title="Delete"
                  >
                    <i class="bi bi-trash-fill"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="racks.length === 0">
                <td colspan="5" class="text-center py-4 text-muted">
                  Tidak ada data rak ditemukan.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
          <div class="text-muted small">
            Menampilkan {{ paginationStart }} sampai {{ paginationEnd }} dari
            {{ totalItems }} entri
          </div>
          <BasePagination
            :current-page="currentPage"
            :total-pages="totalPages"
            :visible-pages="visiblePages"
            @change="goToPage"
          />
        </div>
      </div>
    </div>

    <!-- Modal Form (Tambah / Edit Rak) -->
    <div
      class="modal fade"
      id="rakModal"
      tabindex="-1"
      aria-hidden="true"
      ref="rakModalRef"
    >
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-15 shadow">
          <div class="modal-header border-0 pb-0 pt-4 px-4">
            <h5 class="modal-title fw-bold">
              {{ isEditMode ? "Edit Rak" : "Tambah Rak" }}
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="saveRak">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Kode Rak</label>
                  <input
                    type="text"
                    inputmode="text"
                    autocomplete="off"
                    class="form-control"
                    v-model="form.rack_code"
                    placeholder="Contoh: RAK-A01"
                    maxlength="50"
                    :pattern="CODE_PATTERN"
                    required
                  />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Nama</label>
                  <input
                    type="text"
                    inputmode="text"
                    class="form-control"
                    v-model="form.name"
                    placeholder="Masukkan nama rak / lokasi (opsional)"
                    maxlength="100"
                  />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Status</label>
                  <select class="form-select" v-model="form.status">
                    <option value="AKTIF">AKTIF</option>
                    <option value="NONAKTIF">NONAKTIF</option>
                  </select>
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
import { ref, computed, onMounted, watch } from "vue";
import * as bootstrap from "bootstrap";
import Swal from "sweetalert2";
import api from "../../services/api";
import { getActionErrorMessage } from "../../services/apiNotifier";
import { usePagination } from "../../composables/usePagination";
import { useConfirm } from "../../composables/useConfirm";
import { useAuth } from "../../stores/auth";
import { DEBOUNCE, RACK_PAGE_SIZE } from "../../constants/app";
import { CODE_PATTERN, validateCode, validateText } from "../../utils/validation";
import BasePagination from "../../components/BasePagination.vue";
import PageErrorAlert from "../../components/PageErrorAlert.vue";
import StatusBadge from "../../components/StatusBadge.vue";

const { isAdmin } = useAuth();
const { confirm } = useConfirm();

const searchQuery = ref("");
const loadError = ref(null);
const totalItems = ref(0);

const isEditMode = ref(false);
const saving = ref(false);
const editId = ref(null);

const rakModalRef = ref(null);

let modalInstance = null;
let searchTimer = null;
let loadToken = 0;

const racks = ref([]);
const stats = ref({ total: 0, aktif: 0, nonAktif: 0 });

const rackSlots = computed(() => Array.from({ length: totalItems.value }, (_, index) => index));

const {
  currentPage,
  totalPages,
  visiblePages,
  paginationStart,
  paginationEnd,
  changePage,
  resetPage,
} = usePagination(rackSlots, { perPage: RACK_PAGE_SIZE });

const form = ref({
  rack_code: "",
  name: "",
  status: "AKTIF",
});

const loadRacks = async () => {
  const token = ++loadToken;
  loadError.value = null;
  try {
    const response = await api.get("/rack", {
      params: {
        page: currentPage.value,
        limit: RACK_PAGE_SIZE,
        search: searchQuery.value.trim(),
      },
    });

    if (token !== loadToken) return;

    if (response.data.status) {
      const data = response.data.data || {};
      const items = Array.isArray(data.items)
        ? data.items
        : Array.isArray(data)
          ? data
          : [];

      racks.value = items;
      totalItems.value = Number(data.total || 0);

      const serverStats = data.stats;
      stats.value = serverStats
        ? {
            total: Number(serverStats.total || 0),
            aktif: Number(serverStats.aktif || 0),
            nonAktif: Number(serverStats.nonAktif || 0),
          }
        : {
            total: totalItems.value,
            aktif: items.filter((r) => r.status === "AKTIF").length,
            nonAktif: items.filter((r) => r.status !== "AKTIF").length,
          };
    } else {
      racks.value = [];
      totalItems.value = 0;
    }
  } catch (err) {
    if (token !== loadToken) return;
    racks.value = [];
    totalItems.value = 0;
    loadError.value = err;
  }
};

const reloadAfterChange = async () => {
  const requestedPage = currentPage.value;
  await loadRacks();
  if (requestedPage !== currentPage.value) return;
  if (currentPage.value > totalPages.value) {
    changePage(totalPages.value);
    await loadRacks();
  }
};

const goToPage = (page) => {
  changePage(page);
};

watch(currentPage, () => {
  loadRacks();
});

watch(searchQuery, () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    const wasFirstPage = currentPage.value === 1;
    resetPage();
    if (wasFirstPage) loadRacks();
  }, DEBOUNCE.SEARCH_MS);
});

onMounted(() => {
  modalInstance = new bootstrap.Modal(rakModalRef.value);

  loadRacks();
});

const openAddModal = () => {
  isEditMode.value = false;
  editId.value = null;

  form.value = {
    rack_code: "",
    name: "",
    status: "AKTIF",
  };

  modalInstance.show();
};

const openEditModal = (item) => {
  isEditMode.value = true;
  editId.value = item.id_rack;

  form.value = {
    rack_code: item.rack_code,
    name: item.name || "",
    status: item.status || "AKTIF",
  };

  modalInstance.show();
};

const saveRak = async () => {
  const codeError = validateCode(form.value.rack_code, "Kode rak", 50);
  if (codeError) {
    Swal.fire("Validasi", codeError, "warning");
    return;
  }
  const nameError = validateText(form.value.name, "Nama rak", 100, false);
  if (nameError) {
    Swal.fire("Validasi", nameError, "warning");
    return;
  }
  saving.value = true;
  try {
    const payload = {
      rack_code: form.value.rack_code.trim(),
      name: form.value.name.trim(),
      status: form.value.status,
    };

    if (isEditMode.value) {
      await api.put("/rack/" + editId.value, payload);
      Swal.fire("Berhasil!", "Rak berhasil diupdate.", "success");
    } else {
      await api.post("/rack", payload);
      Swal.fire("Berhasil!", "Rak berhasil ditambahkan.", "success");
    }

    await reloadAfterChange();
    modalInstance.hide();
  } catch (err) {
    const status = err.response?.status ? `(${err.response.status}) ` : "";
    const msg = getActionErrorMessage(err, status + (err.message || "Terjadi kesalahan"));
    Swal.fire("Gagal!", msg, "error");
  } finally {
    saving.value = false;
  }
};

const deleteRak = async (item) => {
  const confirmed = await confirm({
    title: "Hapus Rak?",
    text: `Apakah Anda yakin ingin menghapus rak ${item.rack_code}?`,
    confirmButtonText: "Ya, Hapus!",
    cancelButtonText: "Batal",
    swal: { confirmButtonColor: "#e53e3e", cancelButtonColor: "#6c757d" },
  });
  if (!confirmed) return;

  try {
    await api.delete("/rack/" + item.id_rack);
    Swal.fire("Dihapus!", "Rak berhasil dihapus.", "success");
    await reloadAfterChange();
  } catch (err) {
    const status = err.response?.status ? `(${err.response.status}) ` : "";
    const msg = getActionErrorMessage(err, status + (err.message || "Gagal menghapus rak."));
    Swal.fire("Gagal!", msg, "error");
  }
};

const refreshData = () => {
  clearTimeout(searchTimer);
  searchQuery.value = "";
  const wasFirstPage = currentPage.value === 1;
  resetPage();
  if (wasFirstPage) loadRacks();
};
</script>

<style scoped>
.rak-page {
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

.form-select {
  border-radius: 8px;
  border-color: #cbd5e1;
  padding: 10px 14px;
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
</style>
