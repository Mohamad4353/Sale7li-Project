import { useCallback, useState } from 'react';
import { api } from '../services/api.js';
import { useMutation } from './useMutation.js';

/**
 * Client state for one favourite toggle, built on useMutation. The favourite
 * boolean lives here; useMutation owns pending/error/dedup and runs the
 * rollback returned by onOptimistic if the POST fails. Laravel — not this hook
 * — decides whether the mutation is allowed and returns the authoritative state.
 *
 * @param {{ url: string, initial: boolean }} opts
 */
export function useFavorite({ url, initial }) {
  const [favorited, setFavorited] = useState(Boolean(initial));

  const { mutate, pending, error } = useMutation(() => api.post(url), {
    onOptimistic: () => {
      let previous;
      setFavorited((current) => {
        previous = current;
        return !current;
      });
      return () => setFavorited(previous); // rollback
    },
    onSuccess: (body) => setFavorited(Boolean(body?.favorited)), // reconcile
  });

  const toggle = useCallback(() => mutate(), [mutate]);

  return { favorited, pending, error, toggle };
}
