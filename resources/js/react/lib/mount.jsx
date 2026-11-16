import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from '../app/App.jsx';

/**
 * Safely read the JSON payload Blade put on the mount element. Blade escapes it
 * with @json (hex-encoded quotes), so it is always valid inside the attribute.
 */
function readProps(el) {
  const raw = el.getAttribute('data-props');
  if (!raw) return {};
  try {
    return JSON.parse(raw);
  } catch (err) {
    // eslint-disable-next-line no-console
    console.error('[react] invalid data-props on', el, err);
    return {};
  }
}

/**
 * Find every <div data-react-root="Name"> and mount the registered component
 * into it, wrapped in the shared <App> shell (error boundary + i18n).
 *
 * @param {Record<string, React.ComponentType>} registry
 */
export function mountReactRoots(registry) {
  const nodes = document.querySelectorAll('[data-react-root]');

  nodes.forEach((el) => {
    if (el.dataset.reactMounted === '1') return;

    const name = el.getAttribute('data-react-root');
    const Component = registry[name];

    if (!Component) {
      // eslint-disable-next-line no-console
      console.warn(`[react] no component registered for "${name}"`);
      return;
    }

    el.dataset.reactMounted = '1';
    createRoot(el).render(
      <StrictMode>
        <App Component={Component} props={readProps(el)} />
      </StrictMode>,
    );
  });
}
