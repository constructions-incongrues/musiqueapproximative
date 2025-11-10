document.addEventListener("DOMContentLoaded", (event) => {
    console.log("[desastres/splitouine] Loaded");
    gsap.registerPlugin(SplitText)

    let split = SplitText.create(
        window.DesastreOptions.splitouine.split.selector,
        window.DesastreOptions.splitouine.split.configuration
    );

    let elId = document.getElementById(window.DesastreOptions.splitouine.tween.audio.id);
    elId.onloadedmetadata = function(event) {
        if (window.DesastreOptions.splitouine.tween.audio.matchDuration) {
            window.DesastreOptions.splitouine.tween.configuration.duration = event.target.duration;
        }
        let tween = gsap.from(
            split[window.DesastreOptions.splitouine.tween.type],
            window.DesastreOptions.splitouine.tween.configuration
        );
    };

});