<template>
  <div class="card shadow-sm rounded-15">
    <div class="card-header fw-bold bg-white border-0 pt-3 px-3">
      <h5 class="mb-0">
        <i class="bi bi-calendar-x text-danger me-2"></i>
        Barang Hampir Expired
      </h5>
    </div>

    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light fs-7">
          <tr>
            <th class="py-3 ps-3">Produk</th>
            <th class="py-3">Nomor Batch</th>
            <th class="py-3">Stok</th>
            <th class="py-3">Tanggal Kedaluwarsa</th>
            <th class="py-3 pe-3">Sisa Hari</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="item in rows" :key="item.batchNumber">
            <td>{{ item.name }}</td>

            <td>
              <code>{{ item.batchNumber }}</code>
            </td>

            <td>{{ item.quantity }}</td>

            <td>{{ item.expiryDate }}</td>

            <td>
              <StatusBadge
                :status="expiredDaysTone(item.daysLeft)"
                :label="`${item.daysLeft} Hari`"
                class="rounded-pill"
              />
            </td>
          </tr>

          <tr v-if="rows.length === 0">
            <td colspan="5" class="text-center py-3 text-muted">
              <i class="bi bi-check-circle me-1"></i>
              Tidak ada barang yang akan expired dalam {{ warningDays }} hari ke depan.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { EXPIRED_THRESHOLD } from "../../constants/app";
import { expiredDaysTone, normalizeExpiredItem } from "../../utils/expiredItems";
import StatusBadge from "../StatusBadge.vue";

const props = defineProps({
  items: { type: Array, default: () => [] },
});

const rows = computed(() => props.items.map(normalizeExpiredItem));
const warningDays = EXPIRED_THRESHOLD.WARNING_DAYS;
</script>

<style scoped>
.rounded-15 {
  border-radius: 15px !important;
}
</style>
