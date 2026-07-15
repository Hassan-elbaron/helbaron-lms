"use client";

import { useMemo, useState, type ReactNode } from "react";
import { ArrowDown, ArrowUp, ChevronsUpDown, Columns3, X } from "lucide-react";
import { cn } from "@/lib/utils";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
  type TableDensity,
} from "./table";
import { Skeleton } from "./skeleton";
import { Checkbox } from "./checkbox";
import { Button } from "./button";
import { Popover, PopoverContent, PopoverTrigger } from "./popover";
import { EmptyState } from "@/components/states/empty-state";
import { ErrorState } from "@/components/states/error-state";
import { Pagination } from "./pagination";

export type SortDirection = "asc" | "desc";
export interface SortState {
  key: string;
  direction: SortDirection;
}

export interface ColumnDef<T> {
  key: string;
  header: ReactNode;
  cell: (row: T) => ReactNode;
  className?: string;
  /** Additive: header cell className (e.g. width utilities). */
  headerClassName?: string;
  /** Additive: logical text alignment for header + cells. */
  align?: "start" | "center" | "end";
  /** Additive: enable sorting on this column (requires `sortValue`). */
  sortable?: boolean;
  /** Additive: comparable value used for client-side sorting. */
  sortValue?: (row: T) => string | number;
  /** Additive: allow hiding this column via the visibility toggle. */
  hideable?: boolean;
  /** Additive: hidden until the user enables it. */
  defaultHidden?: boolean;
  /** Additive: pin this column to the inline start/end (e.g. an action column). */
  sticky?: "start" | "end";
}

export interface DataGridLabels {
  columns: string;
  selected: (n: number) => string;
  clear: string;
  selectAll: string;
  selectRow: string;
}

const DEFAULT_LABELS: DataGridLabels = {
  columns: "Columns",
  selected: (n) => `${n} selected`,
  clear: "Clear",
  selectAll: "Select all rows",
  selectRow: "Select row",
};

export interface DataGridProps<T> {
  columns: ColumnDef<T>[];
  data: T[];
  rowKey: (row: T) => string;
  loading?: boolean;
  empty?: ReactNode;
  onRowClick?: (row: T) => void;
  pagination?: { page: number; lastPage: number; onPageChange: (page: number) => void };
  className?: string;

  // ── Additive, opt-in enhancements (all default to previous behaviour) ──
  /** Row density (`comfortable` default | `compact`). */
  density?: TableDensity;
  /** Pin the header while the body scrolls; pair with `maxHeight`. */
  stickyHeader?: boolean;
  /** Constrains the scroll region (enables a scrolling body for sticky header). */
  maxHeight?: string | number;
  /** Error surface. When provided (with no loading), replaces the body with an error state. */
  error?: ReactNode;
  onRetry?: () => void;
  /** Controlled sort. Omit `onSortChange` for built-in client-side sorting. */
  sort?: SortState | null;
  onSortChange?: (sort: SortState | null) => void;
  /** Enable row selection + a bulk-action bar rendered above the table. */
  selectable?: boolean;
  bulkActions?: (ctx: { selectedRows: T[]; clear: () => void }) => ReactNode;
  onSelectionChange?: (selectedRows: T[]) => void;
  /** Show a column visibility toggle (uses each column's `hideable`). */
  columnToggle?: boolean;
  /** Arbitrary toolbar slot (filters, search, export button, …). */
  toolbar?: ReactNode;
  /** Below `md`, stack rows into cards instead of a horizontally-scrolling table. */
  responsiveCards?: boolean;
  /** Custom card renderer for the responsive fallback (defaults to a label/value list). */
  renderCard?: (row: T) => ReactNode;
  /** Override the default English micro-copy (labels/aria) for i18n. */
  labels?: Partial<DataGridLabels>;
}

function nextSort(current: SortState | null, key: string): SortState | null {
  if (!current || current.key !== key) return { key, direction: "asc" };
  if (current.direction === "asc") return { key, direction: "desc" };
  return null;
}

function alignClass(align?: "start" | "center" | "end") {
  if (align === "center") return "text-center";
  if (align === "end") return "text-end";
  return undefined;
}

function stickyClass(sticky?: "start" | "end") {
  if (sticky === "start") return "sticky start-0 z-[1] bg-card";
  if (sticky === "end") return "sticky end-0 z-[1] bg-card";
  return undefined;
}

/**
 * Standardized, token-driven data grid built on the Table primitives. Everything beyond the
 * original `columns/data/rowKey/loading/empty/onRowClick/pagination` API is additive and
 * opt-in: sorting (aria-sort), density, sticky header, sticky columns, selection + bulk bar,
 * column visibility, a toolbar slot, a responsive card fallback, and loading/empty/error states.
 */
export function DataGrid<T>({
  columns,
  data,
  rowKey,
  loading,
  empty,
  onRowClick,
  pagination,
  className,
  density,
  stickyHeader,
  maxHeight,
  error,
  onRetry,
  sort,
  onSortChange,
  selectable,
  bulkActions,
  onSelectionChange,
  columnToggle,
  toolbar,
  responsiveCards,
  renderCard,
  labels: labelOverrides,
}: DataGridProps<T>) {
  const labels: DataGridLabels = { ...DEFAULT_LABELS, ...labelOverrides };
  const controlledSort = onSortChange != null;
  const [uncontrolledSort, setUncontrolledSort] = useState<SortState | null>(null);
  const activeSort = controlledSort ? sort ?? null : uncontrolledSort;

  const [hidden, setHidden] = useState<Set<string>>(
    () => new Set(columns.filter((c) => c.defaultHidden).map((c) => c.key)),
  );
  const [selected, setSelected] = useState<Set<string>>(new Set());

  const visibleColumns = columns.filter((c) => !hidden.has(c.key));
  const hideableColumns = columns.filter((c) => c.hideable);

  const sortedData = useMemo(() => {
    if (controlledSort || !activeSort) return data;
    const col = columns.find((c) => c.key === activeSort.key);
    if (!col?.sortValue) return data;
    const getVal = col.sortValue;
    const dir = activeSort.direction === "asc" ? 1 : -1;
    return [...data].sort((a, b) => {
      const av = getVal(a);
      const bv = getVal(b);
      if (av < bv) return -1 * dir;
      if (av > bv) return 1 * dir;
      return 0;
    });
  }, [controlledSort, activeSort, columns, data]);

  function toggleSort(key: string) {
    const next = nextSort(activeSort, key);
    if (controlledSort) onSortChange!(next);
    else setUncontrolledSort(next);
  }

  function commitSelection(keys: Set<string>) {
    setSelected(keys);
    if (onSelectionChange) onSelectionChange(data.filter((r) => keys.has(rowKey(r))));
  }
  function toggleRow(row: T) {
    const key = rowKey(row);
    const next = new Set(selected);
    if (next.has(key)) next.delete(key);
    else next.add(key);
    commitSelection(next);
  }
  function toggleAll() {
    if (data.length > 0 && data.every((r) => selected.has(rowKey(r)))) commitSelection(new Set());
    else commitSelection(new Set(data.map(rowKey)));
  }
  function clearSelection() {
    commitSelection(new Set());
  }

  const allSelected = data.length > 0 && data.every((r) => selected.has(rowKey(r)));
  const someSelected = !allSelected && data.some((r) => selected.has(rowKey(r)));
  const selectedRows = data.filter((r) => selected.has(rowKey(r)));
  const totalCols = visibleColumns.length + (selectable ? 1 : 0);

  const showToolbar = Boolean(toolbar || (columnToggle && hideableColumns.length > 0));

  return (
    <div className={cn("space-y-4", className)}>
      {showToolbar && (
        <div className="flex flex-wrap items-center justify-between gap-2">
          <div className="min-w-0 flex-1">{toolbar}</div>
          {columnToggle && hideableColumns.length > 0 && (
            <Popover>
              <PopoverTrigger asChild>
                <Button variant="outline" size="sm">
                  <Columns3 className="size-4" aria-hidden />
                  {labels.columns}
                </Button>
              </PopoverTrigger>
              <PopoverContent align="end" className="w-52">
                <div className="space-y-1.5">
                  {hideableColumns.map((c) => {
                    const id = `col-vis-${c.key}`;
                    return (
                      <label key={c.key} htmlFor={id} className="flex items-center gap-2 text-sm">
                        <Checkbox
                          id={id}
                          checked={!hidden.has(c.key)}
                          onChange={() => {
                            const next = new Set(hidden);
                            if (next.has(c.key)) next.delete(c.key);
                            else next.add(c.key);
                            setHidden(next);
                          }}
                        />
                        <span className="truncate">{c.header}</span>
                      </label>
                    );
                  })}
                </div>
              </PopoverContent>
            </Popover>
          )}
        </div>
      )}

      {selectable && selectedRows.length > 0 && (
        <div
          role="region"
          aria-label={labels.selected(selectedRows.length)}
          className="motion-fade-in flex flex-wrap items-center justify-between gap-2 rounded-md border border-primary/30 bg-primary/5 px-3 py-2"
        >
          <span className="text-sm font-medium">{labels.selected(selectedRows.length)}</span>
          <div className="flex items-center gap-2">
            {bulkActions?.({ selectedRows, clear: clearSelection })}
            <Button variant="ghost" size="sm" onClick={clearSelection}>
              <X className="size-4" aria-hidden />
              {labels.clear}
            </Button>
          </div>
        </div>
      )}

      {/* Table view (hidden below md when responsiveCards is on) */}
      <div className={cn("rounded-md border", responsiveCards && "hidden md:block")}>
        <div
          className={stickyHeader ? "overflow-auto" : undefined}
          style={stickyHeader && maxHeight != null ? { maxHeight } : undefined}
        >
          <Table density={density}>
            <TableHeader sticky={stickyHeader}>
              <TableRow>
                {selectable && (
                  <TableHead className="w-10">
                    <Checkbox
                      aria-label={labels.selectAll}
                      checked={allSelected}
                      ref={(el) => {
                        if (el) el.indeterminate = someSelected;
                      }}
                      onChange={toggleAll}
                    />
                  </TableHead>
                )}
                {visibleColumns.map((c) => {
                  const isSorted = activeSort?.key === c.key;
                  const ariaSort = c.sortable
                    ? isSorted
                      ? activeSort!.direction === "asc"
                        ? "ascending"
                        : "descending"
                      : "none"
                    : undefined;
                  const SortIcon = !isSorted ? ChevronsUpDown : activeSort!.direction === "asc" ? ArrowUp : ArrowDown;
                  return (
                    <TableHead
                      key={c.key}
                      aria-sort={ariaSort}
                      className={cn(alignClass(c.align), stickyClass(c.sticky), c.headerClassName, c.className)}
                    >
                      {c.sortable ? (
                        <button
                          type="button"
                          onClick={() => toggleSort(c.key)}
                          className="inline-flex items-center gap-1.5 rounded-sm font-medium hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                          {c.header}
                          <SortIcon className={cn("size-3.5", !isSorted && "opacity-50")} aria-hidden />
                        </button>
                      ) : (
                        c.header
                      )}
                    </TableHead>
                  );
                })}
              </TableRow>
            </TableHeader>
            <TableBody>
              {loading ? (
                Array.from({ length: 5 }).map((_, i) => (
                  <TableRow key={`sk-${i}`}>
                    {selectable && (
                      <TableCell className="w-10">
                        <Skeleton className="size-4" />
                      </TableCell>
                    )}
                    {visibleColumns.map((c) => (
                      <TableCell key={c.key}>
                        <Skeleton className="h-4 w-full" />
                      </TableCell>
                    ))}
                  </TableRow>
                ))
              ) : error ? (
                <TableRow>
                  <TableCell colSpan={totalCols} className="h-40 p-0">
                    <ErrorState onRetry={onRetry} />
                  </TableCell>
                </TableRow>
              ) : sortedData.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={totalCols} className="h-32 p-0">
                    {empty ?? <EmptyState />}
                  </TableCell>
                </TableRow>
              ) : (
                sortedData.map((row) => {
                  const key = rowKey(row);
                  const isSelected = selected.has(key);
                  return (
                    <TableRow
                      key={key}
                      data-state={isSelected ? "selected" : undefined}
                      onClick={onRowClick ? () => onRowClick(row) : undefined}
                      className={onRowClick ? "cursor-pointer" : undefined}
                    >
                      {selectable && (
                        <TableCell className="w-10" onClick={(e) => e.stopPropagation()}>
                          <Checkbox
                            aria-label={labels.selectRow}
                            checked={isSelected}
                            onChange={() => toggleRow(row)}
                          />
                        </TableCell>
                      )}
                      {visibleColumns.map((c) => (
                        <TableCell key={c.key} className={cn(alignClass(c.align), stickyClass(c.sticky), c.className)}>
                          {c.cell(row)}
                        </TableCell>
                      ))}
                    </TableRow>
                  );
                })
              )}
            </TableBody>
          </Table>
        </div>
      </div>

      {/* Responsive card fallback (shown below md when responsiveCards is on) */}
      {responsiveCards && (
        <div className="space-y-3 md:hidden">
          {loading ? (
            Array.from({ length: 3 }).map((_, i) => <Skeleton key={`ck-${i}`} className="h-24 w-full rounded-md" />)
          ) : error ? (
            <ErrorState onRetry={onRetry} />
          ) : sortedData.length === 0 ? (
            empty ?? <EmptyState />
          ) : (
            sortedData.map((row) => {
              const key = rowKey(row);
              return (
                <div
                  key={key}
                  role={onRowClick ? "button" : undefined}
                  tabIndex={onRowClick ? 0 : undefined}
                  onClick={onRowClick ? () => onRowClick(row) : undefined}
                  onKeyDown={
                    onRowClick
                      ? (e) => {
                          if (e.key === "Enter" || e.key === " ") {
                            e.preventDefault();
                            onRowClick(row);
                          }
                        }
                      : undefined
                  }
                  className={cn(
                    "rounded-md border bg-card p-4",
                    onRowClick && "cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring",
                  )}
                >
                  {renderCard ? (
                    renderCard(row)
                  ) : (
                    <dl className="space-y-1.5">
                      {visibleColumns.map((c) => (
                        <div key={c.key} className="flex items-start justify-between gap-3 text-sm">
                          <dt className="text-muted-foreground">{c.header}</dt>
                          <dd className="min-w-0 text-end">{c.cell(row)}</dd>
                        </div>
                      ))}
                    </dl>
                  )}
                </div>
              );
            })
          )}
        </div>
      )}

      {pagination && pagination.lastPage > 1 ? (
        <Pagination page={pagination.page} lastPage={pagination.lastPage} onPageChange={pagination.onPageChange} />
      ) : null}
    </div>
  );
}
