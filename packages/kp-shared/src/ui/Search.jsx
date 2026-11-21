export default function Search({ value, onChange, placeholder = 'Search', className = 'search', ...props }) {
  return (
    <input
      className={className}
      type="search"
      placeholder={placeholder}
      value={value}
      onChange={onChange}
      aria-label={placeholder}
      {...props}
    />
  );
}
