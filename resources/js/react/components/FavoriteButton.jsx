import { useTranslator } from '../app/providers/I18nProvider.jsx';
import { useFavorite } from '../hooks/useFavorite.js';

/**
 * A real <button> that saves/removes a professional from the signed-in
 * customer's favourites via the existing Laravel toggle endpoint.
 *
 * - aria-pressed reflects the saved state; aria-label carries the full action
 * - disabled while the request is in flight (no double submit)
 * - optimistic flip with rollback on failure, error announced politely
 * - state is shown by glyph + text, not colour alone
 * - fixed min width so toggling never shifts the layout
 */
export default function FavoriteButton({ url, name, initialFavorited = false }) {
  const { t } = useTranslator();
  const { favorited, pending, error, toggle } = useFavorite({ url, initial: initialFavorited });

  const label = favorited
    ? t('favorite_remove', { name }, `Remove ${name} from favourites`)
    : t('favorite_add', { name }, `Save ${name} to favourites`);

  return (
    <span className="inline-flex flex-col items-start gap-1">
      <button
        type="button"
        onClick={toggle}
        disabled={pending}
        aria-pressed={favorited}
        aria-label={label}
        className={[
          'inline-flex min-w-[6.5rem] items-center justify-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-bold transition',
          'focus:outline-none focus-visible:ring-4 focus-visible:ring-ink/10 disabled:opacity-60',
          favorited
            ? 'border-rose-200 bg-rose-50 text-rose-600'
            : 'border-line bg-surface text-muted hover:border-slate-300 hover:text-ink',
        ].join(' ')}
      >
        <span aria-hidden="true">{favorited ? '♥' : '♡'}</span>
        <span>{favorited ? t('favorite_saved', {}, 'Saved') : t('favorite_save', {}, 'Save')}</span>
      </button>

      {error && (
        <span role="status" className="text-[11px] font-semibold text-rose-600">
          {t('favorite_error', {}, 'Could not update favourites.')}
        </span>
      )}
    </span>
  );
}
