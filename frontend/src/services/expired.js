import api from "./api";

export default {
  getList(params) {
    return api.get("/expired", { params });
  },

  nearExpiry() {
    return api.get("/expired/near-expiry");
  },

  expiredToday() {
    return api.get("/expired/today");
  },
};
