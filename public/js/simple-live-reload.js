/**
 * Simple Live Reload for CSS/JS Files - Clean Version
 */

(function () {
  "use strict";

  // Only run on localhost
  const hostname = window.location.hostname;
  if (
    hostname !== "localhost" &&
    hostname !== "127.0.0.1" &&
    !hostname.includes(".local")
  ) {
    return;
  }

  console.log("🔄 Live Reload Active");

  // Track CSS files and their last modified times
  let cssFiles = new Map();
  let jsFiles = new Map();
  let checkInterval = 1000; // Check every 1 second

  // Initialize CSS file tracking
  async function initCSSTracking() {
    const links = document.querySelectorAll('link[rel="stylesheet"]');
    for (const link of links) {
      const href = link.href;
      if (href.includes(window.location.origin)) {
        const time = await checkFileTime(href);
        if (time) {
          cssFiles.set(href, time);
          console.log("📁 Watching CSS:", href.split("/").pop());
        }
      }
    }
  }

  // Initialize JS file tracking
  async function initJSTracking() {
    const scripts = document.querySelectorAll("script[src]");
    for (const script of scripts) {
      const src = script.src;
      if (
        src.includes(window.location.origin) &&
        src.includes("/js/") &&
        !src.includes("simple-live-reload.js")
      ) {
        const time = await checkFileTime(src);
        if (time) {
          jsFiles.set(src, time);
          console.log("📁 Watching JS:", src.split("/").pop());
        }
      }
    }
  }

  // Check file modification time
  async function checkFileTime(url) {
    try {
      const response = await fetch(url + "?_check=" + Date.now(), {
        method: "HEAD",
        cache: "no-cache",
      });

      if (response.ok) {
        const lastModified = response.headers.get("last-modified");
        const etag = response.headers.get("etag");
        // Use both last-modified and etag for better accuracy
        return lastModified + "|" + (etag || "");
      }
    } catch (error) {
      // Silent fail
    }
    return null;
  }

  // Reload CSS files
  function reloadCSS() {
    const links = document.querySelectorAll('link[rel="stylesheet"]');

    links.forEach((link) => {
      const href = link.href;
      const url = new URL(href);
      url.searchParams.set("_reload", Date.now());
      link.href = url.toString();
    });

    console.log("🎨 CSS reloaded");
  }

  // Reload the page for JS changes
  function reloadPage() {
    console.log("⚡ Reloading page...");
    window.location.reload();
  }

  // Check for file changes
  async function checkForChanges() {
    let hasChanges = false;

    // Check CSS files
    for (const [url, lastTime] of cssFiles.entries()) {
      const currentTime = await checkFileTime(url);
      if (currentTime && currentTime !== lastTime && lastTime !== undefined) {
        cssFiles.set(url, currentTime);
        console.log("🎨 CSS file changed, reloading...");
        reloadCSS();
        hasChanges = true;
        break; // Only handle one change at a time
      } else if (currentTime && lastTime === undefined) {
        // First time checking, just store the time
        cssFiles.set(url, currentTime);
      }
    }

    // Only check JS if no CSS changes
    if (!hasChanges) {
      for (const [url, lastTime] of jsFiles.entries()) {
        const currentTime = await checkFileTime(url);
        if (currentTime && currentTime !== lastTime && lastTime !== undefined) {
          jsFiles.set(url, currentTime);
          console.log("⚡ JS file changed, reloading page...");
          reloadPage();
          break;
        } else if (currentTime && lastTime === undefined) {
          // First time checking, just store the time
          jsFiles.set(url, currentTime);
        }
      }
    }
  }

  // Initialize everything
  async function init() {
    console.log("🔄 Initializing Live Reload...");

    // Wait a bit for page to fully load
    setTimeout(async () => {
      await initCSSTracking();
      await initJSTracking();

      console.log(
        `📁 Watching ${cssFiles.size} CSS files and ${jsFiles.size} JS files`
      );

      // Start checking for changes every 2 seconds (less frequent)
      setInterval(checkForChanges, 2000);
    }, 1000);
  }

  // Start when DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }

  // Add manual reload hotkey (Ctrl+Shift+R)
  document.addEventListener("keydown", (e) => {
    if (e.ctrlKey && e.shiftKey && e.key === "R") {
      e.preventDefault();
      reloadCSS();
    }
  });
})();
