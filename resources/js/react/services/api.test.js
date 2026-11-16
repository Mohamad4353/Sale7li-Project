import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { api, ApiError } from './api.js';

function jsonResponse(body, { ok = true, status = 200 } = {}) {
  return Promise.resolve({
    ok,
    status,
    text: () => Promise.resolve(JSON.stringify(body)),
  });
}

beforeEach(() => {
  document.head.innerHTML = '<meta name="csrf-token" content="tok-123">';
});

afterEach(() => {
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
});

describe('api client', () => {
  it('GET parses a JSON body', async () => {
    vi.stubGlobal('fetch', vi.fn(() => jsonResponse({ data: [1, 2] })));
    await expect(api.get('/x')).resolves.toEqual({ data: [1, 2] });
    const [, opts] = fetch.mock.calls[0];
    expect(opts.method).toBe('GET');
    expect(opts.headers['X-CSRF-TOKEN']).toBeUndefined();
  });

  it('POST attaches the CSRF token from the meta tag', async () => {
    vi.stubGlobal('fetch', vi.fn(() => jsonResponse({ favorited: true })));
    await api.post('/favorites/9');
    const [url, opts] = fetch.mock.calls[0];
    expect(url).toBe('/favorites/9');
    expect(opts.method).toBe('POST');
    expect(opts.headers['X-CSRF-TOKEN']).toBe('tok-123');
    expect(opts.credentials).toBe('same-origin');
  });

  it('throws ApiError with status and body on a non-2xx response', async () => {
    vi.stubGlobal('fetch', vi.fn(() => jsonResponse({ message: 'nope' }, { ok: false, status: 403 })));
    await expect(api.post('/favorites/9')).rejects.toMatchObject({
      name: 'ApiError',
      status: 403,
      data: { message: 'nope' },
    });
  });

  it('maps a network failure to ApiError status 0', async () => {
    vi.stubGlobal('fetch', vi.fn(() => Promise.reject(new TypeError('offline'))));
    await expect(api.get('/x')).rejects.toBeInstanceOf(ApiError);
  });

  it('re-throws an AbortError so callers can ignore stale requests', async () => {
    const abort = Object.assign(new Error('aborted'), { name: 'AbortError' });
    vi.stubGlobal('fetch', vi.fn(() => Promise.reject(abort)));
    await expect(api.get('/x')).rejects.toHaveProperty('name', 'AbortError');
  });
});
