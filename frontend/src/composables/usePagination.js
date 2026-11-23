import { computed, ref, watch } from "vue";
import { paginationPages } from "../utils/pagination";
import { PAGE_SIZE } from "../constants/app";

export function usePagination(itemsRef, options = {}) {
  const initialPage = options.initialPage || 1;
  const perPage = options.perPage || PAGE_SIZE;
  const currentPage = ref(initialPage);
  const itemsPerPage = ref(perPage);

  const totalPages = computed(() => Math.ceil((itemsRef.value?.length || 0) / itemsPerPage.value));

  const visiblePages = computed(() => paginationPages(currentPage.value, totalPages.value, 2));

  const paginatedItems = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return (itemsRef.value || []).slice(start, start + itemsPerPage.value);
  });

  const paginationStart = computed(() => {
    const total = itemsRef.value?.length || 0;
    if (total === 0) return 0;
    return (currentPage.value - 1) * itemsPerPage.value + 1;
  });

  const paginationEnd = computed(() => {
    const total = itemsRef.value?.length || 0;
    const end = currentPage.value * itemsPerPage.value;
    return end > total ? total : end;
  });

  function changePage(page) {
    if (page >= 1 && page <= totalPages.value) currentPage.value = page;
  }

  function resetPage() {
    currentPage.value = 1;
  }

  watch(itemsRef, () => {
    if (currentPage.value > totalPages.value) currentPage.value = Math.max(1, totalPages.value);
  });

  return {
    currentPage,
    itemsPerPage,
    totalPages,
    visiblePages,
    paginatedItems,
    paginationStart,
    paginationEnd,
    changePage,
    resetPage,
  };
}
