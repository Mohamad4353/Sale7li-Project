import { useCallback, useRef, useState } from 'react';

/**
 * The small amount of behaviour every SALE7LI mutation shares:
 *   - one async call at a time (concurrent calls are ignored)
 *   - a `pending` flag and an `error` value
 *   - an optional optimistic update whose returned function is the rollback,
 *     run automatically if the call fails
 *   - `reset()` to clear pending/error/data
 *
 * It is deliberately NOT a data-fetching framework: no cache, no retries, no
 * global store. Callers own their own state; this hook only orchestrates.
 *
 * @template TVars, TResult
 * @param {(vars: TVars) => Promise<TResult>} mutationFn
 * @param {{
 *   onOptimistic?: (vars: TVars) => (void | (() => void)),
 *   onSuccess?: (result: TResult, vars: TVars) => void,
 *   onError?: (error: unknown, vars: TVars) => void,
 * }} [handlers]
 */
export function useMutation(mutationFn, handlers = {}) {
  const { onOptimistic, onSuccess, onError } = handlers;
  const [pending, setPending] = useState(false);
  const [error, setError] = useState(null);
  const [data, setData] = useState(undefined);
  const inFlight = useRef(false);

  const mutate = useCallback(
    async (vars) => {
      if (inFlight.current) return undefined;
      inFlight.current = true;
      setPending(true);
      setError(null);

      let rollback;
      try {
        if (onOptimistic) rollback = onOptimistic(vars);
        const result = await mutationFn(vars);
        setData(result);
        onSuccess?.(result, vars);
        return result;
      } catch (err) {
        if (typeof rollback === 'function') rollback(err);
        setError(err);
        onError?.(err, vars);
        return undefined;
      } finally {
        setPending(false);
        inFlight.current = false;
      }
    },
    [mutationFn, onOptimistic, onSuccess, onError],
  );

  const reset = useCallback(() => {
    setPending(false);
    setError(null);
    setData(undefined);
  }, []);

  return { mutate, pending, error, data, reset };
}
