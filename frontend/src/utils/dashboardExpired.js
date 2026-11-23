import api from "../services/api";

export async function fetchDashboardExpiredProducts() {
  const res = await api.get("/dashboard/expired-products");
  if (!res.data?.status) {
    throw new Error(res.data?.message || "Gagal memuat data produk expired");
  }
  return Array.isArray(res.data.data) ? res.data.data : [];
}
