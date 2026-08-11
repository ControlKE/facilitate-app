const LEAFLET_CSS_URL = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
const LEAFLET_JS_URL = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';

let leafletAssetsPromise = null;

export function loadLeafletAssets() {
  if (typeof window === 'undefined') {
    return Promise.reject(new Error('Map picker is only available in the browser.'));
  }

  if (window.L) {
    return Promise.resolve(window.L);
  }

  if (!leafletAssetsPromise) {
    leafletAssetsPromise = new Promise((resolve, reject) => {
      let loadTimeoutId = null;
      let existingScriptLoadHandler = null;
      let existingScriptErrorHandler = null;

      const cleanup = (scriptElement = null) => {
        if (loadTimeoutId !== null) {
          window.clearTimeout(loadTimeoutId);
          loadTimeoutId = null;
        }

        if (scriptElement && existingScriptLoadHandler && existingScriptErrorHandler) {
          scriptElement.removeEventListener('load', existingScriptLoadHandler);
          scriptElement.removeEventListener('error', existingScriptErrorHandler);
        }
      };

      const finishResolve = (scriptElement = null) => {
        cleanup(scriptElement);
        resolve(window.L);
      };

      const finishReject = (error, scriptElement = null) => {
        cleanup(scriptElement);
        reject(error);
      };

      loadTimeoutId = window.setTimeout(() => {
        finishReject(new Error('Map picker timed out while loading.'));
      }, 10000);

      const existingLink = document.querySelector('link[data-leaflet-css="true"]');
      if (!existingLink) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = LEAFLET_CSS_URL;
        link.setAttribute('data-leaflet-css', 'true');
        document.head.appendChild(link);
      }

      const existingScript = document.querySelector('script[data-leaflet-js="true"]');
      if (existingScript) {
        existingScriptLoadHandler = () => finishResolve(existingScript);
        existingScriptErrorHandler = () => finishReject(new Error('Failed to load the map picker library.'), existingScript);
        existingScript.addEventListener('load', existingScriptLoadHandler, { once: true });
        existingScript.addEventListener('error', existingScriptErrorHandler, { once: true });
        return;
      }

      const script = document.createElement('script');
      script.src = LEAFLET_JS_URL;
      script.async = true;
      script.defer = true;
      script.setAttribute('data-leaflet-js', 'true');
      script.addEventListener('load', () => finishResolve(script), { once: true });
      script.addEventListener('error', () => finishReject(new Error('Failed to load the map picker library.'), script), { once: true });
      document.head.appendChild(script);
    });
  }

  return leafletAssetsPromise;
}

export function createLeafletTileLayer(L, mapType = 'roadmap') {
  if (String(mapType || '').toLowerCase() === 'satellite') {
    return L.tileLayer(
      'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
      {
        attribution: 'Tiles (c) Esri',
        maxZoom: 20,
      }
    );
  }

  return L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '(c) OpenStreetMap contributors',
    maxZoom: 20,
  });
}
