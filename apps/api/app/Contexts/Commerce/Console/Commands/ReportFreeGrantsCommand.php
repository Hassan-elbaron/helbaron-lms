<?php

namespace App\Contexts\Commerce\Console\Commands;

use App\Platform\Shared\Commerce\Contracts\EntitlementPort;
use App\Platform\Shared\Commerce\Enums\EntitlementKind;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * READ-ONLY audit of payment-free enrolments that look like they should not exist.
 *
 * WHY THIS IS NEEDED. Two defects, both older than the branch that found them, wrote
 * `source = free, expires_at = NULL` — a PERPETUAL grant — onto courses that were being sold:
 *
 *   1. Freeness was inferred from the absence of an ACTIVE product, so any window in which an admin
 *      had moved a live product to Draft (to edit pricing, say) turned every course that product
 *      sells into a one-click free lifetime enrolment.
 *   2. The entitlement bypass re-recorded borrowed access as free. A subscriber or a seated employee
 *      who pressed "Enroll" received permanent access to the course, because access is decided by
 *      the enrollment row alone and nothing reclaims a `free` row — RevokeEnrollmentsOnRefund needs
 *      an order, and revokeCompanySeat() filters on `source = company_seat`.
 *
 * Both are fixed going forward. Rows already written are NOT, and this command is how you see them
 * before deciding anything.
 *
 * IT CHANGES NOTHING. No revocation, no migration, no writes of any kind. Deciding what to do about
 * a customer's existing access is a business call that needs the real numbers in front of it, and
 * those numbers cannot be guessed from the code.
 *
 * The `entitlement` column is what turns the list into a decision: a row whose subscription lapsed
 * months ago is a live problem (someone is using a course nobody is paying for), while a row whose
 * subscription is still running is only latent (it becomes a problem the day they stop paying).
 */
class ReportFreeGrantsCommand extends Command
{
    protected $signature = 'commerce:report-free-grants
        {--csv= : Also write the full report to this path as CSV}
        {--limit=50 : Rows to print to the console (the CSV always contains every row)}';

    protected $description = 'Read-only audit of free-source enrolments on courses that a product sells';

    public function handle(EntitlementPort $entitlements): int
    {
        $rows = $this->findSuspectGrants($entitlements);

        if ($rows === []) {
            $this->info('No free-source enrolments found on courses that any product sells.');

            return self::SUCCESS;
        }

        $lapsed = count(array_filter($rows, fn (array $r): bool => $r['status'] === 'LAPSED'));

        $this->warn(count($rows).' free-source enrolment(s) found on courses a product sells.');
        $this->warn($lapsed.' of them are held by someone with NO current entitlement.');
        $this->newLine();

        $limit = max(1, (int) $this->option('limit'));

        $this->table(
            ['Enrollment', 'User', 'Course', 'Granted', 'Expires', 'Product status', 'Entitlement', 'Status'],
            array_map(
                fn (array $r): array => [
                    $r['enrollment_id'], $r['user_email'], $r['course_public_id'], $r['enrolled_at'],
                    $r['expires_at'] ?? 'never', $r['product_statuses'], $r['entitlement'], $r['status'],
                ],
                array_slice($rows, 0, $limit),
            ),
        );

        if (count($rows) > $limit) {
            $this->line('... '.(count($rows) - $limit).' more. Use --csv to export every row.');
        }

        $this->writeCsv($rows);

        $this->newLine();
        $this->line('This report is READ-ONLY. Nothing has been revoked or modified.');

        return self::SUCCESS;
    }

    /**
     * Every `source = free` enrolment on a course that at least one product row grants.
     *
     * @return list<array<string, string|int|null>>
     */
    private function findSuspectGrants(EntitlementPort $entitlements): array
    {
        $records = DB::table('enrollments')
            ->join('users', 'users.id', '=', 'enrollments.user_id')
            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
            ->where('enrollments.source', 'free')
            ->whereExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('product_courses')
                    ->join('products', 'products.id', '=', 'product_courses.product_id')
                    ->whereColumn('product_courses.course_id', 'enrollments.course_id')
                    ->whereNull('products.deleted_at');
            })
            ->orderBy('enrollments.id')
            ->get([
                'enrollments.id as enrollment_id',
                'enrollments.user_id',
                'enrollments.course_id',
                'enrollments.enrolled_at',
                'enrollments.expires_at',
                'users.email as user_email',
                'courses.public_id as course_public_id',
                'courses.is_free',
            ]);

        $rows = [];

        foreach ($records as $record) {
            $entitlement = $entitlements->courseEntitlement((int) $record->user_id, (int) $record->course_id);

            $rows[] = [
                'enrollment_id' => (int) $record->enrollment_id,
                'user_email' => (string) $record->user_email,
                'course_public_id' => (string) $record->course_public_id,
                'course_declared_free' => $record->is_free ? 'yes' : 'no',
                'enrolled_at' => (string) $record->enrolled_at,
                'expires_at' => $record->expires_at === null ? null : (string) $record->expires_at,
                'product_statuses' => $this->productStatusesFor((int) $record->course_id),
                'entitlement' => $this->describeEntitlement($entitlement),
                // The column that makes this actionable. LAPSED means the learner holds perpetual
                // free access to a sold course with nothing currently entitling them to it.
                'status' => $entitlement === null ? 'LAPSED' : 'covered',
            ];
        }

        return $rows;
    }

    private function describeEntitlement(mixed $entitlement): string
    {
        if ($entitlement === null) {
            return 'none';
        }

        $kind = $entitlement->kind instanceof EntitlementKind ? $entitlement->kind->value : 'unknown';

        return $entitlement->expiresAt === null ? $kind : $kind.' until '.$entitlement->expiresAt;
    }

    /** The distinct statuses of every product granting the course, e.g. "active" or "draft,archived". */
    private function productStatusesFor(int $courseId): string
    {
        $statuses = DB::table('product_courses')
            ->join('products', 'products.id', '=', 'product_courses.product_id')
            ->where('product_courses.course_id', $courseId)
            ->whereNull('products.deleted_at')
            ->distinct()
            ->pluck('products.status')
            ->map(fn ($s): string => (string) $s)
            ->sort()
            ->values()
            ->all();

        return $statuses === [] ? '-' : implode(',', $statuses);
    }

    /** @param  list<array<string, string|int|null>>  $rows */
    private function writeCsv(array $rows): void
    {
        $path = $this->option('csv');

        if (! is_string($path) || trim($path) === '') {
            return;
        }

        $handle = fopen($path, 'wb');

        if ($handle === false) {
            $this->error("Could not open {$path} for writing.");

            return;
        }

        fputcsv($handle, array_keys($rows[0]));

        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn ($v): string => (string) ($v ?? ''), $row));
        }

        fclose($handle);

        $this->info('Wrote '.count($rows).' row(s) to '.$path);
    }
}
