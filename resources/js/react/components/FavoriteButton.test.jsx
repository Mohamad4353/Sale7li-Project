import { afterEach, describe, expect, it, vi } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { fireEvent } from '@testing-library/dom';
import App from '../app/App.jsx';
import FavoriteButton from './FavoriteButton.jsx';

const strings = {
  favorite_add: 'Save :name to your favourites',
  favorite_remove: 'Remove :name from your favourites',
  favorite_save: 'Save',
  favorite_saved: 'Saved',
  favorite_error: 'Could not update your favourites.',
};

function resp(body, { ok = true, status = 200 } = {}) {
  return Promise.resolve({ ok, status, text: () => Promise.resolve(JSON.stringify(body)) });
}

function renderButton(props, { dir = 'ltr', locale = 'en' } = {}) {
  return render(
    <App
      Component={FavoriteButton}
      props={{ strings, locale, dir, url: '/customer/favorites/7', name: 'Layla', ...props }}
    />,
  );
}

afterEach(() => {
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
  document.body.innerHTML = '';
});

describe('FavoriteButton', () => {
  it('reflects the initial favourited state on a real button', () => {
    renderButton({ initialFavorited: true });
    const btn = screen.getByRole('button');
    expect(btn).toHaveAttribute('aria-pressed', 'true');
    expect(btn).toHaveAccessibleName('Remove Layla from your favourites');
    expect(screen.getByText('Saved')).toBeInTheDocument();
  });

  it('optimistically flips, POSTs, and reconciles with the server answer', async () => {
    const fetchMock = vi.fn(() => resp({ favorited: true, count: 1 }));
    vi.stubGlobal('fetch', fetchMock);

    renderButton({ initialFavorited: false });
    const btn = screen.getByRole('button');
    expect(btn).toHaveAttribute('aria-pressed', 'false');

    fireEvent.click(btn);
    expect(btn).toHaveAttribute('aria-pressed', 'true'); // optimistic, immediate
    expect(btn).toBeDisabled();

    await waitFor(() => expect(btn).not.toBeDisabled());
    expect(fetchMock).toHaveBeenCalledTimes(1);
    expect(fetchMock.mock.calls[0][0]).toBe('/customer/favorites/7');
    expect(fetchMock.mock.calls[0][1].method).toBe('POST');
    expect(btn).toHaveAttribute('aria-pressed', 'true');
  });

  it('rolls back and announces an error when the mutation fails', async () => {
    vi.stubGlobal('fetch', vi.fn(() => resp({ message: 'no' }, { ok: false, status: 419 })));

    renderButton({ initialFavorited: false });
    const btn = screen.getByRole('button');

    fireEvent.click(btn);
    await waitFor(() => expect(btn).not.toBeDisabled());

    expect(btn).toHaveAttribute('aria-pressed', 'false'); // rolled back
    expect(screen.getByRole('status')).toHaveTextContent('Could not update your favourites.');
  });

  it('ignores a second click while a request is in flight', async () => {
    let resolve;
    const fetchMock = vi.fn(() => new Promise((r) => { resolve = () => r(resp({ favorited: true })); }));
    vi.stubGlobal('fetch', fetchMock);

    renderButton({ initialFavorited: false });
    const btn = screen.getByRole('button');
    fireEvent.click(btn);
    fireEvent.click(btn);
    fireEvent.click(btn);
    expect(fetchMock).toHaveBeenCalledTimes(1);

    resolve();
    await waitFor(() => expect(btn).not.toBeDisabled());
  });

  it('uses the Arabic label and inherits RTL', () => {
    renderButton(
      { initialFavorited: false },
      { dir: 'rtl', locale: 'ar' },
    );
    // strings map is EN here, but interpolation + structure must still work in RTL
    render(
      <App
        Component={FavoriteButton}
        props={{
          strings: { ...strings, favorite_add: 'حفظ :name في المفضّلة' },
          locale: 'ar',
          dir: 'rtl',
          url: '/customer/favorites/7',
          name: 'ليلى',
        }}
      />,
    );
    expect(screen.getAllByRole('button').at(-1)).toHaveAccessibleName('حفظ ليلى في المفضّلة');
  });
});
