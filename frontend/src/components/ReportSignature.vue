<template>
  <div class="report-signature">
    <div class="sig-line"></div>
    <div class="sig-content">
      <div class="sig-label">Mengetahui,</div>
      <div class="sig-space"></div>
      <div class="sig-underline"></div>
      <div class="sig-name">Nama</div>
      <div class="sig-position">Jabatan</div>
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.report-signature {
  margin-top: 40px;
  page-break-inside: avoid;
}

.sig-line {
  border-top: 1px solid #ccc;
  margin-bottom: 30px;
}

.sig-content {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  padding-right: 40px;
}

.sig-label {
  font-size: 13px;
  font-weight: 600;
  color: #333;
  margin-bottom: 0;
}

.sig-space {
  height: 70px;
}

.sig-underline {
  width: 180px;
  border-bottom: 1px solid #333;
  margin-bottom: 6px;
}

.sig-name {
  font-size: 12px;
  color: #555;
}

.sig-position {
  font-size: 12px;
  color: #555;
}
</style>

<style scoped media="print">
.report-signature {
  margin-top: 40px;
  page-break-inside: avoid;
  break-inside: avoid;
}

.sig-content {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  padding-right: 40px;
}

.sig-label {
  font-size: 13px;
  font-weight: 600;
  color: #000 !important;
}

.sig-space {
  height: 70px;
}

.sig-underline {
  width: 180px;
  border-bottom: 1px solid #000;
  margin-bottom: 6px;
}

.sig-name {
  font-size: 12px;
  color: #000 !important;
}

.sig-position {
  font-size: 12px;
  color: #000 !important;
}
</style>
