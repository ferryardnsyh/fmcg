import { computed, ref } from "vue";

const ADMIN_ROLES = ["SUPERADMIN", "ADMIN"];
const storedUser = readUser();

export const authUser = ref(storedUser);

function readUser() {
  try {
    const raw = localStorage.getItem("user");
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    return parsed && typeof parsed === "object" ? parsed : null;
  } catch {
    return null;
  }
}

export function setAuthUser(user) {
  authUser.value = user && typeof user === "object" ? user : null;
  if (authUser.value) {
    localStorage.setItem("user", JSON.stringify(authUser.value));
  } else {
    localStorage.removeItem("user");
  }
}

export function clearAuthUser() {
  setAuthUser(null);
  localStorage.removeItem("token");
}

export function can(role) {
  const userRole = authUser.value?.role;
  if (!userRole) return false;
  if (Array.isArray(role)) return role.includes(userRole);
  return userRole === role;
}

export const isAdmin = computed(() => ADMIN_ROLES.includes(authUser.value?.role));

export function useAuth() {
  return {
    user: authUser,
    isAdmin,
    can,
    setUser: setAuthUser,
    clear: clearAuthUser,
  };
}
