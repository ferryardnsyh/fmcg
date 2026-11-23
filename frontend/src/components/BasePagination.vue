<template>
  <nav v-if="totalPages > 0">
    <ul class="pagination mb-0">
      <li :class="['page-item', { disabled: currentPage === 1 }]">
        <button class="page-link" @click="$emit('change', currentPage - 1)">Previous</button>
      </li>
      <li
        v-for="(page, idx) in visiblePages"
        :key="page === '...' ? `ellipsis-${idx}` : page"
        :class="['page-item', { active: currentPage === page, disabled: page === '...' }]"
      >
        <button v-if="page !== '...'" class="page-link" @click="$emit('change', page)">{{ page }}</button>
        <span v-else class="page-link text-muted">...</span>
      </li>
      <li :class="['page-item', { disabled: currentPage === totalPages || totalPages === 0 }]">
        <button class="page-link" @click="$emit('change', currentPage + 1)">Next</button>
      </li>
    </ul>
  </nav>
</template>

<script setup>
defineProps({
  currentPage: { type: Number, required: true },
  totalPages: { type: Number, required: true },
  visiblePages: { type: Array, required: true },
});

defineEmits(["change"]);
</script>
