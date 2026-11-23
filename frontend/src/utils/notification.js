import { timeAgo } from "./date";

export const NOTIFICATION_TYPES = {
  out_of_stock: { label: "Stok Habis", icon: "bi bi-x-octagon-fill" },
  expired: { label: "Kadaluarsa", icon: "bi bi-x-circle-fill" },
  low_stock: { label: "Stok Menipis", icon: "bi bi-exclamation-triangle-fill" },
  near_expiry: { label: "Akan Kadaluarsa", icon: "bi bi-clock-fill" },
  pending_approval: { label: "Butuh Approval", icon: "bi bi-hourglass-split" },
  inbound_completed: { label: "Barang Masuk", icon: "bi bi-arrow-down-circle-fill" },
  outbound_completed: { label: "Barang Keluar", icon: "bi bi-arrow-up-circle-fill" },
  opname_selisih: { label: "Opname", icon: "bi bi-arrow-left-right" },
  stock_adjustment: { label: "Adjustment", icon: "bi bi-sliders" },
  user_activity: { label: "Aktivitas User", icon: "bi bi-person-circle" },
};

export const NOTIFICATION_PRIORITIES = {
  critical: "Critical",
  high: "Warning",
  medium: "Information",
  low: "Low",
};

export const NOTIFICATION_ROUTES = {
  inbound: "/barang-masuk",
  outbound: "/barang-keluar",
  product: "/produk",
  batch: "/batch",
  adjustment: "/stok",
  opname_session: "/stock-opname",
  user: "/user",
};

export function notificationIcon(type) {
  return NOTIFICATION_TYPES[type]?.icon || "bi bi-bell-fill";
}

export function notificationLabel(type) {
  return NOTIFICATION_TYPES[type]?.label || type || "-";
}

export function notificationPriorityLabel(priority) {
  return NOTIFICATION_PRIORITIES[priority] || priority || "-";
}

export function notificationPath(referenceType) {
  return NOTIFICATION_ROUTES[referenceType] || null;
}

export function formatNotificationTime(value) {
  return timeAgo(value);
}
