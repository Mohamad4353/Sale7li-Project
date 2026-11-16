/**
 * Mirrors the Blade <x-avatar>: an initials chip, or the photo (served through
 * the authorized workers.photo route — never a storage path) when `src` is set.
 * Photos are lazy + async-decoded with explicit dimensions and fall back to
 * initials on load error.
 */
const SIZES = { sm: 'h-9 w-9 text-[11px]', md: 'h-11 w-11 text-xs', lg: 'h-16 w-16 text-lg' };
const PX = { sm: 36, md: 44, lg: 64 };

function initials(name = '') {
  return name
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((p) => p[0])
    .join('')
    .toUpperCase();
}

export default function Avatar({ name = '', src = null, size = 'md', className = '' }) {
  const dim = SIZES[size] || SIZES.md;
  const px = PX[size] || PX.md;

  if (src) {
    return (
      <img
        src={src}
        alt=""
        width={px}
        height={px}
        loading="lazy"
        decoding="async"
        className={`shrink-0 rounded-full bg-ink object-cover ${dim} ${className}`.trim()}
        onError={(e) => {
          e.currentTarget.style.display = 'none';
        }}
      />
    );
  }

  return (
    <span
      aria-hidden="true"
      className={`grid shrink-0 place-items-center rounded-full bg-ink font-extrabold text-white ${dim} ${className}`.trim()}
    >
      {initials(name) || '?'}
    </span>
  );
}
