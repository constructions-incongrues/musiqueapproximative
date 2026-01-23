<script setup lang="ts">
defineProps<{
  isPlaying: boolean
  isRandom: boolean
  isLoading: boolean
}>()

const emit = defineEmits<{
  'play': []
  'pause': []
  'prev': []
  'next': []
  'toggleRandom': []
}>()
</script>

<template>
  <div class="player-controls">
    <!-- Previous -->
    <button class="btn-control" @click="emit('prev')" title="Previous (J)">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
        <path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/>
      </svg>
    </button>

    <!-- Play/Pause -->
    <button 
      class="btn-play" 
      @click="isPlaying ? emit('pause') : emit('play')"
      :title="isPlaying ? 'Pause (Space)' : 'Play (Space)'"
      :class="{ loading: isLoading }"
    >
      <svg v-if="isLoading" class="spinner" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10" stroke-opacity="0.25"/>
        <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/>
      </svg>
      <svg v-else-if="isPlaying" width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
        <path d="M6 4h4v16H6zM14 4h4v16h-4z"/>
      </svg>
      <svg v-else width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
        <path d="M8 5v14l11-7z"/>
      </svg>
    </button>

    <!-- Next -->
    <button class="btn-control" @click="emit('next')" title="Next (K)">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
        <path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/>
      </svg>
    </button>

    <!-- Random -->
    <button 
      class="btn-control btn-random" 
      :class="{ active: isRandom }"
      @click="emit('toggleRandom')" 
      title="Random (R)"
    >
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="16 3 21 3 21 8"></polyline>
        <line x1="4" y1="20" x2="21" y2="3"></line>
        <polyline points="21 16 21 21 16 21"></polyline>
        <line x1="15" y1="15" x2="21" y2="21"></line>
        <line x1="4" y1="4" x2="9" y2="9"></line>
      </svg>
    </button>
  </div>
</template>

<style scoped>
.player-controls {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
}

.btn-control {
  background: none;
  border: none;
  color: rgba(255,255,255,0.8);
  cursor: pointer;
  padding: 12px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.btn-control:hover {
  background: rgba(255,255,255,0.1);
  color: #fff;
  transform: scale(1.1);
}

.btn-random.active {
  color: #667eea;
}

.btn-play {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  color: #fff;
  cursor: pointer;
  width: 64px;
  height: 64px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
}

.btn-play:hover {
  transform: scale(1.05);
  box-shadow: 0 6px 25px rgba(102, 126, 234, 0.5);
}

.btn-play:active {
  transform: scale(0.98);
}

.btn-play.loading {
  pointer-events: none;
}

.spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
