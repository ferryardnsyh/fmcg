import { MAX_QTY } from "../constants/app";
import { todayIso, isValidIsoDate } from "../utils/date";
import { isIntegerInRange, toFiniteNumber } from "../utils/number";

export { isValidIsoDate };

let rowSequence = 0;

export function createInboundDetailRow(source = {}) {
  return {
    rowKey: `inbound-detail-${++rowSequence}`,
    id_inbound_detail: source.id_inbound_detail ?? null,
    id_product: source.id_product ?? "",
    batch_number: source.batch_number ?? "",
    quantity: toFiniteNumber(source.quantity, 1),
    expiry_date: source.expiry_date ?? "",
    id_rack: source.id_rack ?? "",
  };
}

export function validateInboundForm(form, details) {
  const poNumber = String(form?.po_number || "").trim();
  const deliveryNoteNumber = String(form?.delivery_note_number || "").trim();

  if (!poNumber) return "Nomor PO harus diisi.";
  if (poNumber.length > 50) return "Nomor PO maksimal 50 karakter.";
  if (!deliveryNoteNumber) return "Nomor Surat Jalan harus diisi.";
  if (deliveryNoteNumber.length > 50) return "Nomor Surat Jalan maksimal 50 karakter.";
  if (!form?.id_supplier) return "Supplier harus dipilih.";
  if (!isValidIsoDate(form?.reception_date)) return "Tanggal masuk harus diisi dengan benar.";
  if (form.reception_date < todayIso()) return "Tanggal masuk tidak boleh sebelum hari ini.";
  if (!details?.length) return "Minimal harus ada satu detail barang.";

  const seenBatch = new Set();

  for (let i = 0; i < details.length; i++) {
    const row = i + 1;
    const detail = details[i];

    if (!detail.id_product) return `Baris ${row}: Produk harus dipilih.`;

    const batchNumber = String(detail.batch_number || "").trim();
    if (!batchNumber) return `Baris ${row}: Batch/Lot harus diisi.`;
    if (batchNumber.length > 100) return `Baris ${row}: Batch/Lot maksimal 100 karakter.`;

    const batchKey = `${detail.id_product}|${batchNumber.toUpperCase()}`;
    if (seenBatch.has(batchKey)) return `Baris ${row}: Batch/Lot duplikat untuk produk yang sama.`;
    seenBatch.add(batchKey);

    if (!isIntegerInRange(detail.quantity, 1, MAX_QTY)) {
      return `Baris ${row}: Jumlah harus bilangan bulat antara 1 dan ${MAX_QTY}.`;
    }
    if (!isValidIsoDate(detail.expiry_date)) {
      return `Baris ${row}: Tanggal expired wajib diisi dengan benar.`;
    }
    if (!detail.id_rack) return `Baris ${row}: Rak wajib dipilih.`;
  }

  return "";
}

export function buildInboundPayload(form, details) {
  return {
    po_number: String(form?.po_number || "").trim(),
    delivery_note_number: String(form?.delivery_note_number || "").trim(),
    id_supplier: form?.id_supplier,
    reception_date: form?.reception_date,
    notes: form?.notes,
    details: (details || []).map((detail) => ({
      id_inbound_detail: detail.id_inbound_detail ?? null,
      id_product: detail.id_product,
      batch_number: String(detail.batch_number || "").trim(),
      quantity: detail.quantity,
      expiry_date: detail.expiry_date || null,
      id_rack: detail.id_rack ? Number(detail.id_rack) : null,
    })),
  };
}
