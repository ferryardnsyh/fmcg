<template>
  <div class="card-dashboard">
    <div class="icon">
      <i :class="icon" :style="{ color }"></i>
    </div>

    <div class="content">
      <h2 :style="{ color: color }">
        {{ value }}
      </h2>

      <p>{{ title }}</p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: String,

  value: [String, Number],

  icon: String,

  color: String,
});
</script>

<style scoped>
.card-dashboard {
  background: white;

  border: 1px solid #e5e5e5;

  border-radius: 3px;

  height: 95px;

  padding: 20px;

  display: flex;

  align-items: center;

  gap: 18px;

  transition: 0.25s;
}

.card-dashboard:hover {
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);

  transform: translateY(-3px);
}

.icon {
  width: 55px;

  display: flex;

  justify-content: center;

  align-items: center;
}

.icon i {
  font-size: 40px;
}

.content {
  flex: 1;

  text-align: center;
}

.content h2 {
  margin: 0;

  font-size: 38px;

  font-weight: 700;

  line-height: 1;
}

.content p {
  margin-top: 8px;

  margin-bottom: 0;

  color: #7a7a7a;

  font-size: 15px;
}
</style>
