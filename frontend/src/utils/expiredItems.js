import { EXPIRED_THRESHOLD } from "../constants/app";
import { formatDate } from "./date";
import { toFiniteNumber } from "./number";

export const EXPIRED_CRITICAL_DAYS = EXPIRED_THRESHOLD.CRITICAL_DAYS;

function firstValue(item, keys, fallback = "") {
  for (const key of keys) {
    const value = item?.[key];
    if (value !== null && value !== undefined && value !== "") return value;
  }
  return fallback;
}

export function normalizeExpiredItem(item) {
  const rawDaysLeft = toFiniteNumber(
    firstValue(item, ["days_left", "sisa_hari", "days_remaining", "days_expired"], 0),
  );
  const daysLeft = Math.max(0, rawDaysLeft);
  return {
    id: firstValue(item, ["id_batch", "id"]),
    name: firstValue(item, ["name", "product_name", "nama_produk"]),
    batchNumber: firstValue(item, ["batch_number", "batch_no"]),
    quantity: firstValue(item, ["quantity", "stok", "stok_quantity"], "-"),
    expiryDate: formatDate(firstValue(item, ["expiry_date", "tanggal_expired", "expired_date"])),
    daysLeft,
  };
}

export function expiredDaysTone(daysLeft) {
  return daysLeft <= EXPIRED_CRITICAL_DAYS ? "EXPIRED" : "WARNING";
}
