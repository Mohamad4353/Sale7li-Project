import { ErrorBoundary } from './providers/ErrorBoundary.jsx';
import { I18nProvider } from './providers/I18nProvider.jsx';

/**
 * The shell every React root gets: an error boundary so a failure stays local
 * to its mount point, and the i18n provider seeded from Blade-supplied props.
 * `props.strings`, `props.locale` and `props.dir` are consumed here; everything
 * else is forwarded to the page component untouched.
 */
export default function App({ Component, props = {} }) {
  const { strings, locale, dir, errorMessage, ...pageProps } = props;

  return (
    <ErrorBoundary message={errorMessage}>
      <I18nProvider strings={strings} locale={locale} dir={dir}>
        <Component {...pageProps} />
      </I18nProvider>
    </ErrorBoundary>
  );
}
