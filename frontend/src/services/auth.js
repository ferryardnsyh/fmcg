import api from "./api";

export default {
  login(data) {
    return api.post("/login", data);
  },

  logout() {
    return api.post("/logout");
  },

  me() {
    return api.get("/me");
  },
};
