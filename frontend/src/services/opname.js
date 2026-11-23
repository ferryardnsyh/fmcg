import api from "./api";

export default {
  getSessions() {
    return api.get("/opname/sessions");
  },

  getSession(id) {
    return api.get(`/opname/sessions/${id}`);
  },

  createSession(data) {
    return api.post("/opname/sessions", data);
  },

  addDetail(data) {
    return api.post("/opname/details", data);
  },

  completeSession(id) {
    return api.post(`/opname/sessions/${id}/complete`);
  },

  deleteSession(id) {
    return api.delete(`/opname/sessions/${id}`);
  },
};
