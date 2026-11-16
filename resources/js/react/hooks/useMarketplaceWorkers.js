import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { api } from '../services/api.js';
import { useDebouncedValue } from './useDebouncedValue.js';

// Joins filter values into one effect-dependency string. Uses a control char
// that can never appear in a real filter value.
const SEP = String.fromCharCode(31);

/** Drop empty values so the query string matches the server's defaults. */
function toQuery(params) {
  const usp = new URLSearchParams();
  Object.entries(params).forEach(([k, v]) => {
    if (v !== '' && v !== null && v !== undefined && !(k === 'page' && String(v) === '1')) {
      usp.set(k, String(v));
    }
  });
  return usp.toString();
}

/**
 * Backs the React marketplace on the canonical /professionals page. Free-text
 * query and the price inputs are debounced; category / sort / rating / page
 * apply immediately. Every request runs through an AbortController so a
 * superseded one is cancelled. The server already rendered the result for the
 * URL's initial filters, so that exact combination does not refetch on mount.
 *
 * @param {{
 *   endpoint: string,
 *   filters: { q, category, sort, min_rating, min_price, max_price, page },
 *   initialWorkers?: object[], initialMeta?: object,
 *   initialFilters?: object, debounceMs?: number
 * }} opts
 */
export function useMarketplaceWorkers({
  endpoint,
  filters,
  initialWorkers = [],
  initialMeta = null,
  initialFilters = {},
  debounceMs = 300,
}) {
  const textKey = `${filters.q ?? ''}${SEP}${filters.min_price ?? ''}${SEP}${filters.max_price ?? ''}`;
  const debouncedTextKey = useDebouncedValue(textKey, debounceMs);
  const immediateKey = `${filters.category ?? ''}${SEP}${filters.sort ?? ''}${SEP}${filters.min_rating ?? ''}${SEP}${filters.page ?? 1}`;

  const [state, setState] = useState({
    workers: initialWorkers,
    meta: initialMeta,
    loading: false,
    error: null,
  });
  const [nonce, setNonce] = useState(0);
  const firstRun = useRef(true);

  const initialKey = useMemo(() => {
    const f = initialFilters;
    return [
      f.q ?? '', f.min_price ?? '', f.max_price ?? '',
      f.category ?? '', f.sort ?? '', f.min_rating ?? '', String(f.page ?? 1),
    ].join(SEP);
    // initialFilters is a server-rendered constant for the life of the mount
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const reload = useCallback(() => setNonce((n) => n + 1), []);

  useEffect(() => {
    const [q, minPrice, maxPrice] = debouncedTextKey.split(SEP);
    const [category, sort, minRating, page] = immediateKey.split(SEP);
    const currentKey = [q, minPrice, maxPrice, category, sort, minRating, page].join(SEP);

    if (firstRun.current) {
      firstRun.current = false;
      if (currentKey === initialKey && nonce === 0) return undefined;
    }

    const controller = new AbortController();
    setState((s) => ({ ...s, loading: true, error: null }));

    const qs = toQuery({
      q, category, sort, min_rating: minRating, min_price: minPrice, max_price: maxPrice, page,
    });
    api
      .get(`${endpoint}${qs ? `?${qs}` : ''}`, { signal: controller.signal })
      .then((body) => {
        setState({
          workers: Array.isArray(body?.data) ? body.data : [],
          meta: body?.meta ?? null,
          loading: false,
          error: null,
        });
      })
      .catch((error) => {
        if (error?.name === 'AbortError') return; // superseded by a newer request
        setState((s) => ({ ...s, loading: false, error }));
      });

    return () => controller.abort();
  }, [endpoint, debouncedTextKey, immediateKey, nonce, initialKey]);

  return { ...state, reload };
}
