// Placeholder cover until real cover images are uploaded.
export default function BookCover({ book }) {
  if (book.cover_url) return <img className="cover" src={book.cover_url} alt={book.title} />;
  const hue = [...book.slug].reduce((h, c) => (h * 31 + c.charCodeAt(0)) % 360, 7);
  return (
    <div className="cover" style={{ '--h': hue }}>
      <b>{book.title}</b>
      <small>KP Book</small>
    </div>
  );
}
