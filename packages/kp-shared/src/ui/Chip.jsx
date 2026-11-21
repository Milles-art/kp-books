export default function Chip({ className = '', children, onClick, ...props }) {
  if (onClick) {
    return <button className={`chip ${className}`.trim()} onClick={onClick} {...props}>{children}</button>;
  }
  return <span className={`chip ${className}`.trim()} {...props}>{children}</span>;
}
