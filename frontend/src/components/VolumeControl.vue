<script setup lang="ts">

const props = defineProps<{
  volume: number
  isMuted: boolean
}>()

const emit = defineEmits<{
  'update:volume': [value: number]
  toggleMute: []
}>()

const handleVolumeChange = (e: MouseEvent) => {
  const target = e.currentTarget as HTMLElement
  const rect = target.getBoundingClientRect()
  const percent = (e.clientX - rect.left) / rect.width
  emit('update:volume', Math.max(0, Math.min(1, percent)))
}

const handleMouseMove = (e: MouseEvent) => {
  if (e.buttons === 1) {
    handleVolumeChange(e)
  }
}
</script>

<template>
  <div class="volume-control">
    <button 
      class="btn-mute"
      @click="emit('toggleMute')"
      :title="isMuted ? 'Unmute' : 'Mute'"
    >
      <svg v-if="isMuted || volume === 0" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
        <line x1="23" y1="9" x2="17" y2="15"></line>
        <line x1="17" y1="9" x2="23" y2="15"></line>
      </svg>
      <svg v-else-if="volume < 0.5" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
        <path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path>
      </svg>
      <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
        <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
      </svg>
    </button>
    <div 
      class="volume-bar"
      @click="handleVolumeChange"
      @mousemove="handleMouseMove"
    >
      <div 
        class="volume-level" 
        :style="{ width: `${isMuted ? 0 : volume * 100}%` }"
      ></div>
    </div>
  </div>
</template>

<style scoped>
.volume-control {
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-mute {
  background: none;
  border: none;
  color: rgba(255,255,255,0.8);
  cursor: pointer;
  padding: 8px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.btn-mute:hover {
  background: rgba(255,255,255,0.1);
  color: #fff;
}

.volume-bar {
  width: 100px;
  height: 6px;
  background: rgba(255,255,255,0.2);
  border-radius: 3px;
  cursor: pointer;
  position: relative;
}

.volume-level {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  border-radius: 3px;
  transition: width 0.05s linear;
}
</style>
