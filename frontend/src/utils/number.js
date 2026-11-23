export function coerceFiniteNumber(value, fallback = 0) {
  const numeric = Number(value);
  return Number.isFinite(numeric) ? numeric : fallback;
}

export function toFiniteNumber(value, fallback = 0) {
  if (value === null || value === undefined || value === "") return fallback;
  return coerceFiniteNumber(value, fallback);
}

export function isIntegerInRange(value, min, max = Infinity) {
  return Number.isInteger(value) && value >= min && value <= max;
}
