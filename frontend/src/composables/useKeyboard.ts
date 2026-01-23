import { onMounted, onUnmounted } from "vue";

interface KeyboardOptions {
  onPlayPause: () => void;
  onPrev: () => void;
  onNext: () => void;
  onRandom: () => void;
  onSearch?: () => void;
}

/**
 * Composable for keyboard shortcuts
 */
export function useKeyboard(options: KeyboardOptions) {
  const handleKeyDown = (e: KeyboardEvent) => {
    // Ignore if typing in input field
    if (
      e.target instanceof HTMLInputElement ||
      e.target instanceof HTMLTextAreaElement
    ) {
      return;
    }

    switch (e.code) {
      case "Space":
        e.preventDefault();
        options.onPlayPause();
        break;
      case "KeyJ":
        e.preventDefault();
        options.onPrev();
        break;
      case "KeyK":
        e.preventDefault();
        options.onNext();
        break;
      case "KeyR":
        e.preventDefault();
        options.onRandom();
        break;
      case "KeyS":
        if (options.onSearch) {
          e.preventDefault();
          options.onSearch();
        }
        break;
    }
  };

  onMounted(() => {
    document.addEventListener("keydown", handleKeyDown);
  });

  onUnmounted(() => {
    document.removeEventListener("keydown", handleKeyDown);
  });

  return {};
}
