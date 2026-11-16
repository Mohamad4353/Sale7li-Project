/**
 * Compact numbered pager comparable to Laravel's Blade paginator:
 *   ‹ Prev  1 … 4 [5] 6 … 12  Next ›
 * Real <button>s (the list is API-driven, not URL links). `aria-current="page"`
 * on the active page, disabled prev/next at the ends, an sr-only live "Page x of
 * y" for screen readers, localized labels, RTL-safe (flex row mirrors; chevrons
 * are plain glyphs). The window is bounded — never a huge strip.
 */
function windowPages(current, last) {
  const pages = new Set([1, last, current, current - 1, current + 1]);
  const sorted = [...pages].filter((p) => p >= 1 && p <= last).sort((a, b) => a - b);
  const out = [];
  let prev = 0;
  sorted.forEach((p) => {
    if (p - prev > 1) out.push('…');
    out.push(p);
    prev = p;
  });
  return out;
}

export default function Pagination({ meta, onChange, labels }) {
  const current = meta?.current_page ?? 1;
  const last = meta?.last_page ?? 1;
  if (last <= 1) return null;

  const edge =
    'inline-flex min-h-[44px] items-center gap-1.5 rounded-xl border border-line bg-surface px-3 text-sm font-bold text-ink transition hover:border-line-strong hover:bg-surface-2 focus:outline-none focus-visible:ring-4 focus-visible:ring-ink/15 disabled:cursor-not-allowed disabled:bg-surface-2 disabled:text-faint disabled:hover:border-line disabled:hover:bg-surface-2';
  const num =
    'inline-grid min-h-[44px] min-w-[44px] place-items-center rounded-xl border px-2 text-sm font-bold transition focus:outline-none focus-visible:ring-4 focus-visible:ring-ink/15';

  return (
    <nav aria-label={labels.nav} className="flex flex-wrap items-center justify-between gap-2">
      <button type="button" className={edge} disabled={current <= 1} onClick={() => onChange(current - 1)}>
        <span aria-hidden="true" className="rtl-flip">‹</span>
        <span className="hidden sm:inline">{labels.prev}</span>
      </button>

      <ol className="flex flex-wrap items-center gap-1">
        {windowPages(current, last).map((p, i) =>
          p === '…' ? (
            <li key={`gap-${i}`} aria-hidden="true" className="px-1 text-faint">
              …
            </li>
          ) : (
            <li key={p}>
              <button
                type="button"
                onClick={() => onChange(p)}
                aria-current={p === current ? 'page' : undefined}
                aria-label={labels.goToPage.replace(':page', String(p))}
                className={
                  p === current
                    ? `${num} border-ink bg-ink text-white`
                    : `${num} border-line bg-surface text-ink hover:border-line-strong hover:bg-surface-2`
                }
              >
                {p}
              </button>
            </li>
          ),
        )}
      </ol>

      <button type="button" className={edge} disabled={current >= last} onClick={() => onChange(current + 1)}>
        <span className="hidden sm:inline">{labels.next}</span>
        <span aria-hidden="true" className="rtl-flip">›</span>
      </button>

      <span className="sr-only" aria-live="polite">
        {labels.pageOf.replace(':current', String(current)).replace(':last', String(last))}
      </span>
    </nav>
  );
}
