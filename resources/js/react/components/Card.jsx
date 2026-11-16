/**
 * The SALE7LI marketplace surface card — mirrors the Step 4 Blade
 * <x-worker-card> treatment: rounded-2xl, hairline border, flat at rest,
 * `shadow-raised` + `border-line-strong` on hover, no lift. `interactive`
 * opts a card into that hover feedback.
 */
export default function Card({ as: Tag = 'div', interactive = false, className = '', children, ...rest }) {
  const hover = interactive ? ' transition hover:border-line-strong hover:shadow-raised' : '';
  return (
    <Tag
      className={`rounded-2xl border border-line bg-surface p-5${hover} ${className}`.trim()}
      {...rest}
    >
      {children}
    </Tag>
  );
}
