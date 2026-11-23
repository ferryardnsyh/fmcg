<template>
  <div class="opname-page">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div class="d-flex align-items-center">
        <div class="header-icon-box me-3">
          <i class="bi bi-clipboard-data fs-4 text-primary"></i>
        </div>
        <div>
          <h4 class="mb-0 fw-bold text-dark">Stock Opname</h4>
          <p class="text-muted small mb-0">Fisik Stok Periodik</p>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm rounded-15 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Total Sesi</p>
              <h3 class="fw-bold mb-0 text-dark">{{ stats.total }}</h3>
            </div>
            <div class="stat-icon bg-primary-subtle text-primary">
              <i class="bi bi-clipboard fs-4"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm rounded-15 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Completed</p>
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
              <p class="text-muted small mb-1">In Progress</p>
              <h3 class="fw-bold mb-0 text-warning">{{ stats.in_progress }}</h3>
            </div>
            <div class="stat-icon bg-warning-subtle text-warning">
              <i class="bi bi-arrow-repeat fs-4"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm rounded-15 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Draft</p>
              <h3 class="fw-bold mb-0 text-secondary">{{ stats.draft }}</h3>
            </div>
            <div class="stat-icon bg-secondary-subtle text-secondary">
              <i class="bi bi-pencil-square fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm rounded-15 mb-4">
      <div class="card-body p-4">
        <PageErrorAlert :error="loadError" fallback="Gagal memuat data stock opname." @retry="loadAll" />
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
                placeholder="Cari sesi opname..."
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
            <button class="btn btn-outline-secondary btn-modern d-flex align-items-center" @click="refreshData">
              <i class="bi bi-arrow-clockwise me-1"></i> Refresh
            </button>
          </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
          <h5 class="fw-bold mb-0">Daftar Sesi Stock Opname</h5>
          <button class="btn btn-primary btn-modern" @click="openCreateModal">
            <i class="bi bi-plus-lg me-1"></i> Buat Sesi Baru
          </button>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase fs-7">
              <tr>
                <th class="py-3 ps-3">No</th>
                <th class="py-3">No Sesi</th>
                <th class="py-3">Tanggal</th>
                <th class="py-3">Status</th>
                <th class="py-3 text-center pe-3">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="(item, index) in paginatedSessions" :key="opnameSessionId(item)">
                <tr>
                  <td class="ps-3">{{ paginationStart + index }}</td>
                  <td class="fw-semibold text-primary">{{ item.opname_number || item.no_sesi || item.code || opnameSessionId(item) }}</td>
                  <td>{{ formatDate(item.opname_date || item.date || item.tanggal || item.created_at) }}</td>
                  <td>
                    <StatusBadge
                      :status="opnameBadgeStatus(item.status)"
                      :label="opnameStatusLabel(item.status)"
                      class="rounded-pill px-2 py-1"
                    />
                  </td>
                  <td class="text-center pe-3">
                    <button
                      class="btn btn-sm btn-light text-info action-btn me-1"
                      @click="toggleSession(item)"
                      title="View Details"
                    >
                      <i class="bi bi-eye-fill"></i>
                    </button>
                    <button
                      v-if="!isOpnameCompleted(item.status)"
                      class="btn btn-sm btn-light text-success action-btn me-1"
                      @click="completeSession(item)"
                      title="Selesaikan"
                    >
                      <i class="bi bi-check-lg"></i>
                    </button>
                    <button
                      v-if="!isOpnameCompleted(item.status)"
                      class="btn btn-sm btn-light text-danger action-btn"
                      @click="deleteSession(item)"
                      title="Hapus"
                    >
                      <i class="bi bi-trash-fill"></i>
                    </button>
                  </td>
                </tr>
                <tr v-if="expandedSession === opnameSessionId(item)">
                  <td colspan="5" class="bg-light p-0">
                    <div class="p-3">
                      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                          <h6 class="fw-bold mb-0">Detail Perhitungan</h6>
                          <div v-if="item.notes" class="small text-muted mt-1">Catatan: {{ item.notes }}</div>
                        </div>
                        <button
                          v-if="!isOpnameCompleted(item.status)"
                          class="btn btn-success btn-modern btn-sm"
                          :disabled="savingDetails"
                          @click="saveDetailResults(item)"
                        >
                          <i class="bi bi-save me-1"></i> Simpan Hasil
                        </button>
                      </div>
                      <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 bg-white">
                          <thead class="table-light text-uppercase fs-7">
                            <tr>
                              <th class="py-2">No</th>
                              <th class="py-2">Produk</th>
                              <th class="py-2">Batch</th>
                              <th class="py-2">Stok Sistem</th>
                              <th class="py-2">Stok Fisik</th>
                              <th class="py-2">Selisih</th>
                              <th class="py-2">Catatan</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="(det, dIdx) in getSessionDetails(opnameSessionId(item))" :key="opnameDetailId(det) || dIdx">
                              <td>{{ dIdx + 1 }}</td>
                              <td class="fw-semibold">{{ det.product_name || det.nama_produk || det.product?.name }}</td>
                              <td>{{ det.batch || det.batch_number || '-' }}</td>
                              <td>{{ opnameSystemStock(det) }}</td>
                              <td>
                                <input
                                  v-if="!isOpnameCompleted(item.status)"
                                  type="number"
                                  class="form-control form-control-sm"
                                  style="width: 100px;"
                                  v-model.number="det.physical_quantity"
                                  min="0"
                                  :max="MAX_QTY"
                                  step="1"
                                  inputmode="numeric"
                                />
                                <span v-else>{{ opnamePhysicalStock(det) }}</span>
                              </td>
                              <td :class="opnameDifference(det) !== 0 ? 'text-danger fw-bold' : ''">
                                {{ opnameDifference(det) }}
                              </td>
                              <td>
                                <span>{{ det.notes || det.keterangan || '-' }}</span>
                              </td>
                            </tr>
                            <tr v-if="getSessionDetails(opnameSessionId(item)).length === 0">
                              <td colspan="7" class="text-center py-3 text-muted">Belum ada detail perhitungan.</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
              <tr v-if="filteredSessions.length === 0">
                <td colspan="5" class="text-center py-4 text-muted">Belum ada sesi stock opname.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
          <div class="text-muted small">
            Menampilkan {{ paginationStart }} sampai {{ paginationEnd }} dari {{ filteredSessions.length }} entri
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

    <!-- Modal Buat Sesi -->
    <div class="modal fade" id="createSessionModal" tabindex="-1" aria-hidden="true" ref="createSessionModalRef">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-15 shadow">
          <div class="modal-header border-0 pb-0 pt-4 px-4">
            <h5 class="modal-title fw-bold">Buat Sesi Stock Opname</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="createSession">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label small fw-semibold">Tanggal</label>
                  <input type="date" class="form-control" v-model="sessionForm.opname_date" :min="todayIso()" required />
                </div>
                <div class="col-12">
                  <label class="form-label small fw-semibold">Catatan</label>
                  <textarea class="form-control" rows="3" v-model="sessionForm.notes" inputmode="text" placeholder="Catatan sesi (opsional)"></textarea>
                </div>
              </div>
              <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
                <button type="button" class="btn btn-secondary btn-modern px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-modern px-4" :disabled="creating">
                  <span v-if="creating" class="spinner-border spinner-border-sm me-1"></span>
                  Buat Sesi
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
import { ref, reactive, computed, watch, onMounted } from "vue";
import * as bootstrap from "bootstrap";
import Swal from "sweetalert2";
import opnameService from "../../services/opname";
import { getActionErrorMessage } from "../../services/apiNotifier";
import { usePagination } from "../../composables/usePagination";
import { useConfirm } from "../../composables/useConfirm";
import {
  OPNAME_STATUS,
  buildOpnameDetailPayloads,
  isOpnameCompleted,
  normalizeOpnameStatus,
  opnameBadgeStatus,
  opnameDetailId,
  opnameDifference,
  opnamePhysicalStock,
  opnameSessionId,
  opnameStatusLabel,
  opnameSystemStock,
  runWithConcurrency,
} from "../../composables/useOpnameForm";
import { MAX_QTY, PAGE_SIZE_OPTIONS } from "../../constants/app";
import { formatDate, todayIso } from "../../utils/date";
import BasePagination from "../../components/BasePagination.vue";
import PageErrorAlert from "../../components/PageErrorAlert.vue";
import StatusBadge from "../../components/StatusBadge.vue";

const { confirm } = useConfirm();

const sessions = ref([]);
const detailsMap = reactive({});
const expandedSession = ref(null);
const creating = ref(false);
const savingDetails = ref(false);
const loadError = ref(null);
const searchQuery = ref("");

const createSessionModalRef = ref(null);
let modalInstance = null;

const sessionForm = ref({
  opname_date: todayIso(),
  notes: "",
});

const stats = computed(() => {
  const all = sessions.value;
  return {
    total: all.length,
    completed: all.filter((s) => normalizeOpnameStatus(s.status) === OPNAME_STATUS.COMPLETED).length,
    in_progress: all.filter((s) => normalizeOpnameStatus(s.status) === OPNAME_STATUS.IN_PROGRESS).length,
    draft: all.filter((s) => normalizeOpnameStatus(s.status) === OPNAME_STATUS.DRAFT).length,
  };
});

const filteredSessions = computed(() => {
  const query = String(searchQuery.value || "").trim().toLowerCase();
  if (!query) return sessions.value;
  return sessions.value.filter((session) =>
    [session.opname_number, session.no_sesi, session.code, opnameSessionId(session), session.status]
      .some((value) => String(value ?? "").toLowerCase().includes(query)),
  );
});

const {
  currentPage,
  itemsPerPage,
  totalPages,
  visiblePages,
  paginatedItems: paginatedSessions,
  paginationStart,
  paginationEnd,
  changePage,
  resetPage,
} = usePagination(filteredSessions);

watch(searchQuery, resetPage);

const getSessionDetails = (sessionId) => detailsMap[sessionId] || [];

const loadSessions = async () => {
  try {
    const res = await opnameService.getSessions();
    if (res.data?.status) sessions.value = res.data.data || [];
    else sessions.value = Array.isArray(res.data) ? res.data : [];
  } catch (err) {
    sessions.value = [];
    loadError.value = err;
  }
};

const loadSessionDetails = async (sessionId) => {
  try {
    const res = await opnameService.getSession(sessionId);
    const data = res.data?.data || res.data || [];
    const details = data.details || data.products || data.items || data;
    detailsMap[sessionId] = (Array.isArray(details) ? details : []).map((detail) => ({
      ...detail,
      physical_quantity: detail.physical_quantity ?? detail.physical_stock ?? detail.stok_fisik ?? 0,
      notes: detail.notes ?? detail.keterangan ?? "",
    }));
  } catch (err) {
    detailsMap[sessionId] = [];
    loadError.value = err;
  }
};

const loadAll = () => {
  loadError.value = null;
  return loadSessions();
};

const toggleSession = async (item) => {
  const sessionId = opnameSessionId(item);
  if (expandedSession.value === sessionId) {
    expandedSession.value = null;
    return;
  }
  expandedSession.value = sessionId;
  if (!detailsMap[sessionId]) {
    await loadSessionDetails(sessionId);
  }
};

const openCreateModal = () => {
  if (!modalInstance) return;
  sessionForm.value = {
    opname_date: todayIso(),
    notes: "",
  };
  modalInstance.show();
};

const createSession = async () => {
  creating.value = true;
  try {
    const res = await opnameService.createSession({
      opname_date: sessionForm.value.opname_date,
      notes: sessionForm.value.notes,
    });
    const sessionData = res.data?.data || res.data;
    const sessionId = opnameSessionId(sessionData);
    if (!sessionId) throw new Error("ID sesi opname tidak tersedia.");
    Swal.fire("Berhasil!", "Sesi berhasil dibuat dengan data produk.", "success");
    modalInstance?.hide();
    await loadAll();
    expandedSession.value = sessionId;
    await loadSessionDetails(sessionId);
  } catch (err) {
    loadError.value = err;
    const msg = getActionErrorMessage(err, err.message || "Terjadi kesalahan");
    Swal.fire("Gagal!", msg, "error");
  } finally {
    creating.value = false;
  }
};

const saveDetailResults = async (item) => {
  if (savingDetails.value) return;
  const sessionId = opnameSessionId(item);
  const details = getSessionDetails(sessionId);
  if (!details.length) {
    Swal.fire("Info", "Tidak ada detail untuk disimpan.", "info");
    return;
  }

  const payloads = buildOpnameDetailPayloads(sessionId, details);
  if (payloads.some((payload) => !payload.id_batch)) {
    Swal.fire("Validasi", "Detail opname tanpa batch tidak dapat disimpan.", "warning");
    return;
  }

  savingDetails.value = true;
  try {
    await runWithConcurrency(payloads, async (payload) => {
      const res = await opnameService.addDetail(payload);
      if (res.data?.status === false) {
        throw new Error(res.data?.message || "Gagal menyimpan detail opname.");
      }
      return res;
    });
    Swal.fire("Berhasil!", "Hasil perhitungan berhasil disimpan.", "success");
    await loadSessionDetails(sessionId);
  } catch (err) {
    loadError.value = err;
    const msg = getActionErrorMessage(err, err.message || "Terjadi kesalahan");
    Swal.fire("Gagal!", msg, "error");
  } finally {
    savingDetails.value = false;
  }
};

const completeSession = async (item) => {
  const sessionId = opnameSessionId(item);
  const sessionNumber = item.opname_number || item.no_sesi || item.code || sessionId;
  const confirmed = await confirm({
    title: "Selesaikan Sesi?",
    text: `Selesaikan sesi ${sessionNumber}? Perbedaan stok akan otomatis dibuat penyesuaian.`,
    icon: "question",
    confirmButtonText: "Ya, Selesaikan!",
    cancelButtonText: "Batal",
    swal: { confirmButtonColor: "#2563eb", cancelButtonColor: "#6c757d" },
  });
  if (!confirmed) return;
  try {
    await opnameService.completeSession(sessionId);
    Swal.fire("Berhasil!", "Sesi telah selesai dan penyesuaian telah dibuat.", "success");
    await loadAll();
  } catch (err) {
    loadError.value = err;
    const msg = getActionErrorMessage(err, "Terjadi kesalahan");
    Swal.fire("Gagal!", msg, "error");
  }
};

const deleteSession = async (item) => {
  const confirmed = await confirm({
    title: "Hapus Sesi?",
    text: "Apakah Anda yakin ingin menghapus sesi ini?",
    confirmButtonText: "Ya, Hapus!",
    cancelButtonText: "Batal",
    swal: { confirmButtonColor: "#e53e3e", cancelButtonColor: "#6c757d" },
  });
  if (!confirmed) return;
  try {
    await opnameService.deleteSession(opnameSessionId(item));
    Swal.fire("Dihapus!", "Sesi berhasil dihapus.", "success");
    if (expandedSession.value === opnameSessionId(item)) expandedSession.value = null;
    await loadAll();
  } catch (err) {
    loadError.value = err;
    const msg = getActionErrorMessage(err, "Gagal menghapus sesi.");
    Swal.fire("Gagal!", msg, "error");
  }
};

const refreshData = () => {
  searchQuery.value = "";
  resetPage();
  loadAll();
};

onMounted(() => {
  modalInstance = new bootstrap.Modal(createSessionModalRef.value);
  loadAll();
});
</script>

<style scoped>
.opname-page {
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
</style>
