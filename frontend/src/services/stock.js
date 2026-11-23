import api from "./api";

export default {
  realtime() {
    return api.get("/stock/realtime");
  },

  mutations() {
    return api.get("/stock/mutations");
  },

  lowStock() {
    return api.get("/stock/low-stock");
  },

  getAdjustments() {
    return api.get("/stock/adjustments");
  },

  showAdjustment(id) {
    return api.get(`/stock/adjustments/${id}`);
  },

  storeAdjustment(data) {
    return api.post("/stock/adjustments", data);
  },

  approveAdjustment(id) {
    return api.post(`/stock/adjustments/${id}/approve`);
  },

  rejectAdjustment(id) {
    return api.post(`/stock/adjustments/${id}/reject`);
  },
};
