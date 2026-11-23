import api from "./api";

export default {
  all(params) {
    return api.get("/categories", { params });
  },

  getAllActive() {
    return api.get("/categories/all");
  },

  getById(id) {
    return api.get(`/categories/${id}`);
  },

  getNextCode() {
    return api.get("/categories/next-code");
  },

  create(data) {
    return api.post("/categories", data);
  },

  update(id, data) {
    return api.put(`/categories/${id}`, data);
  },

  delete(id) {
    return api.delete(`/categories/${id}`);
  },
};
