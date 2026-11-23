import api from "./api";

export default {
  statistic() {
    return api.get("/products/statistic");
  },

  getProducts(params) {
    return api.get("/products", { params });
  },

  getProduct(id) {
    return api.get(`/products/${id}`);
  },

  create(data) {
    return api.post("/products", data);
  },

  update(id, data) {
    return api.put(`/products/${id}`, data);
  },

  delete(id) {
    return api.delete(`/products/${id}`);
  },
};
