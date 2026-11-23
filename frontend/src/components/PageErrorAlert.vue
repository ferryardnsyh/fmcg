<template>
  <div
    v-if="message"
    class="alert alert-danger d-flex justify-content-between align-items-center gap-3 py-2 px-3 mb-3"
    role="alert"
  >
    <span>
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      {{ message }}
    </span>
    <button
      v-if="retry"
      type="button"
      class="btn btn-sm btn-outline-danger text-nowrap"
      @click="$emit('retry')"
    >
      Coba Lagi
    </button>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  error: { default: null },
  fallback: { type: String, default: "Gagal memuat data." },
  retry: { type: Boolean, default: true },
});

defineEmits(["retry"]);

const message = computed(() => {
  const error = props.error;
  if (!error) return "";
  return (
    error?.response?.data?.message ||
    error?.message ||
    (typeof error === "string" ? error : props.fallback)
  );
});
</script>
