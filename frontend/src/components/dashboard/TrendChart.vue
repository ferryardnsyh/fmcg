<template>
  <div class="card border-0 shadow-sm h-100">
    <div class="card-header bg-white border-bottom fw-semibold d-flex align-items-center gap-2 py-3">
      <i class="bi bi-bar-chart-fill text-success"></i> Produk yang Sering Keluar
    </div>
    <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 340px">
      <PageErrorAlert :error="error" fallback="Gagal memuat data grafik." @retry="refresh" />
      <div v-if="loading && !hasData" class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
      <p v-else-if="!hasData" class="text-muted mb-0"><i class="bi bi-inbox me-1"></i> Belum ada data barang keluar</p>
      <Bar v-else :data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from "vue";
import { Bar } from "vue-chartjs";
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Tooltip, Legend } from "chart.js";
import dashboardService from "../../services/dashboard";
import PageErrorAlert from "../PageErrorAlert.vue";
import { useAutoRefresh } from "../../composables/useAutoRefresh";

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend);

const loading = ref(true);
const error = ref(null);
const chartData = ref({ labels: [], datasets: [] });
const chartDetails = ref([]);
const hasData = computed(() => chartData.value.labels.length > 0);

const chartOptions = {
  responsive: true,
  indexAxis: "y",
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: (ctx) => {
          const d = chartDetails.value[ctx.dataIndex];
          const total = ctx.parsed.x;
          return ` ${d ? d.product_name : ""}: ${total} pcs`;
        },
      },
    },
  },
  scales: {
    x: { beginAtZero: true, grid: { display: false } },
    y: { grid: { display: false } },
  },
};

const loadChart = async () => {
  const res = await dashboardService.topOutbound();
  if (!res.data?.status) throw new Error(res.data?.message || "Gagal memuat data grafik");
  const d = res.data.data || {};
  chartData.value = {
    labels: d.labels || [],
    datasets: [
      {
        label: "Jumlah Keluar",
        data: d.values || [],
        backgroundColor: "#ff9800",
        borderRadius: 4,
      },
    ],
  };
  chartDetails.value = d.details || [];
  error.value = null;
  loading.value = false;
};

const { refresh } = useAutoRefresh(loadChart, {
  onError: (err) => {
    error.value = err;
    loading.value = false;
  },
});
</script>