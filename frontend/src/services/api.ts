import type { NavigationResponse } from "@/types/post";

const API_BASE = "/api";

/**
 * API service for Musique Approximative
 */
export const api = {
  /**
   * Get a random post
   */
  async getRandomPost(currentId?: number): Promise<NavigationResponse> {
    const params = currentId ? `?current=${currentId}` : "";
    const response = await fetch(`${API_BASE}/posts/random${params}`);
    if (!response.ok) {
      throw new Error("Failed to fetch random post");
    }
    return response.json();
  },

  /**
   * Get the next post
   */
  async getNextPost(currentId: number): Promise<NavigationResponse> {
    const response = await fetch(`${API_BASE}/posts/next?current=${currentId}`);
    if (!response.ok) {
      throw new Error("Failed to fetch next post");
    }
    return response.json();
  },

  /**
   * Get the previous post
   */
  async getPrevPost(currentId: number): Promise<NavigationResponse> {
    const response = await fetch(`${API_BASE}/posts/prev?current=${currentId}`);
    if (!response.ok) {
      throw new Error("Failed to fetch previous post");
    }
    return response.json();
  },

  /**
   * Get post details by slug (with JSON format)
   */
  async getPost(slug: string): Promise<any> {
    const response = await fetch(`${API_BASE}/post/${slug}?format=json`);
    if (!response.ok) {
      throw new Error("Failed to fetch post");
    }
    return response.json();
  },

  /**
   * Get list of posts
   */
  async getPosts(contributor?: string, query?: string): Promise<any[]> {
    const params = new URLSearchParams();
    params.append("format", "json");
    if (contributor) params.append("c", contributor);
    if (query) params.append("q", query);

    const response = await fetch(`${API_BASE}/posts?${params.toString()}`);
    if (!response.ok) {
      throw new Error("Failed to fetch posts");
    }
    return response.json();
  },

  /**
   * Get adjacent posts for preloading (next and previous)
   * Returns URLs that can be preloaded in the background
   */
  async getAdjacentPosts(currentId: number): Promise<{
    next: NavigationResponse | null;
    prev: NavigationResponse | null;
  }> {
    const [nextResult, prevResult] = await Promise.allSettled([
      this.getNextPost(currentId),
      this.getPrevPost(currentId),
    ]);

    return {
      next: nextResult.status === "fulfilled" ? nextResult.value : null,
      prev: prevResult.status === "fulfilled" ? prevResult.value : null,
    };
  },
};
