import { Component } from 'react';

/**
 * Keeps a React failure contained to its own mount point — the rest of the
 * Blade page (nav, footer, Alpine widgets) keeps working. The fallback uses the
 * existing alert styling so it does not look foreign.
 */
export class ErrorBoundary extends Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false };
  }

  static getDerivedStateFromError() {
    return { hasError: true };
  }

  componentDidCatch(error, info) {
    // eslint-disable-next-line no-console
    console.error('[react] render error', error, info);
  }

  render() {
    if (this.state.hasError) {
      return (
        <div role="alert" className="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700">
          {this.props.message || 'Something went wrong loading this section.'}
        </div>
      );
    }
    return this.props.children;
  }
}
