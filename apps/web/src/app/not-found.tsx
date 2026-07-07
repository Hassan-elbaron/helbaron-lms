import Link from "next/link";

export default function NotFound() {
  return (
    <div className="flex min-h-dvh flex-col items-center justify-center gap-4 p-8 text-center">
      <p className="font-serif text-2xl font-semibold">Page not found</p>
      <p className="text-sm text-muted-foreground">The page you are looking for does not exist.</p>
      <div className="flex gap-3">
        <Link href="/" className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground">Home</Link>
        <Link href="/courses" className="rounded-md border px-4 py-2 text-sm font-medium">Browse courses</Link>
      </div>
    </div>
  );
}