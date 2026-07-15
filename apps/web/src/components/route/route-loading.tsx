import { Spinner } from "@/components/ui/spinner";

export default function RouteLoading() {
  return (
    <div className="flex min-h-[40vh] w-full items-center justify-center" role="status" aria-live="polite">
      <Spinner size="md" label="Loading" className="text-primary" />
    </div>
  );
}
