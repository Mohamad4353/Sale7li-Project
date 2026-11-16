/**
 * React entry point for SALE7LI.
 *
 * This file is a SEPARATE Vite entry (see vite.config.js). It is loaded only on
 * Blade pages that render <x-react>, so the React runtime never ships on pages
 * that do not use it. Alpine (resources/js/app.js) is unaffected.
 *
 * Contract: Blade emits
 *     <div data-react-root="ComponentName" data-props="{...json...}"></div>
 * and this scanner mounts the matching component into it. Pages with no such
 * element simply render nothing here.
 */
import { mountReactRoots } from './lib/mount.jsx';
import MarketplaceSearch from './pages/MarketplaceSearch.jsx';
import AttachmentInput from './components/AttachmentInput.jsx';

// The registry is explicit — no dynamic component names from the DOM.
const registry = {
  MarketplaceSearch,
  AttachmentInput,
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => mountReactRoots(registry));
} else {
  mountReactRoots(registry);
}
