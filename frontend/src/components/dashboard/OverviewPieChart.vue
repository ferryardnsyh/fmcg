<template>
  <div class="card border-0 shadow-sm h-100">
    <div class="card-header bg-white border-bottom fw-semibold d-flex align-items-center gap-2 py-3">
      <i class="bi bi-pie-chart-fill text-primary"></i> Ringkasan Keseluruhan
    </div>
    <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 340px">
      <div v-if="loading" class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
      <Pie v-else :data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { Pie } from "vue-chartjs";
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from "chart.js";

ChartJS.register(ArcElement, Tooltip, Legend);

const props = defineProps({
  summary: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
});

const chartData = computed(() => ({
  labels: ["Total Produk", "Total Stok", "Stok Expired"],
  datasets: [{
    label: "Jumlah",
    data: [
      Number(props.summary.total_produk) || 0,
      Number(props.summary.total_stok) || 0,
      Number(props.summary.mendekati_expired) || 0,
    ],
    backgroundColor: ["#3498db", "#2ecc71", "#e74c3c"],
    borderColor: "#ffffff",
    borderWidth: 2,
  }],
}));

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: "bottom" },
    tooltip: {
      callbacks: {
        label: (ctx) => ` ${ctx.label}: ${ctx.parsed}`,
      },
    },
  },
};
</script>
