/**
 * Example: How to use DesastreAudio in your disaster script
 *
 * This file shows different ways to access and use audio information
 */

console.log('[desastres/example] Loaded');

document.addEventListener("DOMContentLoaded", () => {
    console.log('[desastres/example] DOM ready');

    // ============================================
    // METHOD 1: Using onReady callback (RECOMMENDED)
    // ============================================

    window.DesastreAudio.onReady(function(audio) {
        console.log('[desastres/example] Audio is ready!');
        console.log('[desastres/example] Duration:', audio.duration, 'seconds');
        console.log('[desastres/example] Element:', audio.element);

        // Example: Fade out page over the entire track duration
        if (typeof gsap !== 'undefined') {
            gsap.to('body', {
                opacity: 0,
                duration: audio.duration,
                ease: 'linear',
                onComplete: function() {
                    console.log('[desastres/example] Animation complete');
                }
            });
        }

        // Example: Listen to audio events
        audio.element.addEventListener('play', function() {
            console.log('[desastres/example] Track playing');
        });

        audio.element.addEventListener('pause', function() {
            console.log('[desastres/example] Track paused');
        });

        audio.element.addEventListener('ended', function() {
            console.log('[desastres/example] Track ended');
        });

        // Example: Log current time every second
        setInterval(function() {
            if (audio.element && !audio.element.paused) {
                console.log('[desastres/example] Current time:', audio.element.currentTime.toFixed(2) + 's');
            }
        }, 1000);
    });

    // ============================================
    // METHOD 2: Multiple callbacks
    // ============================================

    // You can register multiple callbacks
    window.DesastreAudio.onReady(function(audio) {
        console.log('[desastres/example] Second callback executed');
        console.log('[desastres/example] Track duration is', Math.floor(audio.duration / 60) + 'm' + Math.floor(audio.duration % 60) + 's');
    });

    // ============================================
    // METHOD 3: Direct access (when you know audio is ready)
    // ============================================

    // Note: This only works if audio is already loaded
    // Better to use onReady() callback approach above
    setTimeout(function() {
        if (window.DesastreAudio && window.DesastreAudio.isReady) {
            console.log('[desastres/example] Direct access works:', window.DesastreAudio.duration + 's');
        } else {
            console.log('[desastres/example] Audio not ready yet, use onReady() instead');
        }
    }, 2000);

    // ============================================
    // EXAMPLE 4: Use duration in configuration
    // ============================================

    window.DesastreAudio.onReady(function(audio) {
        // Create an animation that loops for the duration of the track
        var loopDuration = 2; // 2 seconds per loop
        var numberOfLoops = Math.floor(audio.duration / loopDuration);

        console.log('[desastres/example] Will run', numberOfLoops, 'loops');

        if (typeof gsap !== 'undefined') {
            gsap.to('.some-element', {
                rotation: 360,
                duration: loopDuration,
                repeat: numberOfLoops - 1,
                ease: 'linear'
            });
        }
    });

    // ============================================
    // EXAMPLE 5: Synchronize with audio playback
    // ============================================

    window.DesastreAudio.onReady(function(audio) {
        var progressBar = document.createElement('div');
        progressBar.style.position = 'fixed';
        progressBar.style.top = '0';
        progressBar.style.left = '0';
        progressBar.style.height = '5px';
        progressBar.style.width = '0%';
        progressBar.style.background = 'red';
        progressBar.style.zIndex = '9999';
        document.body.appendChild(progressBar);

        // Update progress bar with current time
        audio.element.addEventListener('timeupdate', function() {
            var percent = (audio.element.currentTime / audio.duration) * 100;
            progressBar.style.width = percent + '%';
        });
    });
});
