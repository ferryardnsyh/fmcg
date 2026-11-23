import api from "./api";

export default {
  getSuppliers() {
    return api.get("/master/suppliers");
  },

  getCategories() {
    return api.get("/master/categories");
  },

  getRackLocations() {
    return api.get("/master/rack-locations");
  },
};
