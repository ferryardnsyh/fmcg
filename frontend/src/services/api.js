import axios from "axios";
import { notifySessionExpired } from "./apiNotifier";

const API_URL =
  import.meta.env.VITE_API_URL ||
  `${window.location.protocol}//${window.location.hostname}:8080/api`;
const MUTATING_METHODS = ["post", "put", "patch", "delete"];

let csrfToken = null;
let csrfHeaderName = "X-CSRF-TOKEN";
let csrfTokenRequest = null;

const api = axios.create({
  baseURL: API_URL,
  headers: {
    "Content-Type": "application/json",
  },
  withCredentials: true,
});

const csrfApi = axios.create({
  baseURL: API_URL,
  withCredentials: true,
});

function isMutating(config) {
  return MUTATING_METHODS.includes(String(config?.method || "get").toLowerCase());
}

function setHeader(config, name, value) {
  if (typeof config.headers?.set === "function") {
    config.headers.set(name, value);
    return;
  }
  config.headers = config.headers || {};
  config.headers[name] = value;
}

function fetchCsrfToken() {
  if (!csrfTokenRequest) {
    csrfTokenRequest = csrfApi
      .get("/csrf-token")
      .then((response) => {
        const data = response.data?.data || {};
        if (data.header_name) csrfHeaderName = data.header_name;
        if (!data.csrf_hash) throw new Error("Token CSRF tidak tersedia");
        csrfToken = data.csrf_hash;
        return csrfToken;
      })
      .finally(() => {
        csrfTokenRequest = null;
      });
  }
  return csrfTokenRequest;
}

api.interceptors.request.use(async (config) => {
  if (!isMutating(config)) return config;
  if (!csrfToken) await fetchCsrfToken();
  setHeader(config, csrfHeaderName, csrfToken);
  return config;
});

api.interceptors.response.use(
  (response) => {
    if (isMutating(response.config || {})) {
      csrfToken = null;
      fetchCsrfToken().catch(() => {});
    }
    return response;
  },
  async (error) => {
    const original = error.config;
    const status = error.response?.status;
    const body = error.response?.data || {};
    const message = String(body.message || body.error || "");
    const csrfError =
      status === 403 &&
      !original?._csrfRetried &&
      !String(original?.url || "").includes("/csrf-token") &&
      (body.code === "CSRF_TOKEN_MISMATCH" ||
        /csrf|not allowed|tidak diizinkan|aksi yang diminta/i.test(message) ||
        message === "");

    if (csrfError && original) {
      original._csrfRetried = true;
      csrfToken = null;
      const token = await fetchCsrfToken();
      setHeader(original, csrfHeaderName, token);
      return api(original);
    }

    if (status === 401) {
      csrfToken = null;
      const url = error.config?.url || "";
      const isAuthRequest = url.includes("/login") || url.includes("/logout");
      if (!isAuthRequest) {
        notifySessionExpired();
      }
    }
    return Promise.reject(error);
  },
);

export default api;
