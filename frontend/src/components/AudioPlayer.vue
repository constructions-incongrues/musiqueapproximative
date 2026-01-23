<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { usePlayer } from '@/composables/usePlayer'
import { useKeyboard } from '@/composables/useKeyboard'
import ProgressBar from './ProgressBar.vue'
import VolumeControl from './VolumeControl.vue'
import PlayerControls from './PlayerControls.vue'
import TrackInfo from './TrackInfo.vue'

const props = defineProps<{
  trackUrl: string
  trackTitle: string
  trackArtist: string
  contributor?: string
  autoplay?: boolean
}>()

const emit = defineEmits<{
  ended: []
  prev: []
  next: []
}>()

const audioElement = ref<HTMLAudioElement | null>(null)
const isRandom = ref(false)

const player = usePlayer()

// Set up audio element when mounted
onMounted(() => {
  if (audioElement.value) {
    player.setAudioElement(audioElement.value)
    if (props.autoplay) {
      player.play()
    }
  }
})

// Watch for track URL changes
watch(() => props.trackUrl, (newUrl) => {
  if (audioElement.value && newUrl) {
    audioElement.value.src = newUrl
    audioElement.value.load()
    if (props.autoplay) {
      player.play()
    }
  }
})

// Handle track end
const handleEnded = () => {
  emit('ended')
  emit('next')
}

// Keyboard shortcuts
useKeyboard({
  onPlayPause: player.togglePlay,
  onPrev: () => emit('prev'),
  onNext: () => emit('next'),
  onRandom: () => { isRandom.value = !isRandom.value }
})

const toggleRandom = () => {
  isRandom.value = !isRandom.value
}
</script>

<template>
  <div class="audio-player">
    <!-- Hidden audio element -->
    <audio
      ref="audioElement"
      :src="trackUrl"
      preload="metadata"
      @play="player.onPlay"
      @pause="player.onPause"
      @timeupdate="player.onTimeUpdate"
      @durationchange="player.onDurationChange"
      @loadedmetadata="player.onLoadedMetadata"
      @waiting="player.onWaiting"
      @canplay="player.onCanPlay"
      @progress="player.onProgress"
      @ended="handleEnded"
    ></audio>

    <!-- Track info -->
    <TrackInfo
      :title="trackTitle"
      :artist="trackArtist"
      :contributor="contributor"
    />

    <!-- Progress bar -->
    <div class="progress-section">
      <ProgressBar
        :progress="player.progress.value"
        :buffered="player.buffered.value"
        @seek="player.seek"
      />
      <div class="time-display">
        <span class="time-current">{{ player.formattedCurrentTime.value }}</span>
        <span class="time-duration">{{ player.formattedDuration.value }}</span>
      </div>
    </div>

    <!-- Controls -->
    <PlayerControls
      :is-playing="player.isPlaying.value"
      :is-random="isRandom"
      :is-loading="player.isLoading.value"
      @play="player.play"
      @pause="player.pause"
      @prev="emit('prev')"
      @next="emit('next')"
      @toggle-random="toggleRandom"
    />

    <!-- Volume -->
    <div class="volume-section">
      <VolumeControl
        :volume="player.volume.value"
        :is-muted="player.isMuted.value"
        @update:volume="player.setVolume"
        @toggle-mute="player.toggleMute"
      />
    </div>

    <!-- Keyboard shortcuts hint -->
    <div class="shortcuts-hint">
      <span>espace</span> play/pause
      <span>j</span> précédent
      <span>k</span> suivant
      <span>r</span> aléatoire
    </div>
  </div>
</template>

<style scoped>
.audio-player {
  background: linear-gradient(135deg, rgba(20, 20, 30, 0.95) 0%, rgba(30, 20, 40, 0.95) 100%);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  padding: 32px;
  max-width: 480px;
  margin: 0 auto;
  box-shadow: 
    0 25px 50px -12px rgba(0, 0, 0, 0.5),
    0 0 0 1px rgba(255, 255, 255, 0.1);
}

.progress-section {
  margin: 24px 0;
}

.time-display {
  display: flex;
  justify-content: space-between;
  margin-top: 8px;
  font-size: 0.8rem;
  color: rgba(255, 255, 255, 0.5);
  font-variant-numeric: tabular-nums;
}

.volume-section {
  display: flex;
  justify-content: center;
  margin-top: 24px;
}

.shortcuts-hint {
  margin-top: 24px;
  text-align: center;
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.3);
}

.shortcuts-hint span {
  display: inline-block;
  background: rgba(255, 255, 255, 0.1);
  padding: 2px 8px;
  border-radius: 4px;
  margin: 0 4px;
  font-family: 'SF Mono', 'Menlo', monospace;
}
</style>
