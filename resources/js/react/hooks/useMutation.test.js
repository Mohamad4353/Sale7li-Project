import { describe, expect, it, vi } from 'vitest';
import { renderHook, act } from '@testing-library/react';
import { useMutation } from './useMutation.js';

describe('useMutation', () => {
  it('tracks pending and returns the result on success', async () => {
    const fn = vi.fn(() => Promise.resolve({ ok: 1 }));
    const { result } = renderHook(() => useMutation(fn));

    expect(result.current.pending).toBe(false);
    let returned;
    await act(async () => {
      returned = await result.current.mutate({ id: 5 });
    });

    expect(fn).toHaveBeenCalledWith({ id: 5 });
    expect(returned).toEqual({ ok: 1 });
    expect(result.current.pending).toBe(false);
    expect(result.current.error).toBeNull();
    expect(result.current.data).toEqual({ ok: 1 });
  });

  it('runs the rollback from onOptimistic and records the error on failure', async () => {
    const rollback = vi.fn();
    const onOptimistic = vi.fn(() => rollback);
    const boom = new Error('nope');
    const { result } = renderHook(() =>
      useMutation(() => Promise.reject(boom), { onOptimistic }),
    );

    await act(async () => {
      await result.current.mutate();
    });

    expect(onOptimistic).toHaveBeenCalledTimes(1);
    expect(rollback).toHaveBeenCalledTimes(1);
    expect(result.current.error).toBe(boom);
  });

  it('ignores a second call while one is in flight', async () => {
    let resolve;
    const fn = vi.fn(() => new Promise((r) => { resolve = () => r('done'); }));
    const { result } = renderHook(() => useMutation(fn));

    await act(async () => {
      result.current.mutate();
      result.current.mutate();
      result.current.mutate();
    });
    expect(fn).toHaveBeenCalledTimes(1);
    expect(result.current.pending).toBe(true);

    await act(async () => { resolve(); });
    expect(result.current.pending).toBe(false);
  });

  it('reset() clears error and data', async () => {
    const { result } = renderHook(() => useMutation(() => Promise.reject(new Error('x'))));
    await act(async () => { await result.current.mutate(); });
    expect(result.current.error).toBeTruthy();

    act(() => result.current.reset());
    expect(result.current.error).toBeNull();
    expect(result.current.data).toBeUndefined();
  });
});
