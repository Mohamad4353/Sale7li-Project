import { afterEach, describe, expect, it, vi } from 'vitest';
import { renderHook } from '@testing-library/react';
import { act } from 'react';
import { useDebouncedValue } from './useDebouncedValue.js';

afterEach(() => vi.useRealTimers());

describe('useDebouncedValue', () => {
  it('only updates after the value has been stable for `delay` ms', () => {
    vi.useFakeTimers();
    const { result, rerender } = renderHook(({ v }) => useDebouncedValue(v, 300), {
      initialProps: { v: 'a' },
    });

    expect(result.current).toBe('a');

    rerender({ v: 'ab' });
    rerender({ v: 'abc' });
    act(() => vi.advanceTimersByTime(299));
    expect(result.current).toBe('a'); // still debouncing

    act(() => vi.advanceTimersByTime(1));
    expect(result.current).toBe('abc'); // settled to the latest value only
  });
});
