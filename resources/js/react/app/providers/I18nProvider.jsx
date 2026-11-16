import { createContext, useContext, useMemo } from 'react';
import { createTranslator } from '../../lib/i18n.js';

const I18nContext = createContext({
  t: (key) => key,
  locale: 'en',
  dir: 'ltr',
});

/**
 * Makes the translated `strings` map, the active locale and the writing
 * direction available to any component via useTranslator(). Direction defaults
 * to the live <html dir> so React always agrees with the Blade layout.
 */
export function I18nProvider({ strings = {}, locale, dir, children }) {
  const value = useMemo(() => {
    const resolvedDir =
      dir || (typeof document !== 'undefined' ? document.documentElement.dir : 'ltr') || 'ltr';
    const resolvedLocale =
      locale || (typeof document !== 'undefined' ? document.documentElement.lang : 'en') || 'en';

    return { t: createTranslator(strings), locale: resolvedLocale, dir: resolvedDir };
  }, [strings, locale, dir]);

  return <I18nContext.Provider value={value}>{children}</I18nContext.Provider>;
}

export function useTranslator() {
  return useContext(I18nContext);
}
