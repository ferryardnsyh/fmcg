import { isValidIsoDate, todayIso } from "./date";

export const CODE_PATTERN = "[A-Za-z0-9][A-Za-z0-9._-]*";
export const DOCUMENT_PATTERN = "[A-Za-z0-9][A-Za-z0-9 ._/-]*";
export const PHONE_PATTERN = "[0-9]*";

const CODE_REGEX = /^[A-Za-z0-9][A-Za-z0-9._-]*$/;
const DOCUMENT_REGEX = /^[A-Za-z0-9][A-Za-z0-9 .\/_-]*$/;
const PHONE_REGEX = /^[0-9]+$/;

export function digitsOnly(value) {
  return String(value ?? "").replace(/\D/g, "");
}

export function isValidCode(value, maxLength = 50) {
  const text = String(value ?? "").trim();
  return CODE_REGEX.test(text) && text.length <= maxLength;
}

export function isValidDocumentNumber(value, maxLength = 50) {
  const text = String(value ?? "").trim();
  return DOCUMENT_REGEX.test(text) && text.length <= maxLength;
}

export function isValidPhone(value, maxLength = 50) {
  const text = String(value ?? "").trim();
  return text === "" || (PHONE_REGEX.test(text) && text.length <= maxLength);
}

export function validateText(value, label, maxLength, required = true) {
  const text = String(value ?? "").trim();
  if (!text) return required ? `${label} wajib diisi.` : "";
  if (text.length > maxLength) return `${label} maksimal ${maxLength} karakter.`;
  return "";
}

export function validateCode(value, label, maxLength = 50, required = true) {
  const text = String(value ?? "").trim();
  if (!text) return required ? `${label} wajib diisi.` : "";
  if (text.length > maxLength) return `${label} maksimal ${maxLength} karakter.`;
  if (!CODE_REGEX.test(text)) return `${label} hanya boleh huruf, angka, titik, garis bawah, atau tanda hubung.`;
  return "";
}

export function validateDocumentNumber(value, label, maxLength = 50, required = true) {
  const text = String(value ?? "").trim();
  if (!text) return required ? `${label} wajib diisi.` : "";
  if (text.length > maxLength) return `${label} maksimal ${maxLength} karakter.`;
  if (!DOCUMENT_REGEX.test(text)) return `${label} mengandung karakter yang tidak diizinkan.`;
  return "";
}

export function validateDateNotPast(value, label) {
  if (!isValidIsoDate(value)) return `${label} wajib diisi dengan benar.`;
  if (value < todayIso()) return `${label} tidak boleh sebelum hari ini.`;
  return "";
}
