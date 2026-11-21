export default function EmptyState({ children, className = 'muted' }) {
  return <p className={className}>{children}</p>;
}
