import { useId } from 'react';

/**
 * Labelled search box using the existing `.input` class. The label is always
 * present (visually hidden by default) so the control has an accessible name;
 * `type="search"` gives the native clear affordance and semantics.
 */
export default function SearchInput({
  value,
  onChange,
  label,
  placeholder,
  labelHidden = true,
  className = '',
}) {
  const id = useId();

  return (
    <div className={className}>
      <label htmlFor={id} className={labelHidden ? 'sr-only' : 't-label mb-1.5 block'}>
        {label}
      </label>
      <input
        id={id}
        type="search"
        className="input"
        value={value}
        placeholder={placeholder}
        autoComplete="off"
        onChange={(e) => onChange(e.target.value)}
      />
    </div>
  );
}
