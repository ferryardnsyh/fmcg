<template>
  <span :class="badgeClass"><slot>{{ label }}</slot></span>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  status: { type: [String, Number, Boolean], default: "" },
  label: { type: String, default: "" },
});

const normalized = computed(() => String(props.status ?? "").trim().toUpperCase());

const badgeClass = computed(() => {
  const map = {
    AKTIF: "badge bg-success-subtle text-success",
    ACTIVE: "badge bg-success-subtle text-success",
    NONAKTIF: "badge bg-secondary-subtle text-secondary",
    INACTIVE: "badge bg-secondary-subtle text-secondary",
    EXPIRED: "badge bg-danger-subtle text-danger",
    WARNING: "badge bg-warning-subtle text-warning",
    PENDING: "badge bg-warning-subtle text-warning",
    SELESAI: "badge bg-success-subtle text-success",
    DRAFT: "badge bg-secondary-subtle text-secondary",
  };
  return map[normalized.value] || "badge bg-light text-dark border";
});
</script>
