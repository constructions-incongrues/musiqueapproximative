<script setup lang="ts">
defineProps<{
  artist: string
  title: string
  contributor?: string
}>()
</script>

<template>
  <div class="track-info">
    <h1 class="track-title">{{ title }}</h1>
    <h2 class="track-artist">{{ artist }}</h2>
    <p v-if="contributor" class="track-contributor">
      Posté par <span>{{ contributor }}</span>
    </p>
  </div>
</template>

<style scoped>
.track-info {
  text-align: center;
  padding: 20px;
}

.track-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #fff;
  margin: 0 0 8px 0;
  line-height: 1.3;
  text-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

.track-artist {
  font-size: 1.1rem;
  font-weight: 400;
  color: rgba(255,255,255,0.7);
  margin: 0 0 12px 0;
}

.track-contributor {
  font-size: 0.85rem;
  color: rgba(255,255,255,0.5);
  margin: 0;
}

.track-contributor span {
  color: #667eea;
  font-weight: 500;
}
</style>
