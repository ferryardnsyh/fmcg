<template>
  <div class="stok-page">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div class="d-flex align-items-center">
        <div class="header-icon-box me-3">
          <i class="bi bi-sliders fs-4 text-primary"></i>
        </div>
        <div>
          <h4 class="mb-0 fw-bold text-dark">Stock Adjustment</h4>
          <p class="text-muted small mb-0">Penyesuaian dan Koreksi Stok</p>
        </div>
      </div>
      <button v-if="isAdmin" class="btn btn-primary btn-modern" @click="openAdjustmentModal">
        <i class="bi bi-plus-lg me-1"></i> Buat Penyesuaian
      </button>
    </div>

    <div class="card border-0 shadow-sm rounded-15 mb-4">
      <div class="card-body p-4">
        <PageErrorAlert :error="loadError" fallback="Gagal memuat data penyesuaian stok." @retry="loadAll" />
        <div class="d-flex justify-content-end mb-3">
          <button class="btn btn-outline-secondary btn-modern d-flex align-items-center" @click="loadAll">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
          </button>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light fs-7">
              <tr>
                <th class="py-3 ps-3">No</th>
                <th class="py-3">Nomor Penyesuaian Stok</th>
                <th class="py-3">Nomor Batch</th>
                <th class="py-3">Tipe</th>
                <th class="py-3">Jumlah</th>
                <th class="py-3">Status</th>
                <th class="py-3">Alasan</th>
                <th class="py-3 pe-3 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in adjustments" :key="item.id_adjustment || index">
                <td class="ps-3">{{ index + 1 }}</td>
                <td class="fw-semibold text-primary">{{ item.id_adjustment || '-' }}</td>
                <td>{{ item.batch_number || item.id_batch || '-' }}</td>
                <td>
                  <span
                    :class="[
                      'badge rounded-pill px-2 py-1',
                      (item.type || '').toUpperCase() === 'INCREASE'
                        ? 'bg-success-subtle text-success'
                        : 'bg-danger-subtle text-danger',
                    ]"
                  >
                    {{ (item.type || '').toUpperCase() === 'INCREASE' ? '+' : '-' }}
                  </span>
                </td>
                <td>{{ formatAdjustmentQuantity(item.quantity) }}</td>
                <td>
                  <StatusBadge
                    :status="adjustmentBadgeStatus(item.status)"
                    :label="adjustmentStatusLabel(item.status)"
                    class="rounded-pill px-2 py-1"
                  />
                </td>
                <td>{{ item.reason || '-' }}</td>
                <td class="pe-3 text-center">
                  <template v-if="isAdjustmentPending(item.status) && isAdmin">
                    <button class="btn btn-sm btn-success btn-modern me-1" title="Setujui" @click="approveAdjustment(item)">
                      <i class="bi bi-check-lg"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger btn-modern" title="Tolak" @click="rejectAdjustment(item)">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </template>
                  <span v-else class="text-muted small">-</span>
                </td>
              </tr>
              <tr v-if="adjustments.length === 0">
                <td colspan="8" class="text-center py-4 text-muted">Belum ada penyesuaian stok.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Buat Penyesuaian -->
    <div class="modal fade" id="adjustmentModal" tabindex="-1" aria-hidden="true" ref="adjustmentModalRef">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-15 shadow">
          <div class="modal-header border-0 pb-0 pt-4 px-4">
            <h5 class="modal-title fw-bold">Buat Penyesuaian Stok</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="saveAdjustment">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label small fw-semibold">Batch</label>
                  <select class="form-select" v-model.number="adjForm.batch_id" required>
                    <option value="" disabled>Pilih Batch</option>
                    <option v-for="b in batches" :key="b.id_batch" :value="b.id_batch">
                      {{ b.batch_number }} - {{ b.product_name }} (Qty: {{ b.quantity }})
                    </option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Tipe</label>
                  <select class="form-select" v-model="adjForm.type" required>
                    <option value="" disabled>Pilih Tipe</option>
                    <option value="INCREASE">INCREASE (+)</option>
                    <option value="DECREASE">DECREASE (-)</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Quantity</label>
                  <input type="number" class="form-control" v-model.number="adjForm.quantity" min="1" :max="MAX_QTY" step="1" inputmode="numeric" required />
                </div>
                <div class="col-12">
                  <label class="form-label small fw-semibold">Alasan</label>
                  <textarea class="form-control" rows="3" v-model="adjForm.reason" maxlength="200" inputmode="text" placeholder="Alasan penyesuaian stok" required></textarea>
                </div>
              </div>
              <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
                <button type="button" class="btn btn-secondary btn-modern px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-modern px-4" :disabled="saving">
                  <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                  Simpan
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
import { ref, onMounted } from "vue";
import * as bootstrap from "bootstrap";
import Swal from "sweetalert2";
import stockService from "../../services/stock";
import { getActionErrorMessage } from "../../services/apiNotifier";
import batchService from "../../services/batch";
import { useAuth } from "../../stores/auth";
import { useConfirm } from "../../composables/useConfirm";
import {
  adjustmentBadgeStatus,
  adjustmentStatusLabel,
  buildAdjustmentPayload,
  formatAdjustmentQuantity,
  isAdjustmentPending,
  validateAdjustmentForm,
} from "../../composables/useAdjustmentForm";
import { MAX_QTY } from "../../constants/app";
import PageErrorAlert from "../../components/PageErrorAlert.vue";
import StatusBadge from "../../components/StatusBadge.vue";

const { isAdmin } = useAuth();
const { confirm } = useConfirm();

const adjustments = ref([]);
const batches = ref([]);
const saving = ref(false);
const loadError = ref(null);
const adjustmentModalRef = ref(null);
let modalInstance = null;

const adjForm = ref({
  batch_id: "",
  type: "",
  quantity: 1,
  reason: "",
});

const toArray = (data) => (Array.isArray(data) ? data : []);

const loadAdjustments = async () => {
  try {
    const res = await stockService.getAdjustments();
    adjustments.value = toArray(res.data?.status ? res.data.data : res.data);
  } catch (err) {
    adjustments.value = [];
    loadError.value = err;
  }
};

const loadBatches = async () => {
  try {
    const res = await batchService.getBatches();
    if (res.data?.status) batches.value = res.data.data;
  } catch (err) {
    batches.value = [];
    loadError.value = err;
  }
};

const loadAll = () => {
  loadError.value = null;
  return Promise.all([loadAdjustments(), loadBatches()]);
};

const openAdjustmentModal = () => {
  if (!modalInstance) return;
  adjForm.value = { batch_id: "", type: "", quantity: 1, reason: "" };
  loadBatches();
  modalInstance.show();
};

const approveAdjustment = async (item) => {
  const approved = await confirm({
    title: "Setujui Penyesuaian?",
    text: `Setujui penyesuaian ${item.id_adjustment}? Stok batch akan diperbarui.`,
    icon: "question",
    confirmButtonText: "Ya, Setujui",
    cancelButtonText: "Batal",
  });
  if (!approved) return;
  try {
    const res = await stockService.approveAdjustment(item.id_adjustment);
    if (res.data?.status) {
      Swal.fire("Berhasil!", res.data.message || "Adjustment disetujui.", "success");
      await loadAdjustments();
    } else {
      loadError.value = res.data?.message || "Gagal menyetujui penyesuaian.";
      Swal.fire("Gagal!", res.data?.message || "Terjadi kesalahan", "error");
    }
  } catch (err) {
    loadError.value = err;
    Swal.fire("Gagal!", getActionErrorMessage(err, "Terjadi kesalahan"), "error");
  }
};

const rejectAdjustment = async (item) => {
  const rejected = await confirm({
    title: "Tolak Penyesuaian?",
    text: `Tolak penyesuaian ${item.id_adjustment}?`,
    icon: "warning",
    confirmButtonText: "Ya, Tolak",
    cancelButtonText: "Batal",
  });
  if (!rejected) return;
  try {
    const res = await stockService.rejectAdjustment(item.id_adjustment);
    if (res.data?.status) {
      Swal.fire("Berhasil!", res.data.message || "Adjustment ditolak.", "success");
      await loadAdjustments();
    } else {
      loadError.value = res.data?.message || "Gagal menolak penyesuaian.";
      Swal.fire("Gagal!", res.data?.message || "Terjadi kesalahan", "error");
    }
  } catch (err) {
    loadError.value = err;
    Swal.fire("Gagal!", getActionErrorMessage(err, "Terjadi kesalahan"), "error");
  }
};

const saveAdjustment = async () => {
  const validationMessage = validateAdjustmentForm(adjForm.value);
  if (validationMessage) {
    Swal.fire("Validasi", validationMessage, "warning");
    return;
  }

  saving.value = true;
  try {
    await stockService.storeAdjustment(buildAdjustmentPayload(adjForm.value));
    Swal.fire("Berhasil!", "Penyesuaian stok berhasil disimpan.", "success");
    modalInstance?.hide();
    await loadAdjustments();
  } catch (err) {
    loadError.value = err;
    const msg = getActionErrorMessage(err, "Terjadi kesalahan");
    Swal.fire("Gagal!", msg, "error");
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  modalInstance = new bootstrap.Modal(adjustmentModalRef.value);
  loadAll();
});
</script>

<style scoped>
.stok-page {
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
</style>
