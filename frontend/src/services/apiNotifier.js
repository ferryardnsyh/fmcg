import Swal from "sweetalert2";
import router from "../router";
import { clearAuthUser } from "../stores/auth";

export function getActionErrorMessage(error, fallback) {
  return error?.response?.data?.message || fallback;
}

export function notifyActionError(error, fallback = "Terjadi kesalahan pada server") {
  Swal.fire({
    icon: "error",
    title: "Gagal",
    text: error?.response?.data?.message || error?.message || fallback,
  });
}

export function notifySessionExpired() {
  clearAuthUser();
  if (window.location.pathname === "/login") return;
  Swal.fire({
    icon: "warning",
    title: "Sesi Berakhir",
    text: "Sesi login Anda telah berakhir. Silakan login kembali.",
    confirmButtonText: "Login Ulang",
  }).then(() => {
    router.push("/login");
  });
}
