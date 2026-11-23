import api from "./api";

export default {
  getList() {
    return api.get("/outbound");
  },

  getById(id) {
    return api.get(`/outbound/${id}`);
  },

  create(data) {
    return api.post("/outbound", data);
  },

  update(id, data) {
    return api.put(`/outbound/${id}`, data);
  },

  delete(id) {
    return api.delete(`/outbound/${id}`);
  },

  complete(id) {
    return api.post(`/outbound/${id}/complete`);
  },
};
