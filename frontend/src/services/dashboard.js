import api from "./api";

export default {
  summary() {
    return api.get("/dashboard/summary");
  },

  chart() {
    return api.get("/dashboard/chart");
  },

  topOutbound() {
    return api.get("/dashboard/top-outbound");
  },
};
