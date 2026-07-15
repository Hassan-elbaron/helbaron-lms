/**
 * Canonical state components — one source of truth for the app's status surfaces.
 *
 * Loading   → LoadingState / PageLoading (spinner + polite live region)
 * Skeleton  → Skeleton (+ variants: text/avatar/card/table-row) / SkeletonText
 * Empty     → EmptyState
 * Error     → ErrorState / ErrorBoundary
 * Success   → SuccessState
 * Offline   → OfflineBanner + useOnlineStatus
 * ComingSoon→ ComingSoon
 * Query     → QueryState (loading/error/empty/content for a TanStack query)
 *
 * The original module paths (states/*, student/query-state, ui/skeleton) remain valid
 * for back-compat; import from here for new code.
 */
export { LoadingState, PageLoading } from "./loading-state";
export { EmptyState, type EmptyStateProps } from "./empty-state";
export { ErrorState, type ErrorStateProps } from "./error-state";
export { ErrorBoundary } from "./error-boundary";
export { SuccessState, type SuccessStateProps } from "./success-state";
export { OfflineBanner, useOnlineStatus } from "./offline-banner";
export { ComingSoon } from "./coming-soon";
export { Skeleton, SkeletonText, type SkeletonProps } from "@/components/ui/skeleton";
export { QueryState, type QueryStateProps } from "@/components/student/query-state";
