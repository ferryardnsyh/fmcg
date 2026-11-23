<template>
  <div class="user-page">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div class="d-flex align-items-center">
        <div class="header-icon-box me-3">
          <i class="bi bi-people-fill fs-4 text-primary"></i>
        </div>
        <div>
          <h4 class="mb-0 fw-bold text-dark">User Management</h4>
          <p class="text-muted small mb-0">Kelola akun pengguna sistem</p>
        </div>
      </div>
      <button class="btn btn-primary btn-modern" @click="openAddModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah User
      </button>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
      <div class="col-xl-4 col-md-6">
        <div class="card stat-card border-0 shadow-sm rounded-15 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Total User</p>
              <h3 class="fw-bold mb-0 text-dark">{{ stats.total }}</h3>
            </div>
            <div class="stat-icon bg-primary-subtle text-primary">
              <i class="bi bi-people fs-4"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-md-6">
        <div class="card stat-card border-0 shadow-sm rounded-15 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">User Aktif</p>
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

    <!-- Table -->
    <div class="card border-0 shadow-sm rounded-15 mb-4">
      <div class="card-body p-4">
        <PageErrorAlert :error="loadError" fallback="Gagal memuat data user." @retry="loadUsers" />
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
                placeholder="Cari user..."
                v-model="searchQuery"
              />
            </div>
          </div>
          <div class="col-auto">
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
            <thead class="table-light text-uppercase fs-7">
              <tr>
                <th class="py-3 ps-3">No</th>
                <th class="py-3">Nama</th>
                <th class="py-3">Username</th>
                <th class="py-3">Role</th>
                <th class="py-3">Status</th>
                <th class="py-3">Dibuat</th>
                <th class="py-3 text-center pe-3">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in paginatedUsers" :key="item.id_user ?? item.id">
                <td class="ps-3">
                  {{ paginationStart + index }}
                </td>
                <td class="fw-semibold">{{ item.name }}</td>
                <td><code>{{ item.username }}</code></td>
                <td>
                  <span
                    :class="[
                      'badge rounded-pill px-2 py-1',
                      item.role === 'SUPERADMIN'
                        ? 'bg-danger-subtle text-danger'
                        : item.role === 'ADMIN'
                          ? 'bg-warning-subtle text-warning'
                          : 'bg-info-subtle text-info',
                    ]"
                  >
                    {{ item.role }}
                  </span>
                </td>
                <td>
                  <span
                    :class="[
                      'badge rounded-pill px-2 py-1',
                      item.status === 'AKTIF'
                        ? 'bg-success-subtle text-success'
                        : 'bg-danger-subtle text-danger',
                    ]"
                  >
                    {{ item.status }}
                  </span>
                </td>
                <td>{{ formatDate(item.created_at) }}</td>
                <td class="text-center pe-3">
                  <button
                    class="btn btn-sm btn-light text-primary action-btn me-1"
                    @click="openPasswordModal(item)"
                    title="Ubah Password"
                  >
                    <i class="bi bi-key-fill"></i>
                  </button>
                  <button
                    class="btn btn-sm btn-light text-warning action-btn me-1"
                    @click="openEditModal(item)"
                    title="Edit"
                  >
                    <i class="bi bi-pencil-fill"></i>
                  </button>
                  <button
                    class="btn btn-sm btn-light text-danger action-btn"
                    @click="deleteUser(item)"
                    title="Hapus"
                  >
                    <i class="bi bi-trash-fill"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="filteredUsers.length === 0">
                <td colspan="7" class="text-center py-4 text-muted">
                  Tidak ada data user ditemukan.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
          <div class="text-muted small">
            Menampilkan {{ paginationStart }} sampai {{ paginationEnd }} dari
            {{ filteredUsers.length }} entri
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

    <!-- Modal Tambah / Edit User -->
    <div
      class="modal fade"
      id="userModal"
      tabindex="-1"
      aria-hidden="true"
      ref="userModalRef"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-15 shadow">
          <div class="modal-header border-0 pb-0 pt-4 px-4">
            <h5 class="modal-title fw-bold">
              {{ isEditMode ? "Edit User" : "Tambah User" }}
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="saveUser">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label small fw-semibold">Nama Lengkap</label>
                  <input
                    type="text"
                    inputmode="text"
                    autocomplete="name"
                    maxlength="200"
                    class="form-control"
                    v-model="form.name"
                    placeholder="Masukkan nama lengkap"
                    required
                  />
                </div>
                <div class="col-12">
                  <label class="form-label small fw-semibold">Username</label>
                  <input
                    type="text"
                    inputmode="text"
                    autocomplete="username"
                    maxlength="100"
                    class="form-control"
                    v-model="form.username"
                    placeholder="Masukkan username untuk login"
                    required
                  />
                </div>
                <div class="col-12" v-if="!isEditMode">
                  <label class="form-label small fw-semibold">Password</label>
                  <input
                    type="password"
                    autocomplete="new-password"
                    minlength="6"
                    maxlength="255"
                    class="form-control"
                    v-model="form.password"
                    placeholder="Minimal 6 karakter"
                    required
                  />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Role</label>
                  <select class="form-select" v-model="form.role" required>
                    <option value="SUPERADMIN">SUPERADMIN</option>
                    <option value="ADMIN">ADMIN</option>
                    <option value="GUDANG">GUDANG</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Status</label>
                  <select class="form-select" v-model="form.status" required>
                    <option value="AKTIF">AKTIF</option>
                    <option value="NONAKTIF">NONAKTIF</option>
                  </select>
                </div>
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
                  {{ saving ? "Menyimpan..." : isEditMode ? "Update" : "Simpan" }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Ubah Password -->
    <div
      class="modal fade"
      id="passwordModal"
      tabindex="-1"
      aria-hidden="true"
      ref="passwordModalRef"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-15 shadow">
          <div class="modal-header border-0 pb-0 pt-4 px-4">
            <h5 class="modal-title fw-bold">Ubah Password</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body p-4">
            <p class="text-muted mb-3">
              Ubah password untuk user <strong>{{ passwordForm.name }}</strong>
            </p>
            <form @submit.prevent="changePassword">
              <div class="mb-3">
                <label class="form-label small fw-semibold">Password Lama</label>
                  <input
                    type="password"
                    autocomplete="current-password"
                    maxlength="255"
                    class="form-control"
                    v-model="passwordForm.current_password"
                    placeholder="Masukkan password lama"
                    required
                  />
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">Password Baru</label>
                  <input
                    type="password"
                    autocomplete="new-password"
                    minlength="6"
                    maxlength="255"
                    class="form-control"
                    v-model="passwordForm.new_password"
                    placeholder="Minimal 6 karakter"
                    required
                  />
              </div>
              <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
                <button
                  type="button"
                  class="btn btn-secondary btn-modern px-4"
                  data-bs-dismiss="modal"
                >
                  Batal
                </button>
                <button type="submit" class="btn btn-warning btn-modern px-4" :disabled="changingPassword">
                  {{ changingPassword ? "Mengubah..." : "Ubah Password" }}
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
import userService from "../../services/user";
import { usePagination } from "../../composables/usePagination";
import { useConfirm } from "../../composables/useConfirm";
import { useAuth } from "../../stores/auth";
import { formatDate } from "../../utils/date";
import BasePagination from "../../components/BasePagination.vue";
import PageErrorAlert from "../../components/PageErrorAlert.vue";

const { user: authUser } = useAuth();
const { confirm } = useConfirm();

const searchQuery = ref("");
const loadError = ref(null);

const isEditMode = ref(false);
const saving = ref(false);
const changingPassword = ref(false);
const editId = ref(null);

const userModalRef = ref(null);
const passwordModalRef = ref(null);

let userModalInstance = null;
let passwordModalInstance = null;

const users = ref([]);

const form = ref({
  name: "",
  username: "",
  password: "",
  role: "GUDANG",
  status: "AKTIF",
});

const passwordForm = ref({
  id: null,
  name: "",
  current_password: "",
  new_password: "",
});

const loadUsers = async () => {
  loadError.value = null;
  try {
    const response = await userService.getUsers();
    if (response.data.status) {
      users.value = response.data.data;
    }
  } catch (err) {
    users.value = [];
    loadError.value = err;
  }
};

const stats = computed(() => ({
  total: users.value.length,
  aktif: users.value.filter((u) => u.status === "AKTIF").length,
  nonAktif: users.value.filter((u) => u.status !== "AKTIF").length,
}));

const filteredUsers = computed(() => {
  if (!searchQuery.value) return users.value;
  const q = searchQuery.value.toLowerCase();
  return users.value.filter(
    (u) =>
      u.name.toLowerCase().includes(q) ||
      u.username.toLowerCase().includes(q) ||
      u.role.toLowerCase().includes(q),
  );
});

const {
  currentPage,
  totalPages,
  visiblePages,
  paginatedItems: paginatedUsers,
  paginationStart,
  paginationEnd,
  changePage,
  resetPage,
} = usePagination(filteredUsers, { perPage: 8 });

watch(searchQuery, resetPage);

onMounted(() => {
  userModalInstance = new bootstrap.Modal(userModalRef.value);
  passwordModalInstance = new bootstrap.Modal(passwordModalRef.value);
  loadUsers();
});

const openAddModal = () => {
  isEditMode.value = false;
  editId.value = null;
  form.value = { name: "", username: "", password: "", role: "GUDANG", status: "AKTIF" };
  userModalInstance.show();
};

const openEditModal = (item) => {
  isEditMode.value = true;
  editId.value = item.id_user ?? item.id;
  form.value = {
    name: item.name,
    username: item.username,
    password: "",
    role: item.role,
    status: item.status,
  };
  userModalInstance.show();
};

const openPasswordModal = (item) => {
  passwordForm.value = {
    id: item.id_user ?? item.id,
    name: item.name,
    current_password: "",
    new_password: "",
  };
  passwordModalInstance.show();
};

const saveUser = async () => {
  saving.value = true;
  try {
    if (isEditMode.value) {
      await userService.update(editId.value, {
        name: form.value.name,
        username: form.value.username,
        role: form.value.role,
        status: form.value.status,
      });
      Swal.fire("Berhasil!", "User berhasil diupdate.", "success");
    } else {
      await userService.create(form.value);
      Swal.fire("Berhasil!", "User berhasil dibuat.", "success");
    }
    await loadUsers();
    userModalInstance.hide();
  } catch (err) {
    const msg = err.response?.data?.message || "Terjadi kesalahan";
    Swal.fire("Gagal!", msg, "error");
  }
  saving.value = false;
};

const changePassword = async () => {
  changingPassword.value = true;
  try {
    await userService.changePassword(passwordForm.value.id, {
      current_password: passwordForm.value.current_password,
      new_password: passwordForm.value.new_password,
    });
    Swal.fire("Berhasil!", "Password berhasil diubah.", "success");
    passwordModalInstance.hide();
  } catch (err) {
    const msg = err.response?.data?.message || "Terjadi kesalahan";
    Swal.fire("Gagal!", msg, "error");
  }
  changingPassword.value = false;
};

const deleteUser = async (item) => {
  const currentUserId = authUser.value?.id_user ?? authUser.value?.id;
  const targetUserId = item.id_user ?? item.id;
  if (currentUserId !== null && currentUserId !== undefined && String(targetUserId) === String(currentUserId)) {
    Swal.fire("Peringatan!", "Tidak bisa menghapus akun sendiri.", "warning");
    return;
  }

  const confirmed = await confirm({
    title: "Hapus User?",
    text: `Apakah Anda yakin ingin menghapus user "${item.name}"?`,
    confirmButtonText: "Ya, Hapus!",
    cancelButtonText: "Batal",
    swal: { confirmButtonColor: "#e53e3e", cancelButtonColor: "#6c757d" },
  });
  if (!confirmed) return;

  try {
    await userService.delete(targetUserId);
    Swal.fire("Dihapus!", "User berhasil dihapus.", "success");
    await loadUsers();
  } catch (err) {
    const msg = err.response?.data?.message || "Gagal menghapus user";
    Swal.fire("Gagal!", msg, "error");
  }
};

const refreshData = () => {
  searchQuery.value = "";
  resetPage();
  loadUsers();
};
</script>

<style scoped>
.user-page {
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
