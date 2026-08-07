console.log("[desastres/redirect] Loaded");

// Get url parameters
let allParams = new URLSearchParams(window.location.search);
let params = Object.fromEntries(allParams.entries());

// check if param "noredirect" is present
if (params.hasOwnProperty("noredirect")) {
  console.log(
    "[desastres/redirect] noredirect parameter detected, aborting redirect",
  );
} else {
  // Redirect to option.url after option.delay seconds
  document.addEventListener("DOMContentLoaded", function () {
    // Vérifier que les options sont présentes
    if (!window.DesastreOptions || !window.DesastreOptions.redirect) {
      console.error(
        "[desastres/redirect] ERROR: Options not found in window.DesastreOptions.redirect",
      );
      console.log(
        "[desastres/redirect] Available options:",
        window.DesastreOptions,
      );
      return;
    }

    const url = window.DesastreOptions.redirect.url;
    const delay = parseInt(window.DesastreOptions.redirect.seconds);
    console.log(
      "[desastres/redirect] Redirecting to",
      url,
      "in",
      delay,
      "seconds",
    );
    setTimeout(function () {
      console.log("[desastres/redirect] Redirecting now to:", url);
      window.location.href = url;
    }, delay * 1000);
  });
}
