/**
 * Mirrors the Blade <x-button> class set so a React button is visually
 * identical to a server-rendered one. Renders an <a> when `href` is given,
 * a real <button> otherwise — never a clickable <div>.
 */
const BASE =
  'inline-flex items-center justify-center gap-2 rounded-xl font-bold transition duration-200 focus:outline-none focus-visible:ring-4 disabled:pointer-events-none disabled:opacity-50';

const VARIANTS = {
  primary: 'bg-ink text-white hover:bg-ink-soft focus-visible:ring-ink/15',
  secondary:
    'border border-line bg-surface text-ink hover:border-line-strong hover:bg-surface-2 focus-visible:ring-ink/15',
  accent: 'bg-brand text-ink font-extrabold hover:brightness-95 focus-visible:ring-brand/30',
  ghost: 'border border-white/15 bg-white/5 text-white hover:bg-white/10 focus-visible:ring-white/20',
  danger: 'border border-rose-200 bg-surface text-rose-600 hover:bg-rose-50 focus-visible:ring-rose-500/20',
  link: 'font-bold text-ink underline decoration-2 decoration-brand underline-offset-4 hover:decoration-ink focus-visible:ring-ink/15',
};

const SIZES = {
  sm: 'min-h-[2.25rem] px-3 py-2 text-xs',
  md: 'min-h-[2.75rem] px-5 py-3 text-sm',
  lg: 'min-h-12 px-6 py-3.5 text-sm',
};

export default function Button({
  href,
  variant = 'primary',
  size = 'md',
  className = '',
  children,
  ...rest
}) {
  const classes = `${BASE} ${VARIANTS[variant] || VARIANTS.primary} ${SIZES[size] || SIZES.md} ${className}`.trim();

  if (href) {
    return (
      <a href={href} className={classes} {...rest}>
        {children}
      </a>
    );
  }
  return (
    <button type="button" className={classes} {...rest}>
      {children}
    </button>
  );
}
