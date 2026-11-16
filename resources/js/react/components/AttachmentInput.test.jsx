import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import { fireEvent } from '@testing-library/dom';
import App from '../app/App.jsx';
import AttachmentInput from './AttachmentInput.jsx';

const strings = { attachments: 'Add photos' };

function renderInput(props = {}, { dir = 'ltr', locale = 'en' } = {}) {
  return render(
    <App Component={AttachmentInput} props={{ strings, locale, dir, ...props }} />,
  );
}

function file(name, type, bytes = 4) {
  return new File([new Uint8Array(bytes)], name, { type });
}

let created = [];
let revoked = [];
// jsdom has no object-URL statics; fall back to no-ops so teardown never
// restores `undefined` (which the unmount cleanup would then try to call).
const originalCreate = URL.createObjectURL ?? (() => 'blob:noop');
const originalRevoke = URL.revokeObjectURL ?? (() => {});

beforeEach(() => {
  created = [];
  revoked = [];
  URL.createObjectURL = vi.fn(() => {
    const url = `blob:mock/${created.length}`;
    created.push(url);
    return url;
  });
  URL.revokeObjectURL = vi.fn((url) => revoked.push(url));
});

afterEach(() => {
  URL.createObjectURL = originalCreate;
  URL.revokeObjectURL = originalRevoke;
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
  document.body.innerHTML = '';
});

describe('AttachmentInput', () => {
  it('renders a real multiple file input with the unchanged name and accept', () => {
    renderInput();
    const input = document.querySelector('input[type="file"]');
    expect(input).toBeInTheDocument();
    expect(input).toHaveAttribute('name', 'attachments[]');
    expect(input).toHaveAttribute('multiple');
    expect(input).toHaveAttribute('accept', 'image/jpeg,image/png,image/webp');
    expect(screen.getByText('Add photos')).toBeInTheDocument();
  });

  it('shows the selected filenames and a preview for images only', () => {
    renderInput();
    const input = document.querySelector('input[type="file"]');

    fireEvent.change(input, {
      target: { files: [file('photo.png', 'image/png'), file('notes.pdf', 'application/pdf')] },
    });

    expect(screen.getByText('photo.png')).toBeInTheDocument();
    expect(screen.getByText('notes.pdf')).toBeInTheDocument();

    const imgs = document.querySelectorAll('li img');
    expect(imgs).toHaveLength(1); // only the image file got a preview
    expect(imgs[0]).toHaveAttribute('alt', ''); // decorative
    expect(URL.createObjectURL).toHaveBeenCalledTimes(1);
  });

  it('revokes previous object URLs when the selection changes', () => {
    renderInput();
    const input = document.querySelector('input[type="file"]');

    fireEvent.change(input, { target: { files: [file('a.png', 'image/png')] } });
    expect(created).toHaveLength(1);

    fireEvent.change(input, { target: { files: [file('b.webp', 'image/webp')] } });
    expect(revoked).toContain('blob:mock/0');
  });

  it('revokes object URLs on unmount', () => {
    const { unmount } = renderInput();
    const input = document.querySelector('input[type="file"]');
    fireEvent.change(input, { target: { files: [file('a.png', 'image/png')] } });

    unmount();
    expect(revoked).toContain('blob:mock/0');
  });

  it('performs no upload — no fetch is made by the component', () => {
    const fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);
    renderInput();
    fireEvent.change(document.querySelector('input[type="file"]'), {
      target: { files: [file('a.png', 'image/png')] },
    });
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('the preview list is a polite live region and images are not interactive', () => {
    renderInput();
    fireEvent.change(document.querySelector('input[type="file"]'), {
      target: { files: [file('a.png', 'image/png')] },
    });
    expect(document.querySelector('ul[aria-live="polite"]')).toBeInTheDocument();
    expect(document.querySelector('li img').closest('button')).toBeNull();
    expect(document.querySelector('li img').closest('a')).toBeNull();
  });
});
