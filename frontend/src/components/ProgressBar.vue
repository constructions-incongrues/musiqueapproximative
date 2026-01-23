<script setup lang="ts">

const props = defineProps<{
  progress: number
  buffered: number
}>()

const emit = defineEmits<{
  seek: [percent: number]
}>()

const handleClick = (e: MouseEvent) => {
  const target = e.currentTarget as HTMLElement
  const rect = target.getBoundingClientRect()
  const percent = ((e.clientX - rect.left) / rect.width) * 100
  emit('seek', percent)
}

const handleMouseMove = (e: MouseEvent) => {
  if (e.buttons === 1) {
    handleClick(e)
  }
}
</script>

<template>
  <div 
    class="progress-bar"
    @click="handleClick"
    @mousemove="handleMouseMove"
  >
    <div class="progress-buffered" :style="{ width: `${buffered}%` }"></div>
    <div class="progress-current" :style="{ width: `${progress}%` }"></div>
    <div class="progress-handle" :style="{ left: `${progress}%` }"></div>
  </div>
</template>

<style scoped>
.progress-bar {
  position: relative;
  width: 100%;
  height: 35px;
  background: linear-gradient(180deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.1) 100%);
  border-radius: 8px;
  cursor: pointer;
  overflow: hidden;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.1);
}

.progress-buffered {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  background: rgba(255,255,255,0.1);
  border-radius: 8px;
  transition: width 0.1s ease;
}

.progress-current {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  border-radius: 8px;
  transition: width 0.05s linear;
}

.progress-handle {
  position: absolute;
  top: 50%;
  width: 16px;
  height: 16px;
  background: #fff;
  border-radius: 50%;
  transform: translate(-50%, -50%);
  box-shadow: 0 2px 8px rgba(0,0,0,0.3);
  opacity: 0;
  transition: opacity 0.2s ease;
}

.progress-bar:hover .progress-handle {
  opacity: 1;
}
</style>
