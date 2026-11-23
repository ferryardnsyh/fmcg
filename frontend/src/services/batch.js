import api from "./api";

export default {
  statistic() {
    return api.get("/batches/statistic");
  },

  getBatches() {
    return api.get("/batches");
  },

  getBatch(id) {
    return api.get(`/batches/${id}`);
  },
};
