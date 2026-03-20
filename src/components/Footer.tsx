export default function Footer() {
  return (
    <footer className="py-8 text-center">
      <p className="text-sm text-muted">
        &copy; {new Date().getFullYear()} Christian Lanzaderas. All rights reserved.
      </p>
      <p className="text-xs text-muted/60 mt-1">
        Built with Next.js & Tailwind CSS
      </p>
    </footer>
  );
}