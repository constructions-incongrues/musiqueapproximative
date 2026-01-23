<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import AudioPlayer from './components/AudioPlayer.vue'
import { api } from './services/api'
import { getGlobalPreloader } from './composables/usePreloader'
import type { Post } from './types/post'

// Track list from API
const posts = ref<Post[]>([])
const currentIndex = ref(0)
const isLoading = ref(true)

// Current track info derived from posts
const currentTrack = computed(() => {
  const post = posts.value[currentIndex.value]
  if (!post) {
    return {
      url: '',
      title: 'Chargement...',
      artist: '',
      contributor: ''
    }
  }
  return {
    url: post.trackUrl,
    title: post.trackTitle,
    artist: post.trackAuthor,
    contributor: post.contributor
  }
})

const autoplay = ref(false)
const isRandom = ref(false)

// Initialize the global preloader
const preloader = getGlobalPreloader()

// Preload adjacent tracks based on current index
const preloadAdjacentTracks = () => {
  const urlsToPreload: string[] = []
  
  // Preload next 2 tracks
  for (let i = 1; i <= 2; i++) {
    const nextIndex = currentIndex.value + i
    const nextPost = posts.value[nextIndex]
    if (nextPost) {
      urlsToPreload.push(nextPost.trackUrl)
    }
  }
  
  // Preload previous track
  const prevIndex = currentIndex.value - 1
  const prevPost = posts.value[prevIndex]
  if (prevPost) {
    urlsToPreload.push(prevPost.trackUrl)
  }
  
  if (urlsToPreload.length > 0) {
    console.log('🎵 Preloading tracks:', urlsToPreload)
    preloader.preloadMultiple(urlsToPreload)
  }
}

// Load posts and initialize player
onMounted(async () => {
  try {
    isLoading.value = true
    console.log('📡 Fetching posts from API...')
    
    // Fetch all posts from API
    const data = await api.getPosts()
    posts.value = data
    
    console.log(`✅ Loaded ${posts.value.length} posts`)
    
    if (posts.value.length > 0) {
      // Most recent post is first (index 0)
      currentIndex.value = 0
      
      // Preload adjacent tracks
      preloadAdjacentTracks()
    }
  } catch (error) {
    console.error('❌ Failed to load posts:', error)
  } finally {
    isLoading.value = false
  }
})

// Navigation handlers
const handlePrev = () => {
  if (isRandom.value) {
    // Random mode: pick a random track
    const randomIndex = Math.floor(Math.random() * posts.value.length)
    currentIndex.value = randomIndex
  } else {
    // Sequential mode: go to previous (older) post
    if (currentIndex.value < posts.value.length - 1) {
      currentIndex.value++
    }
  }
  autoplay.value = true
  preloadAdjacentTracks()
}

const handleNext = () => {
  if (isRandom.value) {
    // Random mode: pick a random track
    const randomIndex = Math.floor(Math.random() * posts.value.length)
    currentIndex.value = randomIndex
  } else {
    // Sequential mode: go to next (more recent) post
    if (currentIndex.value > 0) {
      currentIndex.value--
    } else {
      // Loop to oldest
      currentIndex.value = posts.value.length - 1
    }
  }
  autoplay.value = true
  preloadAdjacentTracks()
}

const handleEnded = () => {
  // Auto-advance to next track
  handleNext()
}
</script>

<template>
  <div class="app">
    <header class="app-header">
      <img src="./assets/logo.svg" alt="Musique Approximative" class="logo" />
    </header>

    <main class="app-main">
      <AudioPlayer
        :track-url="currentTrack.url"
        :track-title="currentTrack.title"
        :track-artist="currentTrack.artist"
        :contributor="currentTrack.contributor"
        :autoplay="autoplay"
        @prev="handlePrev"
        @next="handleNext"
        @ended="handleEnded"
      />
    </main>

    <footer class="app-footer">
      <p>
        <a href="https://www.musiqueapproximative.net" target="_blank" rel="noopener">
          Musique Approximative
        </a>
        — L'exutoire anarchique d'une bande de mélomanes fêlé⋅e⋅s
      </p>
    </footer>
  </div>
</template>

<style scoped>
.app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.app-header {
  margin-bottom: 32px;
}

.logo {
  width: 120px;
  height: auto;
  filter: drop-shadow(0 4px 20px rgba(102, 126, 234, 0.3));
}

.app-main {
  width: 100%;
  max-width: 520px;
}

.app-footer {
  margin-top: 48px;
  text-align: center;
  font-size: 0.85rem;
  color: rgba(255, 255, 255, 0.4);
}

.app-footer a {
  color: #667eea;
  text-decoration: none;
  transition: color 0.2s ease;
}

.app-footer a:hover {
  color: #764ba2;
}
</style>
