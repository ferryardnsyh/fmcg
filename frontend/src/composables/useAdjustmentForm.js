import { MAX_QTY } from "../constants/app";
import { isIntegerInRange, toFiniteNumber } from "../utils/number";

export function normalizeAdjustmentStatus(status) {
  const value = String(status ?? "").trim().toUpperCase();
  return ["PENDING", "APPROVED", "REJECTED"].includes(value) ? value : "";
}

export function adjustmentBadgeStatus(status) {
  switch (normalizeAdjustmentStatus(status)) {
    case "APPROVED":
      return "SELESAI";
    case "REJECTED":
      return "EXPIRED";
    default:
      return "PENDING";
  }
}

export function adjustmentStatusLabel(status) {
  switch (normalizeAdjustmentStatus(status)) {
    case "PENDING":
      return "Menunggu";
    case "APPROVED":
      return "Disetujui";
    case "REJECTED":
      return "Ditolak";
    default:
      return status || "-";
  }
}

export function isAdjustmentPending(status) {
  return normalizeAdjustmentStatus(status) === "PENDING";
}

export function toAdjustmentQuantity(value, fallback = null) {
  return toFiniteNumber(value, fallback);
}

export function formatAdjustmentQuantity(value) {
  const quantity = toAdjustmentQuantity(value);
  return quantity === null ? "-" : quantity;
}

export function validateAdjustmentForm(form) {
  if (!form?.batch_id) return "Batch harus dipilih.";
  if (!["INCREASE", "DECREASE"].includes(form?.type)) {
    return "Tipe penyesuaian harus dipilih.";
  }
  const quantity = toAdjustmentQuantity(form?.quantity);
  if (!isIntegerInRange(quantity, 1, MAX_QTY)) {
    return `Quantity harus bilangan bulat antara 1 dan ${MAX_QTY}.`;
  }
  if (!String(form?.reason || "").trim()) return "Alasan harus diisi.";
  return "";
}

export function buildAdjustmentPayload(form) {
  return {
    batch_id: form?.batch_id,
    type: form?.type,
    quantity: toAdjustmentQuantity(form?.quantity),
    reason: form?.reason,
  };
}
