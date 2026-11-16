/**
 * Minimal same-origin JSON client built on native fetch. No Axios, no business
 * rules — callers own the URLs and payloads. Mutations send the CSRF token from
 * the existing <meta name="csrf-token"> (never passed through React props).
 */

export class ApiError extends Error {
  constructor(message, { status = 0, data = null } = {}) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.data = data;
  }
}

function csrfToken() {
  if (typeof document === 'undefined') return '';
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

async function request(url, { method = 'GET', body, signal } = {}) {
  const headers = { Accept: 'application/json' };
  const isMutation = method !== 'GET' && method !== 'HEAD';

  if (isMutation) {
    headers['X-CSRF-TOKEN'] = csrfToken();
    headers['X-Requested-With'] = 'XMLHttpRequest';
  }
  if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
  }

  let response;
  try {
    response = await fetch(url, {
      method,
      headers,
      credentials: 'same-origin',
      signal,
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });
  } catch (err) {
    if (err?.name === 'AbortError') throw err; // let callers ignore stale requests
    throw new ApiError('network', { status: 0 });
  }

  const text = await response.text();
  let data = null;
  if (text) {
    try {
      data = JSON.parse(text);
    } catch {
      data = null;
    }
  }

  if (!response.ok) {
    throw new ApiError(`http_${response.status}`, { status: response.status, data });
  }
  return data;
}

export const api = {
  get: (url, opts) => request(url, { ...opts, method: 'GET' }),
  post: (url, body, opts) => request(url, { ...opts, method: 'POST', body: body ?? {} }),
  del: (url, opts) => request(url, { ...opts, method: 'DELETE' }),
};
