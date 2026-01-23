/**
 * Post type from Musique Approximative API
 */
export interface Post {
  id: number;
  slug: string;
  title: string;
  trackAuthor: string;
  trackTitle: string;
  trackFilename: string;
  trackUrl: string;
  body: string;
  contributor: string;
  createdAt: string;
}

/**
 * API response for navigation endpoints (prev/next/random)
 */
export interface NavigationResponse {
  url: string;
  title: string;
}

/**
 * Player state
 */
export interface PlayerState {
  isPlaying: boolean;
  isPaused: boolean;
  currentTime: number;
  duration: number;
  volume: number;
  isMuted: boolean;
  isRandom: boolean;
  isLoading: boolean;
}
