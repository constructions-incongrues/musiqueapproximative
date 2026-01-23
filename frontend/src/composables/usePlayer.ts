import { ref, computed } from "vue";

/**
 * Composable for audio player functionality
 */
export function usePlayer() {
  const audioRef = ref<HTMLAudioElement | null>(null);
  const isPlaying = ref(false);
  const isPaused = ref(true);
  const currentTime = ref(0);
  const duration = ref(0);
  const volume = ref(0.8);
  const isMuted = ref(false);
  const isLoading = ref(false);
  const buffered = ref(0);

  // Format time as mm:ss
  const formatTime = (seconds: number): string => {
    if (isNaN(seconds) || !isFinite(seconds)) return "0:00";
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, "0")}`;
  };

  const formattedCurrentTime = computed(() => formatTime(currentTime.value));
  const formattedDuration = computed(() => formatTime(duration.value));
  const progress = computed(() =>
    duration.value > 0 ? (currentTime.value / duration.value) * 100 : 0,
  );

  // Set up audio element
  const setAudioElement = (el: HTMLAudioElement) => {
    audioRef.value = el;
    el.volume = volume.value;
  };

  // Play
  const play = () => {
    if (audioRef.value) {
      audioRef.value.play();
    }
  };

  // Pause
  const pause = () => {
    if (audioRef.value) {
      audioRef.value.pause();
    }
  };

  // Toggle play/pause
  const togglePlay = () => {
    if (isPlaying.value) {
      pause();
    } else {
      play();
    }
  };

  // Seek to position (0-100)
  const seek = (percent: number) => {
    if (audioRef.value && duration.value > 0) {
      audioRef.value.currentTime = (percent / 100) * duration.value;
    }
  };

  // Set volume (0-1)
  const setVolume = (val: number) => {
    volume.value = Math.max(0, Math.min(1, val));
    if (audioRef.value) {
      audioRef.value.volume = volume.value;
    }
    if (volume.value > 0) {
      isMuted.value = false;
    }
  };

  // Toggle mute
  const toggleMute = () => {
    isMuted.value = !isMuted.value;
    if (audioRef.value) {
      audioRef.value.muted = isMuted.value;
    }
  };

  // Event handlers for audio element
  const onPlay = () => {
    isPlaying.value = true;
    isPaused.value = false;
  };

  const onPause = () => {
    isPlaying.value = false;
    isPaused.value = true;
  };

  const onTimeUpdate = () => {
    if (audioRef.value) {
      currentTime.value = audioRef.value.currentTime;
    }
  };

  const onDurationChange = () => {
    if (audioRef.value) {
      duration.value = audioRef.value.duration;
    }
  };

  const onLoadedMetadata = () => {
    if (audioRef.value) {
      duration.value = audioRef.value.duration;
    }
    isLoading.value = false;
  };

  const onWaiting = () => {
    isLoading.value = true;
  };

  const onCanPlay = () => {
    isLoading.value = false;
  };

  const onProgress = () => {
    if (audioRef.value && audioRef.value.buffered.length > 0) {
      buffered.value =
        (audioRef.value.buffered.end(audioRef.value.buffered.length - 1) /
          duration.value) *
        100;
    }
  };

  return {
    audioRef,
    isPlaying,
    isPaused,
    currentTime,
    duration,
    volume,
    isMuted,
    isLoading,
    buffered,
    formattedCurrentTime,
    formattedDuration,
    progress,
    setAudioElement,
    play,
    pause,
    togglePlay,
    seek,
    setVolume,
    toggleMute,
    // Event handlers to bind
    onPlay,
    onPause,
    onTimeUpdate,
    onDurationChange,
    onLoadedMetadata,
    onWaiting,
    onCanPlay,
    onProgress,
  };
}
