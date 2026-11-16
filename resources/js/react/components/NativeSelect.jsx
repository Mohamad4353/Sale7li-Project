import { useId } from 'react';

/**
 * A real <label> + native <select>. Native = full keyboard support and the
 * platform's own RTL handling. `options` is `[{ value, label }]`; the caller
 * decides whether "" (all / any) is one of them.
 */
export default function NativeSelect({ options = [], value, onChange, label, className = '' }) {
  const id = useId();

  return (
    <div className={className}>
      <label htmlFor={id} className="t-label mb-1.5 block">
        {label}
      </label>
      <select id={id} className="input" value={value} onChange={(e) => onChange(e.target.value)}>
        {options.map((o) => (
          <option key={o.value} value={o.value}>
            {o.label}
          </option>
        ))}
      </select>
    </div>
  );
}
