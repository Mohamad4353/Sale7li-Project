import { useCallback, useEffect, useRef, useState } from 'react';
import { useTranslator } from '../app/providers/I18nProvider.jsx';

/**
 * React replacement for the Alpine `attachmentField()`. It renders a REAL
 * <input type="file" name="attachments[]" multiple> that stays part of the
 * surrounding Blade <form>, so ordinary multipart submission is unchanged.
 * React only reads the already-picked FileList to show filenames and a local
 * image preview; it uploads nothing and never sees a private URL. All MIME /
 * size / dimension / auth / storage rules stay in Laravel (StoreMessageRequest,
 * ConversationController) — untouched.
 */
const CLIPBOARD_PATH = 'M9 4h6v3H9zM6 5h2v2h8V5h2a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z';

export default function AttachmentInput({
  name = 'attachments[]',
  accept = 'image/jpeg,image/png,image/webp',
  multiple = true,
}) {
  const { t } = useTranslator();
  const [items, setItems] = useState([]);
  const objectUrls = useRef([]);

  const revokeAll = useCallback(() => {
    objectUrls.current.forEach((url) => URL.revokeObjectURL(url));
    objectUrls.current = [];
  }, []);

  // Clean up any live object URLs when the component goes away.
  useEffect(() => revokeAll, [revokeAll]);

  const handleChange = useCallback(
    (event) => {
      revokeAll();
      const files = event.target.files ? Array.from(event.target.files) : [];
      setItems(
        files.map((file) => {
          const isImage = file.type.indexOf('image/') === 0;
          const url = isImage ? URL.createObjectURL(file) : null;
          if (url) objectUrls.current.push(url);
          return { name: file.name, url };
        }),
      );
    },
    [revokeAll],
  );

  const label = t('attachments', {}, 'Add photos');

  return (
    <div className="text-sm">
      <label className="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-line bg-surface px-3 py-2 font-bold text-ink transition hover:border-ink">
        <svg
          className="h-4 w-4"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth="2"
          strokeLinecap="round"
          strokeLinejoin="round"
          aria-hidden="true"
        >
          <path d={CLIPBOARD_PATH} />
        </svg>
        <span>{label}</span>
        <input
          type="file"
          name={name}
          accept={accept}
          multiple={multiple}
          className="sr-only"
          onChange={handleChange}
        />
      </label>

      {items.length > 0 && (
        <ul className="mt-2 flex flex-wrap gap-2" aria-live="polite">
          {items.map((item, i) => (
            <li
              key={`${item.name}-${i}`}
              className="inline-flex items-center gap-2 rounded-lg border border-line bg-surface px-2 py-1 text-xs"
            >
              {item.url && <img src={item.url} alt="" className="h-8 w-8 rounded object-cover" />}
              <span className="max-w-[12rem] truncate">{item.name}</span>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
