<template>
  <div class="dashboard">
    <PageErrorAlert :error="loadError" fallback="Gagal memuat data dashboard." @retry="refresh" />

    <div class="summary">
      <DashboardCard
        title="Total Produk"
        :value="summary.total_produk"
        icon="bi bi-box-seam"
        color="#3498db"
      />
      <DashboardCard
        title="Total Stok"
        :value="summary.total_stok"
        icon="bi bi-boxes"
        color="#2ecc71"
      />
      <DashboardCard
        title="Barang Masuk"
        :value="summary.total_barang_masuk_hari_ini"
        icon="bi bi-arrow-down-circle"
        color="#00bcd4"
      />
      <DashboardCard
        title="Barang Keluar"
        :value="summary.total_barang_keluar_hari_ini"
        icon="bi bi-arrow-up-circle"
        color="#ff9800"
      />
      <DashboardCard
        title="Stok Hampir Habis"
        :value="summary.stok_rendah"
        icon="bi bi-exclamation-triangle"
        color="#f1c40f"
      />
      <DashboardCard
        title="Stok Expired"
        :value="summary.mendekati_expired"
        icon="bi bi-calendar-x"
        color="#e74c3c"
      />
    </div>

    <div class="row g-4 mt-2">
      <div class="col-xl-6">
        <OverviewPieChart :summary="summary" :loading="summaryLoading" />
      </div>
      <div class="col-xl-6">
        <TrendChart />
      </div>
    </div>

    <div class="row g-4 mt-2">
      <div class="col-12">
        <ExpiredTable :items="expiredItems" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import DashboardCard from "../../components/DashboardCard.vue";
import OverviewPieChart from "../../components/dashboard/OverviewPieChart.vue";
import TrendChart from "../../components/dashboard/TrendChart.vue";
import ExpiredTable from "../../components/dashboard/ExpiredTable.vue";
import PageErrorAlert from "../../components/PageErrorAlert.vue";
import dashboardService from "../../services/dashboard";
import { fetchDashboardExpiredProducts } from "../../utils/dashboardExpired";
import { useAutoRefresh } from "../../composables/useAutoRefresh";

const summary = ref({
  total_produk: 0, total_batch: 0, total_stok: 0,
  total_barang_masuk_hari_ini: 0, total_barang_keluar_hari_ini: 0,
  stok_rendah: 0, mendekati_expired: 0,
});
const expiredItems = ref([]);
const summaryLoading = ref(true);
const loadError = ref(null);

const handleError = (error) => {
  summaryLoading.value = false;
  if (!loadError.value) loadError.value = error;
};

const loadDashboard = async () => {
  const response = await dashboardService.summary();
  if (!response.data?.status) throw new Error(response.data?.message || "Gagal memuat ringkasan dashboard");
  summary.value = { ...summary.value, ...(response.data.data || {}) };
  summaryLoading.value = false;
};

const loadExpired = async () => {
  try {
    expiredItems.value = await fetchDashboardExpiredProducts();
  } catch (error) {
    handleError(error);
  }
};

const { refresh } = useAutoRefresh(loadDashboard, { onError: handleError });

onMounted(loadExpired);
</script>

<style scoped>
.dashboard { width: 100%; padding: 20px; }

.summary {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px;
}
@media (max-width: 992px) { .summary { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 576px) { .summary { grid-template-columns: 1fr; } }

</style>
