import { MAX_QTY } from "../constants/app";

let rowSequence = 0;

export function createOutboundDetailRow(source = {}) {
  return {
    rowKey: `outbound-detail-${++rowSequence}`,
    id_product: source.id_product ?? "",
    id_batch: source.id_batch ?? "",
    quantity: source.quantity ?? 1,
    availableBatches: source.availableBatches ?? [],
    maxQuantity: source.maxQuantity ?? 0,
  };
}

export function toNumericQuantity(value, fallback = 0) {
  if (value === null || value === undefined || value === "") return fallback;
  const numeric = Number(value);
  return Number.isFinite(numeric) ? numeric : fallback;
}

export function hasAvailableStock(batch) {
  return toNumericQuantity(batch?.quantity) > 0;
}

export function availableBatchesFor(allBatches, idProduct) {
  return (allBatches || []).filter(
    (batch) =>
      String(batch.id_product) === String(idProduct) && hasAvailableStock(batch),
  );
}

export function getStockLimit(row) {
  return toNumericQuantity(row?.maxQuantity);
}

export function stockQuantityError(row) {
  const quantity = row?.quantity;
  if (!Number.isInteger(quantity) || quantity < 1) {
    return "Jumlah harus bilangan bulat positif.";
  }
  if (quantity > MAX_QTY) return `Jumlah maksimal ${MAX_QTY}.`;
  const limit = getStockLimit(row);
  if (quantity > limit) return `Melebihi stok tersedia (max: ${limit})`;
  return "";
}

export function validateOutboundDetails(details) {
  if (!details?.length) return "Minimal satu barang harus ditambahkan.";

  for (const row of details) {
    if (
      !row.id_product ||
      !row.id_batch ||
      !Number.isInteger(row.quantity) ||
      row.quantity < 1 ||
      row.quantity > MAX_QTY
    ) {
      return "Detail barang harus memiliki produk, batch, dan quantity bulat positif.";
    }
    if (row.quantity > getStockLimit(row)) {
      return "Quantity melebihi stok tersedia untuk salah satu barang.";
    }
  }

  return "";
}

export function buildOutboundPayload(form, details) {
  return {
    delivery_number: form?.delivery_number || undefined,
    delivery_date: form?.delivery_date,
    notes: form?.notes,
    details: (details || []).map((row) => ({
      id_product: row.id_product,
      id_batch: row.id_batch,
      quantity: row.quantity,
    })),
  };
}
