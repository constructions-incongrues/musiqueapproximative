console.log('[desastres/redirect] loaded');

// Get url parameters
let allParams = new URLSearchParams(window.location.search);
let params = Object.fromEntries(allParams.entries())

// check if param "noredirect" is present
if (params.hasOwnProperty('noredirect')) {
    console.log('[desastres/redirect] noredirect parameter detected, aborting redirect.');
} else {
    // Redir ect to option.url after option.delay seconds
    document.addEventListener("DOMContentLoaded", function() {
        const url = window.DesastreOptions.redirect.url;
        const delay = parseInt(window.DesastreOptions.redirect.seconds);
        setTimeout(function() {
            window.location.href = url;
        }, delay * 1000);
    });
}