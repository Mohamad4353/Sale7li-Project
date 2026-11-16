import { useCallback, useEffect, useId, useState } from 'react';
import { useTranslator } from '../app/providers/I18nProvider.jsx';
import { useMarketplaceWorkers } from '../hooks/useMarketplaceWorkers.js';
import Card from '../components/Card.jsx';
import Button from '../components/Button.jsx';
import Avatar from '../components/Avatar.jsx';
import SearchInput from '../components/SearchInput.jsx';
import CategorySelect from '../components/CategorySelect.jsx';
import NativeSelect from '../components/NativeSelect.jsx';
import Pagination from '../components/Pagination.jsx';
import FavoriteButton from '../components/FavoriteButton.jsx';

/**
 * The React marketplace that mounts over the Blade fallback on the canonical
 * /professionals page, with functional parity to the server-rendered filtering:
 * q + category + sort + minimum-rating + price range + pagination, all in a
 * shareable URL (History API, no router). Cards carry photo / verified / rating
 * / experience / services / availability / service radius / favourite —
 * mirroring the Step 4 <x-worker-card> visual spec.
 *
 * Data contract:
 *   A. server props  → initialFilters, initialWorkers, initialMeta, categories,
 *                      sorts, ratings, api.workers, viewer.isCustomer, strings
 *   B. API response  → GET /api/marketplace/workers?... → { data:[card], meta }
 *   C. local state   → the filter form values + per-card favourite state
 *
 * Laravel stays authoritative for filtering, ordering, visibility and the
 * "Save" mutation. React sends filter values; it never filters or sorts data.
 */
const AVAIL_TONE = {
  available_today: { text: 'text-emerald-700', dot: 'bg-emerald-500' },
  available: { text: 'text-emerald-700', dot: 'bg-emerald-400' },
  busy: { text: 'text-amber-700', dot: 'bg-amber-500' },
  unavailable: { text: 'text-faint', dot: 'bg-line-strong' },
};

const SHIELD = 'M12 3 5 6v6c0 5 3 7 7 9 4-2 7-4 7-9V6Z';
const USERS =
  'M16 19a5 5 0 0 0-10 0M11 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM19 18a4 4 0 0 0-3-3.9M16 5.1A3 3 0 0 1 16 11';

function Glyph({ path, className = 'h-4 w-4' }) {
  return (
    <svg
      className={className}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
    >
      <path d={path} />
    </svg>
  );
}

function Stars({ average }) {
  const filled = Math.max(0, Math.min(5, Math.round(average || 0)));
  return (
    <span aria-hidden="true">
      <span className="text-amber-500">{'★'.repeat(filled)}</span>
      <span className="text-line">{'☆'.repeat(5 - filled)}</span>
    </span>
  );
}

function WorkerCard({ worker, canFavorite, t }) {
  const hasRating = worker.rating && worker.rating.count > 0;
  const availLabel = t(`availability_${worker.availability}`, {}, worker.availability);
  const availTone = AVAIL_TONE[worker.availability] || AVAIL_TONE.unavailable;
  const extraServices = (worker.services?.length || 0) - 3;

  return (
    <Card as="li" interactive className="flex flex-col">
      <div className="flex items-start gap-3">
        <Avatar name={worker.name} src={worker.photo_url} />
        <div className="min-w-0 flex-1">
          <div className="flex items-start justify-between gap-2">
            <h3 className="truncate font-extrabold text-ink">{worker.name}</h3>
            {worker.verified && (
              <span className="inline-flex shrink-0 items-center gap-1 text-[11px] font-extrabold text-success-fg">
                <Glyph path={SHIELD} className="h-3.5 w-3.5" />
                {t('verified', {}, 'Verified')}
              </span>
            )}
          </div>
          <p className="mt-0.5 text-xs font-bold text-faint">
            {hasRating ? (
              <>
                <Stars average={worker.rating.average} />{' '}
                <span className="text-ink">{worker.rating.average.toFixed(1)}</span>{' '}
                <span>({worker.rating.count})</span>
              </>
            ) : (
              t('new_professional', {}, 'New')
            )}
          </p>
          <span className={`mt-1 inline-flex items-center gap-1.5 text-xs font-bold ${availTone.text}`}>
            <span aria-hidden="true" className={`h-1.5 w-1.5 shrink-0 rounded-full ${availTone.dot}`} />
            {availLabel}
          </span>
        </div>
      </div>

      {worker.services?.length > 0 && (
        <ul className="mt-3 flex flex-wrap gap-1.5">
          {worker.services.slice(0, 3).map((s) => (
            <li key={s} className="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-extrabold text-slate-600">
              {s}
            </li>
          ))}
          {extraServices > 0 && (
            <li className="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-extrabold text-slate-600">
              +{extraServices}
            </li>
          )}
        </ul>
      )}

      <p className="mt-3 flex flex-wrap items-center gap-x-2 t-small">
        <span>
          {t('years_experience', { count: worker.experience_years }, `${worker.experience_years} yrs`)}
        </span>
        {worker.service_radius_km ? (
          <>
            <span aria-hidden="true" className="text-line">·</span>
            <span>
              {t('service_area', { km: worker.service_radius_km }, `${worker.service_radius_km} km`)}
            </span>
          </>
        ) : null}
      </p>

      <div className="mt-4 flex flex-wrap items-center gap-2 border-t border-line pt-4">
        <Button href={worker.profile_url} variant="secondary" size="sm">
          {t('view_profile', {}, 'View profile')}
        </Button>
        {canFavorite && (
          <FavoriteButton url={worker.favorite_url} name={worker.name} initialFavorited={worker.favorited} />
        )}
      </div>
    </Card>
  );
}

const FILTER_KEYS = ['q', 'category', 'sort', 'min_rating', 'min_price', 'max_price', 'page'];

export default function MarketplaceSearch({
  initialFilters = {},
  initialWorkers = [],
  initialMeta = null,
  categories = [],
  sorts = [],
  ratings = [],
  api = {},
  viewer = {},
  debounceMs = 300,
}) {
  const { t, dir } = useTranslator();
  const sortId = useId();

  const defaultSort = sorts[0]?.value ?? '';

  const [filters, setFilters] = useState(() => ({
    q: initialFilters.q || '',
    category: initialFilters.category || '',
    sort: initialFilters.sort || defaultSort,
    min_rating: initialFilters.min_rating || '',
    min_price: initialFilters.min_price || '',
    max_price: initialFilters.max_price || '',
    page: Number(initialFilters.page) || 1,
  }));

  // Any filter change resets to page 1, except a page change itself.
  const setFilter = useCallback((key, value) => {
    setFilters((prev) => ({ ...prev, [key]: value, ...(key === 'page' ? {} : { page: 1 }) }));
  }, []);

  const clearAll = useCallback(() => {
    setFilters({
      q: '', category: '', sort: defaultSort,
      min_rating: '', min_price: '', max_price: '', page: 1,
    });
  }, [defaultSort]);

  const { workers, meta, loading, error, reload } = useMarketplaceWorkers({
    endpoint: api.workers,
    filters,
    initialWorkers,
    initialMeta,
    initialFilters,
    debounceMs,
  });

  // Shareable URL — write every active filter, preserve unrelated params.
  // A value equal to its default (page 1, or the default sort) is omitted so a
  // clean /professionals never becomes /professionals?sort=recommended.
  useEffect(() => {
    if (typeof window === 'undefined' || !window.history?.replaceState) return;
    const params = new URLSearchParams(window.location.search);
    FILTER_KEYS.forEach((k) => {
      const v = filters[k];
      const isDefault =
        (k === 'page' && Number(v) === 1) || (k === 'sort' && v === defaultSort);
      if (v && !isDefault) params.set(k, String(v));
      else params.delete(k);
    });
    const qs = params.toString();
    window.history.replaceState(null, '', qs ? `${window.location.pathname}?${qs}` : window.location.pathname);
  }, [filters, defaultSort]);

  const canFavorite = Boolean(viewer.isCustomer);
  const total = meta?.total ?? workers.length;
  const anyActive =
    Boolean(filters.q || filters.category || filters.min_rating || filters.min_price || filters.max_price) ||
    filters.sort !== defaultSort;

  return (
    <section dir={dir} className="space-y-6">
      {/* The page <h1> + intro are server-rendered in Blade, outside this island. */}

      {/* 2 — Search & filters, grouped on one surface. */}
      <div className="rounded-2xl border border-line bg-surface p-4 shadow-card sm:p-5">
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <SearchInput
            value={filters.q}
            onChange={(v) => setFilter('q', v)}
            label={t('search_label', {}, 'Search professionals')}
            placeholder={t('search_placeholder', {}, 'Name or service…')}
            labelHidden={false}
            className="sm:col-span-2 lg:col-span-1"
          />
          <CategorySelect
            categories={categories}
            value={filters.category}
            onChange={(v) => setFilter('category', v)}
            label={t('category_label', {}, 'Category')}
            allLabel={t('all_categories', {}, 'All categories')}
          />
          <NativeSelect
            options={ratings}
            value={filters.min_rating}
            onChange={(v) => setFilter('min_rating', v)}
            label={t('rating_label', {}, 'Minimum rating')}
          />
        </div>

        <fieldset className="mt-4 border-t border-line pt-4">
          <legend className="t-label mb-2">{t('price_label', {}, 'Price')} ($)</legend>
          <div className="flex flex-wrap items-center gap-3">
            <label className="flex flex-col text-sm">
              <span className="sr-only">{t('price_min', {}, 'Min')}</span>
              <input
                type="number" inputMode="numeric" min="0" dir="ltr" className="input w-28"
                placeholder={t('price_min', {}, 'Min')}
                value={filters.min_price}
                onChange={(e) => setFilter('min_price', e.target.value)}
              />
            </label>
            <span aria-hidden="true" className="text-faint">–</span>
            <label className="flex flex-col text-sm">
              <span className="sr-only">{t('price_max', {}, 'Max')}</span>
              <input
                type="number" inputMode="numeric" min="0" dir="ltr" className="input w-28"
                placeholder={t('price_max', {}, 'Max')}
                value={filters.max_price}
                onChange={(e) => setFilter('max_price', e.target.value)}
              />
            </label>
          </div>
        </fieldset>
      </div>

      {/* 3 — Results / active-filter reset / sort. */}
      <div className="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-b border-line pb-4">
        <p className="text-sm font-bold text-ink" aria-live="polite">
          {loading ? t('loading', {}, 'Loading…') : t('result_count', { count: total }, `${total} results`)}
        </p>
        <div className="flex flex-wrap items-center gap-x-4 gap-y-2">
          {anyActive && (
            <button
              type="button"
              onClick={clearAll}
              className="rounded-lg px-1 py-1 text-[13px] font-bold text-muted transition hover:text-ink focus:outline-none focus-visible:ring-4 focus-visible:ring-ink/15"
            >
              {t('clear', {}, 'Clear filters')}
            </button>
          )}
          <div className="flex items-center gap-2">
            <label htmlFor={sortId} className="shrink-0 text-[13px] font-semibold text-muted">
              {t('sort_label', {}, 'Sort by')}
            </label>
            <select
              id={sortId}
              className="input w-auto min-w-[10.5rem]"
              value={filters.sort}
              onChange={(e) => setFilter('sort', e.target.value)}
            >
              {sorts.map((o) => (
                <option key={o.value} value={o.value}>
                  {o.label}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {error && (
        <div
          role="alert"
          className="flex flex-wrap items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800"
        >
          <span>{t('error', {}, 'Could not load professionals.')}</span>
          <Button variant="secondary" size="sm" onClick={reload}>
            {t('retry', {}, 'Try again')}
          </Button>
        </div>
      )}

      {loading ? (
        <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-hidden="true">
          {[0, 1, 2].map((i) => (
            <li key={i} className="h-56 animate-pulse rounded-2xl border border-line bg-surface-2" />
          ))}
        </ul>
      ) : workers.length === 0 && !error ? (
        <div className="rounded-2xl border border-line bg-surface">
          <div className="flex flex-col items-center px-6 py-12 text-center">
            <span className="grid h-12 w-12 place-items-center rounded-2xl bg-surface-2 text-muted">
              <Glyph path={USERS} className="h-6 w-6" />
            </span>
            <p className="mt-4 t-card">{t('empty_title', {}, 'No matches')}</p>
            <p className="mt-1.5 t-muted max-w-sm">{t('empty_hint', {}, 'Try a different filter.')}</p>
            {anyActive && (
              <div className="mt-5">
                <Button variant="secondary" size="sm" onClick={clearAll}>
                  {t('clear', {}, 'Clear filters')}
                </Button>
              </div>
            )}
          </div>
        </div>
      ) : (
        <>
          <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {workers.map((w) => (
              <WorkerCard key={w.id ?? w.profile_url} worker={w} canFavorite={canFavorite} t={t} />
            ))}
          </ul>
          <Pagination
            meta={meta}
            onChange={(p) => setFilter('page', p)}
            labels={{
              nav: t('pagination_nav', {}, 'Pagination'),
              prev: t('prev_page', {}, 'Previous'),
              next: t('next_page', {}, 'Next'),
              pageOf: t('page_of', {}, 'Page :current of :last'),
              goToPage: t('go_to_page', {}, 'Go to page :page'),
            }}
          />
        </>
      )}
    </section>
  );
}
