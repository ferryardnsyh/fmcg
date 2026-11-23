import api from "./api";

export default {
  barangMasuk(params) {
    return api.get("/laporan/barang-masuk", { params });
  },

  barangKeluar(params) {
    return api.get("/laporan/barang-keluar", { params });
  },

  stok(params) {
    return api.get("/laporan/stok", { params });
  },

  stokOpname(params) {
    return api.get("/laporan/stok-opname", { params });
  },

  adjustments(params) {
    return api.get("/laporan/adjustments", { params });
  },

  expired(params) {
    return api.get("/laporan/expired", { params });
  },

  exportPdf(params) {
    return api.get("/laporan/export-pdf", { params });
  },
};
