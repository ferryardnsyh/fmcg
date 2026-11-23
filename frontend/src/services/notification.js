import api from "./api";

export default {
  getAll(params = {}) {
    return api.get("/notifications", { params });
  },
  getUnread() {
    return api.get("/notifications/unread");
  },
  markRead(id) {
    return api.put(`/notifications/${id}/read`);
  },
  markAllRead() {
    return api.put("/notifications/read-all");
  },
  generate() {
    return api.post("/notifications/generate");
  },
};
