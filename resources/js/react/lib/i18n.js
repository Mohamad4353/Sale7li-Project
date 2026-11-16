/**
 * Tiny translation helper for React. It does NOT re-implement Laravel's
 * translator — Blade passes down only the handful of already-translated strings
 * a component needs, as a flat `strings` map. This just does lookup + `:param`
 * interpolation so components never hard-code English.
 *
 *   const t = createTranslator({ hello: 'Hi :name' });
 *   t('hello', { name: 'Sam' })  // "Hi Sam"
 *   t('missing', {}, 'fallback') // "fallback"
 */
export function createTranslator(strings = {}) {
  return function t(key, params = {}, fallback = key) {
    const template = Object.prototype.hasOwnProperty.call(strings, key) ? strings[key] : fallback;
    if (typeof template !== 'string') return String(template);

    return Object.keys(params).reduce(
      (out, name) => out.replaceAll(`:${name}`, String(params[name])),
      template,
    );
  };
}
