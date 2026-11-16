import { useId } from 'react';

/**
 * A real <label> + <select> for the marketplace category filter. Native select
 * = full keyboard support and the platform's own RTL handling. Options are the
 * already-localised category names passed from Blade; "" is "All categories".
 */
export default function CategorySelect({ categories = [], value, onChange, label, allLabel, className = '' }) {
  const id = useId();

  return (
    <div className={className}>
      <label htmlFor={id} className="t-label mb-1.5 block">
        {label}
      </label>
      <select
        id={id}
        className="input"
        value={value}
        onChange={(e) => onChange(e.target.value)}
      >
        <option value="">{allLabel}</option>
        {categories.map((c) => (
          <option key={c.slug} value={c.slug}>
            {c.name}
          </option>
        ))}
      </select>
    </div>
  );
}
