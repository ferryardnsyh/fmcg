import api from "./api";

export default {
  getList() {
    return api.get("/inbound");
  },

  getById(id) {
    return api.get(`/inbound/${id}`);
  },

  getProductsBySupplier(supplierId) {
    return api.get(`/inbound/products-by-supplier/${supplierId}`);
  },

  create(data) {
    return api.post("/inbound", data);
  },

  update(id, data) {
    return api.put(`/inbound/${id}`, data);
  },

  delete(id) {
    return api.delete(`/inbound/${id}`);
  },

  complete(id) {
    return api.post(`/inbound/${id}/complete`);
  },
};
