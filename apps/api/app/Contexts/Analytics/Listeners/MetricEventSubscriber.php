<?php

namespace App\Contexts\Analytics\Listeners;

use App\Contexts\Analytics\Services\MetricRollupService;
use App\Contexts\Commerce\Events\OrderPaid;
use App\Contexts\Learning\Events\CourseCompleted;
use App\Contexts\Learning\Events\UserEnrolled;
use App\Domains\Certification\Events\CertificateIssued;
use App\Domains\Crm\Events\ConsultingRequestCreated;
use App\Domains\Live\Events\SessionCompleted;
use App\Platform\Identity\Events\UserRegistered;
use Illuminate\Events\Dispatcher;

/**
 * The analytics consumer. Subscribes to producer-domain EVENTS ONLY (never their models/tables)
 * and maintains the metric_snapshots read model. This is the sole write-path into analytics.
 */
class MetricEventSubscriber
{
    public function __construct(private readonly MetricRollupService $rollup) {}

    public function onUserRegistered(UserRegistered $event): void
    {
        $this->rollup->increment('signups');
    }

    public function onUserEnrolled(UserEnrolled $event): void
    {
        $this->rollup->increment('enrollments');
    }

    public function onCourseCompleted(CourseCompleted $event): void
    {
        $this->rollup->increment('completions');
    }

    public function onOrderPaid(OrderPaid $event): void
    {
        $this->rollup->increment('orders_paid');
        $this->rollup->increment('revenue', (int) $event->order->total_minor);
    }

    public function onCertificateIssued(CertificateIssued $event): void
    {
        $this->rollup->increment('certificates_issued');
    }

    public function onSessionCompleted(SessionCompleted $event): void
    {
        $this->rollup->increment('live_sessions_completed');
    }

    public function onConsultingRequestCreated(ConsultingRequestCreated $event): void
    {
        $this->rollup->increment('consulting_requests');
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            UserRegistered::class => 'onUserRegistered',
            UserEnrolled::class => 'onUserEnrolled',
            CourseCompleted::class => 'onCourseCompleted',
            OrderPaid::class => 'onOrderPaid',
            CertificateIssued::class => 'onCertificateIssued',
            SessionCompleted::class => 'onSessionCompleted',
            ConsultingRequestCreated::class => 'onConsultingRequestCreated',
        ];
    }
}
