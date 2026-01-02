/**
 * Shared Audio Helper for Desastres
 *
 * Exposes audio information globally via window.DesastreAudio
 * Works with jPlayer and native HTML5 audio elements
 */

(function () {
  "use strict";

  console.log("[desastres/shared/audio] Initializing audio helper");

  // Global object to store audio information
  window.DesastreAudio = {
    duration: null,
    element: null,
    isReady: false,
    readyCallbacks: [],
  };

  /**
   * Register a callback to be called when audio metadata is loaded
   * @param {Function} callback - Function to call when audio is ready
   */
  window.DesastreAudio.onReady = function (callback) {
    if (this.isReady) {
      // Already ready, call immediately
      callback(this);
    } else {
      // Not ready yet, queue callback
      this.readyCallbacks.push(callback);
    }
  };

  /**
   * Trigger all ready callbacks
   */
  function triggerReadyCallbacks() {
    console.log(
      "[desastres/shared/audio] Audio ready, duration:",
      window.DesastreAudio.duration + "s",
    );
    window.DesastreAudio.isReady = true;

    window.DesastreAudio.readyCallbacks.forEach(function (callback) {
      try {
        callback(window.DesastreAudio);
      } catch (error) {
        console.error(
          "[desastres/shared/audio] Error in ready callback:",
          error,
        );
      }
    });

    // Clear callbacks after execution
    window.DesastreAudio.readyCallbacks = [];
  }

  /**
   * Initialize audio tracking
   */
  function initAudio() {
    // Try to find jPlayer audio element first
    var jPlayerAudio = document.querySelector(".jp-jplayer audio");

    if (jPlayerAudio) {
      console.log("[desastres/shared/audio] Found jPlayer audio element");
      window.DesastreAudio.element = jPlayerAudio;

      // Check if metadata is already loaded
      if (
        jPlayerAudio.duration &&
        !isNaN(jPlayerAudio.duration) &&
        jPlayerAudio.duration !== Infinity
      ) {
        window.DesastreAudio.duration = jPlayerAudio.duration;
        triggerReadyCallbacks();
      } else {
        // Wait for metadata to load
        jPlayerAudio.addEventListener("loadedmetadata", function () {
          window.DesastreAudio.duration = jPlayerAudio.duration;
          triggerReadyCallbacks();
        });

        jPlayerAudio.addEventListener("durationchange", function () {
          if (
            jPlayerAudio.duration &&
            !isNaN(jPlayerAudio.duration) &&
            jPlayerAudio.duration !== Infinity
          ) {
            window.DesastreAudio.duration = jPlayerAudio.duration;
            if (!window.DesastreAudio.isReady) {
              triggerReadyCallbacks();
            }
          }
        });
      }

      return;
    }

    // Fallback: try to find any audio element
    var audioElement = document.querySelector("audio");

    if (audioElement) {
      console.log("[desastres/shared/audio] Found generic audio element");
      window.DesastreAudio.element = audioElement;

      // Check if metadata is already loaded
      if (
        audioElement.duration &&
        !isNaN(audioElement.duration) &&
        audioElement.duration !== Infinity
      ) {
        window.DesastreAudio.duration = audioElement.duration;
        triggerReadyCallbacks();
      } else {
        // Wait for metadata to load
        audioElement.addEventListener("loadedmetadata", function () {
          window.DesastreAudio.duration = audioElement.duration;
          triggerReadyCallbacks();
        });

        audioElement.addEventListener("durationchange", function () {
          if (
            audioElement.duration &&
            !isNaN(audioElement.duration) &&
            audioElement.duration !== Infinity
          ) {
            window.DesastreAudio.duration = audioElement.duration;
            if (!window.DesastreAudio.isReady) {
              triggerReadyCallbacks();
            }
          }
        });
      }

      return;
    }

    console.warn("[desastres/shared/audio] No audio element found");
  }

  // Initialize when DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAudio);
  } else {
    // DOM already loaded
    initAudio();
  }

  // Also try to init after a short delay (in case jPlayer creates the audio element dynamically)
  setTimeout(function () {
    if (!window.DesastreAudio.element) {
      console.log(
        "[desastres/shared/audio] Retrying audio element detection...",
      );
      initAudio();
    }
  }, 500);
})();
