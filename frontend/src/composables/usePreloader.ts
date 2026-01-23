import { ref, shallowRef } from "vue";

/**
 * Composable for preloading audio tracks in the background.
 * This improves user experience by buffering the next track
 * while the current one is playing.
 */
export function usePreloader() {
  // Cache of preloaded audio elements (URL -> Audio element)
  const preloadCache = shallowRef<Map<string, HTMLAudioElement>>(new Map());

  // Track which URLs are currently being preloaded
  const preloadingUrls = ref<Set<string>>(new Set());

  // Track which URLs have been fully preloaded
  const preloadedUrls = ref<Set<string>>(new Set());

  /**
   * Preload an audio track
   * @param url The URL of the audio file to preload
   * @returns Promise that resolves when preloading is complete or starts buffering
   */
  const preload = async (url: string): Promise<void> => {
    if (!url) return;

    // Skip if already preloaded or currently preloading
    if (preloadedUrls.value.has(url) || preloadingUrls.value.has(url)) {
      return;
    }

    preloadingUrls.value.add(url);

    return new Promise((resolve, reject) => {
      const audio = new Audio();

      // Set preload to auto for aggressive buffering
      audio.preload = "auto";

      // Handle successful preload
      const handleCanPlayThrough = () => {
        preloadedUrls.value.add(url);
        preloadingUrls.value.delete(url);
        preloadCache.value.set(url, audio);
        cleanup();
        resolve();
      };

      // Handle at least enough data to start playing
      const handleCanPlay = () => {
        // Mark as preloaded even if not fully buffered
        preloadedUrls.value.add(url);
        preloadingUrls.value.delete(url);
        preloadCache.value.set(url, audio);
      };

      // Handle errors
      const handleError = () => {
        preloadingUrls.value.delete(url);
        cleanup();
        reject(new Error(`Failed to preload: ${url}`));
      };

      // Cleanup function
      const cleanup = () => {
        audio.removeEventListener("canplaythrough", handleCanPlayThrough);
        audio.removeEventListener("canplay", handleCanPlay);
        audio.removeEventListener("error", handleError);
      };

      audio.addEventListener("canplaythrough", handleCanPlayThrough);
      audio.addEventListener("canplay", handleCanPlay);
      audio.addEventListener("error", handleError);

      // Start loading
      audio.src = url;
      audio.load();

      // Resolve after a timeout even if not fully loaded
      // This prevents blocking if the connection is slow
      setTimeout(() => {
        if (preloadingUrls.value.has(url)) {
          preloadingUrls.value.delete(url);
          cleanup();
          resolve();
        }
      }, 10000); // 10 second timeout
    });
  };

  /**
   * Preload multiple tracks
   * @param urls Array of URLs to preload
   */
  const preloadMultiple = async (urls: string[]): Promise<void> => {
    const promises = urls.map((url) => preload(url).catch(() => {}));
    await Promise.all(promises);
  };

  /**
   * Get a preloaded audio element if available
   * @param url The URL of the audio file
   * @returns The preloaded HTMLAudioElement or null
   */
  const getPreloaded = (url: string): HTMLAudioElement | null => {
    return preloadCache.value.get(url) || null;
  };

  /**
   * Check if a URL is preloaded
   * @param url The URL to check
   * @returns true if the URL is preloaded
   */
  const isPreloaded = (url: string): boolean => {
    return preloadedUrls.value.has(url);
  };

  /**
   * Check if a URL is currently being preloaded
   * @param url The URL to check
   * @returns true if the URL is being preloaded
   */
  const isPreloading = (url: string): boolean => {
    return preloadingUrls.value.has(url);
  };

  /**
   * Clear a specific URL from the cache
   * @param url The URL to remove from cache
   */
  const clearPreload = (url: string): void => {
    const audio = preloadCache.value.get(url);
    if (audio) {
      audio.src = "";
      preloadCache.value.delete(url);
    }
    preloadedUrls.value.delete(url);
    preloadingUrls.value.delete(url);
  };

  /**
   * Clear all preloaded audio from cache
   */
  const clearAll = (): void => {
    preloadCache.value.forEach((audio) => {
      audio.src = "";
    });
    preloadCache.value.clear();
    preloadedUrls.value.clear();
    preloadingUrls.value.clear();
  };

  /**
   * Get the number of preloaded tracks
   */
  const preloadCount = (): number => {
    return preloadedUrls.value.size;
  };

  return {
    preload,
    preloadMultiple,
    getPreloaded,
    isPreloaded,
    isPreloading,
    clearPreload,
    clearAll,
    preloadCount,
    preloadedUrls,
    preloadingUrls,
  };
}

// Singleton instance for global preloader
let globalPreloader: ReturnType<typeof usePreloader> | null = null;

export function getGlobalPreloader(): ReturnType<typeof usePreloader> {
  if (!globalPreloader) {
    globalPreloader = usePreloader();
  }
  return globalPreloader;
}
