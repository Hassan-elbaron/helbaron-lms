"use client";

export default function RouteError({ error, reset }: { error: Error & { digest?: string }; reset: () => void }) {
  return (
    <div className="flex min-h-[40vh] w-full flex-col items-center justify-center gap-4 p-8 text-center">
      <p className="font-medium">Something went wrong.</p>
      <p className="max-w-md text-sm text-muted-foreground">{error?.message ?? "Unexpected error."}</p>
      <button onClick={reset} className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
        Try again
      </button>
    </div>
  );
}