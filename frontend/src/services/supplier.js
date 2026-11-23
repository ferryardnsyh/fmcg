import api from "./api";

export default {
  getSuppliers() {
    return api.get("/suppliers");
  },

  getSupplier(id) {
    return api.get(`/suppliers/${id}`);
  },

  create(data) {
    return api.post("/suppliers", data);
  },

  update(id, data) {
    return api.put(`/suppliers/${id}`, data);
  },

  delete(id) {
    return api.delete(`/suppliers/${id}`);
  },
};
