export default function Spinner({ label = 'Loading…', className = 'muted' }) {
  return <p className={className}>{label}</p>;
}
