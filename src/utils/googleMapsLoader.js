let googleMapsPromise = null;

export function loadGoogleMapsApi({ apiKey, language = 'en-GB', region = 'GB', version = 'weekly' } = {}) {
  const key = String(apiKey || '').trim();
  if (!key) {
    return Promise.reject(new Error('Google Maps browser key is missing.'));
  }

  if (typeof window === 'undefined') {
    return Promise.reject(new Error('Google Maps is only available in the browser.'));
  }

  if (window.google?.maps?.Map) {
    return Promise.resolve(window.google.maps);
  }

  if (!googleMapsPromise) {
    googleMapsPromise = new Promise((resolve, reject) => {
      const existingScript = document.querySelector('script[data-google-maps-loader="true"]');
      let timeoutId = null;

      const cleanup = () => {
        if (timeoutId !== null) {
          window.clearTimeout(timeoutId);
          timeoutId = null;
        }
      };

      const finishResolve = () => {
        cleanup();
        if (window.google?.maps) {
          resolve(window.google.maps);
          return;
        }
        reject(new Error('Google Maps loaded without the expected API objects.'));
      };

      const finishReject = (error) => {
        cleanup();
        reject(error);
      };

      timeoutId = window.setTimeout(() => {
        finishReject(new Error('Google Maps timed out while loading.'));
      }, 15000);

      if (existingScript) {
        if (window.google?.maps) {
          finishResolve();
          return;
        }

        existingScript.addEventListener('load', finishResolve, { once: true });
        existingScript.addEventListener('error', () => finishReject(new Error('Failed to load Google Maps.')), { once: true });
        return;
      }

      const script = document.createElement('script');
      const params = new URLSearchParams({
        key,
        v: version,
        loading: 'async',
        language,
        region,
        libraries: 'marker',
      });
      script.src = `https://maps.googleapis.com/maps/api/js?${params.toString()}`;
      script.async = true;
      script.defer = true;
      script.setAttribute('data-google-maps-loader', 'true');
      script.addEventListener('load', finishResolve, { once: true });
      script.addEventListener('error', () => finishReject(new Error('Failed to load Google Maps.')), { once: true });
      document.head.appendChild(script);
    });
  }

  return googleMapsPromise;
}
