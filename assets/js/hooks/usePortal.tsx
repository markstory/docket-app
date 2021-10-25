import {useEffect, useRef} from 'react';

/**
 * Minimalist portal implementation.
 *
 * This doesn't aim to handle multi-document situations, as we don't have those.
 */
function usePortal(id?: string) {
  const portal = useRef(document.createElement('div'));
  if (id) {
    portal.current.setAttribute('id', id);
  }

  useEffect(() => {
    let app = document.getElementById('app');
    if (!app) {
      // For test suites.
      app = document.body;
    }
    app.appendChild(portal.current);

    return function cleanup() {
      if (portal.current) {
        app.removeChild(portal.current);
      }
    };
  }, []);

  return portal.current;
}

export default usePortal;
