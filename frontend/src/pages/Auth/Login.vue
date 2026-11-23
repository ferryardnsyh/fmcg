<template>
  <div class="login-container">
    <!-- LEFT PANEL -->
    <div class="left-panel">
      <div class="brand-accent"></div>
      <div class="brand-content">
        <h2>CV. Putra Sumber Sari</h2>
        <div class="logo-divider"></div>
        <p class="tagline">Warehouse Management System</p>
      </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="right-panel">
      <div class="login-box">
        <div class="login-header">
          <div class="login-icon">
            <img src="../../assets/logo.png" alt="PSSWMS" class="login-logo" />
          </div>
          <h2>Selamat Datang</h2>
          <p>Silakan login untuk melanjutkan ke sistem</p>
        </div>

        <!-- Username -->
        <div class="mb-3">
          <label class="form-label">Username</label>
          <div class="input-group">
            <span class="input-group-text">
              <i class="bi bi-person"></i>
            </span>
            <input
              v-model="username"
              type="text"
              inputmode="text"
              autocomplete="username"
              maxlength="100"
              class="form-control"
              placeholder="Masukkan username"
            />
          </div>
        </div>

        <!-- Password -->
        <div class="mb-4">
          <label class="form-label">Password</label>
          <div class="input-group">
            <span class="input-group-text">
              <i class="bi bi-lock"></i>
            </span>
            <input
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="current-password"
              maxlength="255"
              class="form-control"
              placeholder="Masukkan password"
              @keyup.enter="doLogin"
            />
            <span
              class="input-group-text password-toggle"
              @click="togglePassword"
            >
              <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
            </span>
          </div>
        </div>

        <!-- BUTTON -->
        <button class="btn login-btn" @click="doLogin" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
          {{ loading ? "Memproses..." : "LOGIN" }}
        </button>

        <div class="login-footer">
          <small>PSS WMS &bull; Version 1.0</small>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";

import { useRouter } from "vue-router";

import Swal from "sweetalert2";

import authService from "../../services/auth";
import { setAuthUser } from "../../stores/auth";

const router = useRouter();

const username = ref("");

const password = ref("");

const showPassword = ref(false);

const loading = ref(false);

const togglePassword = () => {
  showPassword.value = !showPassword.value;
};

const doLogin = async () => {
  if (username.value === "") {
    Swal.fire({
      icon: "warning",

      title: "Peringatan",

      text: "Username masih kosong",
    });

    return;
  }

  if (password.value === "") {
    Swal.fire({
      icon: "warning",

      title: "Peringatan",

      text: "Password masih kosong",
    });

    return;
  }

  loading.value = true;

  try {
    const response = await authService.login({
      username: username.value,

      password: password.value,
    });

    if (response.data.status) {
      const loginUser = response.data.data || {};
      setAuthUser({
        ...loginUser,
        id: loginUser.id ?? loginUser.id_user,
        id_user: loginUser.id_user ?? loginUser.id,
      });

      Swal.fire({
        icon: "success",
        title: "Login Berhasil",
        text: response.data.message,
        timer: 1200,
        showConfirmButton: false,
      });

      router.push("/dashboard");
    } else {
      Swal.fire({
        icon: "error",
        title: "Login Gagal",
        text: response.data.message,
      });
    }
  } catch (error) {
    const msg =
      error.response?.data?.message || "Backend tidak dapat dihubungi";
    Swal.fire({
      icon: "error",
      title:
        error.response?.data?.message
          ? "Login Gagal"
          : "Server Error",
      text: msg,
    });
  }

  loading.value = false;
};
</script>

<style scoped>
.login-container {
  display: flex;

  width: 100%;

  height: 100vh;
}

/* ===========================
        LEFT PANEL (BRAND)
=========================== */

.left-panel {
  width: 65%;

  background-image: url("../../assets/pss.jpg");

  background-size: cover;

  background-position: center 70%;

  background-repeat: no-repeat;

  display: flex;

  flex-direction: column;

  justify-content: center;

  align-items: center;

  position: relative;

  padding: 40px;
}

.left-panel::before {
  content: "";

  position: absolute;

  inset: 0;

  background: rgba(30, 41, 59, 0.35);

}

.brand-accent {
  position: absolute;

  top: 0;

  left: 0;

  right: 0;

  height: 4px;

  background: linear-gradient(90deg, #dc2626 0%, #dc2626 50%, #1e293b 50%, #1e293b 100%);
}

.brand-content {
  text-align: center;

  max-width: 460px;

  position: relative;

  z-index: 1;
}

.logo {
  width: 150px;

  margin-bottom: 20px;

  border-radius: 16px;

  box-shadow: 0 0.5rem 1.5rem rgba(30, 41, 59, 0.25);
}

.brand-content h2 {
  color: #ffffff;

  font-weight: 700;

  font-size: 26px;

  letter-spacing: 0.3px;

  margin-bottom: 10px;
}

.logo-divider {
  width: 40px;

  height: 3px;

  background: linear-gradient(90deg, #dc2626 0%, #dc2626 50%, #ffffff 50%, #ffffff 100%);

  margin: 0 auto 14px;

  border-radius: 2px;
}

.tagline {
  color: rgba(255, 255, 255, 0.9);

  font-size: 14px;

  font-weight: 500;

  font-style: italic;

  margin-bottom: 0;
}

/* ===========================
        RIGHT PANEL (FORM)
=========================== */

.right-panel {
  width: 35%;

  background: #ffffff;

  display: flex;

  justify-content: center;

  align-items: center;

  padding: 40px 24px;
}

.login-box {
  width: 380px;
  max-width: 100%;
}

.login-header {
  text-align: center;

  margin-bottom: 30px;
}

.login-icon {
  width: 56px;

  height: 56px;

  margin: 0 auto 16px;

  border-radius: 16px;

  display: flex;

  align-items: center;

  justify-content: center;

  font-size: 26px;
}

.login-logo {
  width: 56px;

  height: 56px;

  border-radius: 12px;

  object-fit: contain;

  background: #ffffff;
}

.login-header h2 {
  color: #1e293b;

  font-weight: 700;

  font-size: 24px;

  margin-bottom: 6px;
}

.login-header p {
  color: #94a3b8;

  font-size: 14px;

  margin-bottom: 0;
}

.form-label {
  font-size: 13px;

  font-weight: 600;

  color: #475569;

  margin-bottom: 6px;
}

.input-group-text {
  background: #ffffff;

  border-color: #e2e8f0;

  color: #94a3b8;
}

.form-control {
  height: 48px;

  border-radius: 8px;

  border-color: #cbd5e1;

  padding: 10px 14px;

  font-size: 14px;
}

.form-control:focus,
.input-group:focus-within .input-group-text {
  border-color: #2563eb;

  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.input-group:focus-within .input-group-text {
  border-color: #2563eb;

  color: #2563eb;
}

.password-toggle {
  cursor: pointer;
}

.login-btn {
  width: 100%;

  height: 48px;

  background: #2563eb;

  color: #ffffff;

  font-weight: 600;

  border: none;

  border-radius: 8px;

  transition: all 0.2s ease;
}

.login-btn:hover:not(:disabled) {
  background: #1d4ed8;

  color: #ffffff;

  transform: translateY(-1px);

  box-shadow: 0 0.5rem 1.25rem rgba(37, 99, 235, 0.3);
}

.login-btn:disabled {
  opacity: 0.7;
}

.login-footer {
  margin-top: 26px;

  text-align: center;

  padding-top: 16px;

  border-top: 1px solid #f1f5f9;
}

.login-footer small {
  color: #94a3b8;

  font-size: 12px;
}

@media (max-width: 992px) {
  .left-panel {
    display: none;
  }

  .right-panel {
    width: 100%;

    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  }
}
</style>
