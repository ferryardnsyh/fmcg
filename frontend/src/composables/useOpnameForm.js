import { toFiniteNumber } from "../utils/number";

export const OPNAME_STATUS = Object.freeze({
  DRAFT: "DRAFT",
  IN_PROGRESS: "IN_PROGRESS",
  COMPLETED: "COMPLETED",
});

const COMPLETED_VALUES = ["completed", "complete", "selesai", "done"];
const IN_PROGRESS_VALUES = ["in_progress", "inprogress", "active", "aktif", "process"];

export function normalizeOpnameStatus(status) {
  const value = String(status ?? "")
    .trim()
    .toLowerCase()
    .replace(/[\s-]+/g, "_");
  if (COMPLETED_VALUES.includes(value)) return OPNAME_STATUS.COMPLETED;
  if (IN_PROGRESS_VALUES.includes(value)) return OPNAME_STATUS.IN_PROGRESS;
  return OPNAME_STATUS.DRAFT;
}

export function opnameBadgeStatus(status) {
  switch (normalizeOpnameStatus(status)) {
    case OPNAME_STATUS.COMPLETED:
      return "SELESAI";
    case OPNAME_STATUS.IN_PROGRESS:
      return "PENDING";
    default:
      return "DRAFT";
  }
}

export function opnameStatusLabel(status) {
  switch (normalizeOpnameStatus(status)) {
    case OPNAME_STATUS.COMPLETED:
      return "Selesai";
    case OPNAME_STATUS.IN_PROGRESS:
      return "In Progress";
    default:
      return "Draft";
  }
}

export function isOpnameCompleted(status) {
  return normalizeOpnameStatus(status) === OPNAME_STATUS.COMPLETED;
}

export function opnameSessionId(session) {
  return session?.id ?? session?.id_opname ?? session?.session_id ?? "";
}

export function opnameDetailId(detail) {
  return detail?.id ?? detail?.id_opname_detail ?? detail?.id_detail ?? "";
}

export function toNumericQuantity(value, fallback = 0) {
  return toFiniteNumber(value, fallback);
}

export function opnameDifference(detail) {
  const system = toNumericQuantity(
    detail?.system_stock ?? detail?.stok_sistem ?? detail?.system_quantity,
  );
  const physical = toNumericQuantity(detail?.physical_quantity);
  return physical - system;
}

export function opnameSystemStock(detail) {
  return toNumericQuantity(
    detail?.system_stock ?? detail?.stok_sistem ?? detail?.system_quantity,
  );
}

export function opnamePhysicalStock(detail) {
  return toNumericQuantity(
    detail?.physical_stock ?? detail?.stok_fisik ?? detail?.physical_quantity,
  );
}

export function buildOpnameDetailPayloads(sessionId, details) {
  return (details || []).map((detail) => ({
    id_opname: sessionId,
    id_batch: detail?.id_batch ?? detail?.batch_id ?? "",
    physical_quantity: toNumericQuantity(detail?.physical_quantity),
  }));
}

export async function runWithConcurrency(items, worker, limit = 5) {
  const source = items || [];
  const size = Math.max(1, Math.min(limit, source.length || 1));
  const results = [];
  let nextIndex = 0;
  let firstError = null;

  const runner = async () => {
    while (nextIndex < source.length) {
      const index = nextIndex++;
      try {
        results[index] = await worker(source[index], index);
      } catch (err) {
        if (!firstError) firstError = err;
      }
    }
  };

  await Promise.all(Array.from({ length: size }, runner));
  if (firstError) throw firstError;
  return results;
}
