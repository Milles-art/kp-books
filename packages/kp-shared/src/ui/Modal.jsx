export default function Modal({ open, onClose, title, children, labelledBy }) {
  if (!open) return null;
  return (
    <div className="ov" onClick={(e) => e.target === e.currentTarget && onClose && onClose()}>
      <div className="modal" role="dialog" aria-modal="true" aria-labelledby={labelledBy}>
        {title && <div className="mh"><h2>{title}</h2>{onClose && <button className="x" onClick={onClose} aria-label="Close">×</button>}</div>}
        {children}
      </div>
    </div>
  );
}
