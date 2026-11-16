import { afterEach, describe, expect, it, vi } from 'vitest';
import { render, screen, waitFor, within } from '@testing-library/react';
import { fireEvent } from '@testing-library/dom';
import App from '../app/App.jsx';
import MarketplaceSearch from './MarketplaceSearch.jsx';

const strings = {
  heading: 'Professionals',
  sub: 'Search',
  search_label: 'Search professionals',
  search_placeholder: 'Name or service',
  category_label: 'Category',
  all_categories: 'All categories',
  sort_label: 'Sort by',
  rating_label: 'Minimum rating',
  price_label: 'Price',
  price_min: 'Min',
  price_max: 'Max',
  result_count: ':count found',
  loading: 'Loading…',
  empty_title: 'Nothing here',
  empty_hint: 'Adjust your search',
  error: 'Could not load professionals.',
  retry: 'Try again',
  view_profile: 'View profile',
  new_professional: 'New',
  verified: 'Verified',
  years_experience: ':count yrs',
  service_area: 'Within :km km',
  page_of: 'Page :current of :last',
  go_to_page: 'Go to page :page',
  prev_page: 'Previous',
  next_page: 'Next',
  pagination_nav: 'Pagination',
  availability_available: 'Available',
  availability_available_today: 'Available today',
  availability_busy: 'Busy',
  availability_unavailable: 'Unavailable',
};

const card = (name, id, extra = {}) => ({
  id,
  name,
  verified: true,
  experience_years: 4,
  rating: { average: 4.2, count: 9 },
  services: ['Plumbing'],
  availability: 'available',
  service_radius_km: 10,
  photo_url: null,
  profile_url: `/professionals/${id}`,
  favorite_url: `/customer/favorites/${id}`,
  favorited: false,
  ...extra,
});

const initialWorkers = [card('Layla Plumber', 1), card('Omar Electric', 2)];
const initialMeta = { current_page: 1, last_page: 3, per_page: 12, total: 30 };
const categories = [{ slug: 'plumbing', name: 'Plumbing' }, { slug: 'electrical', name: 'Electrical' }];
const sorts = [
  { value: 'recommended', label: 'Recommended' },
  { value: 'rating', label: 'Highest rated' },
  { value: 'newest', label: 'Newest' },
];
const ratings = [
  { value: '', label: 'Any rating' },
  { value: '5', label: '5 stars & up' },
  { value: '4', label: '4 stars & up' },
];

function resp(body, { ok = true, status = 200 } = {}) {
  return Promise.resolve({ ok, status, text: () => Promise.resolve(JSON.stringify(body)) });
}

function renderPage(extra = {}) {
  return render(
    <App
      Component={MarketplaceSearch}
      props={{
        strings, locale: 'en', dir: 'ltr',
        initialWorkers, initialMeta, categories, sorts, ratings,
        initialFilters: { q: '', category: '', sort: 'recommended', min_rating: '', min_price: '', max_price: '', page: 1 },
        api: { workers: '/api/marketplace/workers' },
        viewer: {}, debounceMs: 10,
        ...extra,
      }}
    />,
  );
}

afterEach(() => {
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
  window.history.replaceState(null, '', '/');
  document.body.innerHTML = '';
});

describe('MarketplaceSearch — parity filters', () => {
  it('renders initial server data + all filter controls, no request on mount', () => {
    const fetchMock = vi.fn(() => resp({ data: [], meta: initialMeta }));
    vi.stubGlobal('fetch', fetchMock);
    renderPage();

    expect(screen.getByText('Layla Plumber')).toBeInTheDocument();
    expect(screen.getByText('30 found')).toBeInTheDocument();
    expect(screen.getByLabelText('Search professionals')).toBeInTheDocument();
    expect(screen.getByLabelText('Category').tagName).toBe('SELECT');
    expect(screen.getByLabelText('Sort by').tagName).toBe('SELECT');
    expect(screen.getByLabelText('Minimum rating').tagName).toBe('SELECT');
    expect(screen.getByPlaceholderText('Min')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Max')).toBeInTheDocument();
    // card parity fields
    expect(within(screen.getAllByRole('listitem')[0]).getByText('Available')).toBeInTheDocument();
    expect(screen.getAllByText(/Within 10 km/)[0]).toBeInTheDocument();
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('debounced search carries every active filter and resets to page 1', async () => {
    const fetchMock = vi.fn(() => resp({ data: [card('Omar Electric', 2)], meta: { ...initialMeta, total: 1, last_page: 1 } }));
    vi.stubGlobal('fetch', fetchMock);
    renderPage({ initialFilters: { q: '', category: 'plumbing', sort: 'rating', min_rating: '4', min_price: '', max_price: '', page: 1 } });

    fireEvent.change(screen.getByLabelText('Search professionals'), { target: { value: 'oma' } });
    await waitFor(() => expect(screen.getByText('1 found')).toBeInTheDocument());

    expect(fetchMock).toHaveBeenCalledTimes(1);
    const url = new URL(fetchMock.mock.calls[0][0], 'http://x');
    expect(url.searchParams.get('q')).toBe('oma');
    expect(url.searchParams.get('category')).toBe('plumbing');
    expect(url.searchParams.get('sort')).toBe('rating');
    expect(url.searchParams.get('min_rating')).toBe('4');
  });

  it('sort / rating changes request immediately and appear in the URL', async () => {
    const fetchMock = vi.fn(() => resp({ data: [], meta: initialMeta }));
    vi.stubGlobal('fetch', fetchMock);
    renderPage();

    fireEvent.change(screen.getByLabelText('Sort by'), { target: { value: 'newest' } });
    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(1));
    expect(new URL(fetchMock.mock.calls[0][0], 'http://x').searchParams.get('sort')).toBe('newest');

    fireEvent.change(screen.getByLabelText('Minimum rating'), { target: { value: '5' } });
    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(2));
    expect(new URL(fetchMock.mock.calls[1][0], 'http://x').searchParams.get('min_rating')).toBe('5');

    expect(window.location.search).toContain('sort=newest');
    expect(window.location.search).toContain('min_rating=5');
  });

  it('omits the default sort from the URL but keeps an explicit non-default sort', async () => {
    const fetchMock = vi.fn(() => resp({ data: [], meta: initialMeta }));
    vi.stubGlobal('fetch', fetchMock);
    renderPage(); // initialFilters.sort === 'recommended' (the default)

    // On mount the clean URL must not gain ?sort=recommended.
    expect(window.location.search).not.toContain('sort');

    // An explicit non-default sort is written…
    fireEvent.change(screen.getByLabelText('Sort by'), { target: { value: 'newest' } });
    await waitFor(() => expect(window.location.search).toContain('sort=newest'));

    // …and switching back to the default drops it again.
    fireEvent.change(screen.getByLabelText('Sort by'), { target: { value: 'recommended' } });
    await waitFor(() => expect(window.location.search).not.toContain('sort'));
  });

  it('price range is debounced and sent as min_price / max_price', async () => {
    const fetchMock = vi.fn(() => resp({ data: [], meta: initialMeta }));
    vi.stubGlobal('fetch', fetchMock);
    renderPage();

    fireEvent.change(screen.getByPlaceholderText('Min'), { target: { value: '50' } });
    fireEvent.change(screen.getByPlaceholderText('Max'), { target: { value: '200' } });
    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(1));

    const url = new URL(fetchMock.mock.calls[0][0], 'http://x');
    expect(url.searchParams.get('min_price')).toBe('50');
    expect(url.searchParams.get('max_price')).toBe('200');
  });

  it('does not refetch the exact server-rendered filter combination on mount', () => {
    const fetchMock = vi.fn(() => resp({ data: [], meta: initialMeta }));
    vi.stubGlobal('fetch', fetchMock);
    renderPage({ initialFilters: { q: 'plumb', category: 'plumbing', sort: 'rating', min_rating: '4', min_price: '10', max_price: '90', page: 2 } });

    expect(screen.getByLabelText('Search professionals')).toHaveValue('plumb');
    expect(screen.getByLabelText('Sort by')).toHaveValue('rating');
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('numbered pagination: real buttons, aria-current, preserves filters, resets on filter change', async () => {
    const fetchMock = vi.fn(() => resp({ data: [card('Page Two Pat', 9)], meta: { current_page: 2, last_page: 5, per_page: 12, total: 55 } }));
    vi.stubGlobal('fetch', fetchMock);
    renderPage({
      initialWorkers: [card('P1', 1)],
      initialMeta: { current_page: 1, last_page: 5, per_page: 12, total: 55 },
      initialFilters: { q: 'lay', category: '', sort: 'recommended', min_rating: '', min_price: '', max_price: '', page: 1 },
    });

    // numbered buttons (windowed: 1 2 … 5) and Prev disabled on page 1
    expect(screen.getByRole('button', { name: 'Previous' })).toBeDisabled();
    expect(screen.getByRole('button', { name: 'Go to page 1' })).toHaveAttribute('aria-current', 'page');

    fireEvent.click(screen.getByRole('button', { name: 'Go to page 2' }));
    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(1));

    const url = new URL(fetchMock.mock.calls[0][0], 'http://x');
    expect(url.searchParams.get('page')).toBe('2');
    expect(url.searchParams.get('q')).toBe('lay');           // filter preserved
    expect(window.location.search).toContain('page=2');
    await waitFor(() => expect(screen.getByRole('button', { name: 'Go to page 2' })).toHaveAttribute('aria-current', 'page'));
    expect(screen.getByRole('button', { name: 'Previous' })).not.toBeDisabled();
    expect(screen.getByText('Page 2 of 5')).toBeInTheDocument();   // sr-only live announcement

    // changing a non-page filter resets page → 1
    fetchMock.mockImplementation(() => resp({ data: [card('R1', 2)], meta: { current_page: 1, last_page: 2, per_page: 12, total: 14 } }));
    fireEvent.change(screen.getByLabelText('Sort by'), { target: { value: 'newest' } });
    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(2));
    expect(new URL(fetchMock.mock.calls[1][0], 'http://x').searchParams.get('page')).toBeNull();
  });

  it('retry re-requests the current filters after an error', async () => {
    const fetchMock = vi.fn()
      .mockImplementationOnce(() => resp({ message: 'boom' }, { ok: false, status: 500 }))
      .mockImplementationOnce(() => resp({ data: [card('Layla Plumber', 1)], meta: { ...initialMeta, total: 1 } }));
    vi.stubGlobal('fetch', fetchMock);
    renderPage({ initialFilters: { q: 'lay', category: 'plumbing', sort: 'recommended', min_rating: '', min_price: '', max_price: '', page: 1 } });

    fireEvent.change(screen.getByLabelText('Search professionals'), { target: { value: 'layl' } });
    await waitFor(() => expect(screen.getByRole('alert')).toBeInTheDocument());

    fireEvent.click(screen.getByRole('button', { name: 'Try again' }));
    await waitFor(() => expect(screen.queryByRole('alert')).not.toBeInTheDocument());
    const lastUrl = new URL(fetchMock.mock.calls.at(-1)[0], 'http://x');
    expect(lastUrl.searchParams.get('q')).toBe('layl');
    expect(lastUrl.searchParams.get('category')).toBe('plumbing');
  });

  it('cancels a stale request superseded by a newer one', async () => {
    vi.stubGlobal('fetch', vi.fn((url, opts) => {
      if (url.includes('q=old')) {
        return new Promise((_, reject) => {
          opts.signal.addEventListener('abort', () => reject(Object.assign(new Error('a'), { name: 'AbortError' })));
        });
      }
      return resp({ data: [card('Fresh Result', 3)], meta: { ...initialMeta, total: 1 } });
    }));
    renderPage();

    const input = screen.getByLabelText('Search professionals');
    fireEvent.change(input, { target: { value: 'old' } });
    await waitFor(() => expect(screen.getByText('Loading…')).toBeInTheDocument());
    fireEvent.change(input, { target: { value: 'new' } });

    await waitFor(() => expect(screen.getByText('Fresh Result')).toBeInTheDocument());
    expect(screen.getByText('1 found')).toBeInTheDocument();
  });

  it('shows a favourite button per card only for a customer viewer', () => {
    vi.stubGlobal('fetch', vi.fn(() => resp({ data: [], meta: initialMeta })));
    renderPage({ viewer: { isCustomer: true } });
    expect(screen.getAllByRole('button', { name: /favourites/i })).toHaveLength(2);
  });

  it('honours RTL and Arabic control labels', () => {
    vi.stubGlobal('fetch', vi.fn(() => resp({ data: [], meta: initialMeta })));
    const { container } = renderPage({
      dir: 'rtl',
      strings: { ...strings, sort_label: 'ترتيب حسب', rating_label: 'أدنى تقييم', category_label: 'الفئة' },
    });
    expect(container.querySelector('section')).toHaveAttribute('dir', 'rtl');
    expect(screen.getByLabelText('ترتيب حسب')).toBeInTheDocument();
    expect(screen.getByLabelText('أدنى تقييم')).toBeInTheDocument();
  });
});
