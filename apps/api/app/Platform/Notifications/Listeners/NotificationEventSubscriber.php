<?php

namespace App\Platform\Notifications\Listeners;

use App\Contexts\Commerce\Events\OrderPaid;
use App\Contexts\Learning\Events\CourseCompleted;
use App\Contexts\Learning\Events\UserEnrolled;
use App\Domains\Certification\Events\CertificateIssued;
use App\Domains\Crm\Events\ConsultingRequestCreated;
use App\Domains\Live\Events\SessionScheduled;
use App\Platform\Identity\Events\UserRegistered;
use App\Platform\Notifications\Enums\NotificationCategory;
use App\Platform\Notifications\Services\NotificationDispatcher;
use Illuminate\Events\Dispatcher;

/**
 * The notifications consumer. Reacts to producer EVENTS ONLY (reading the user off each event's
 * aggregate) and dispatches queued notifications. It never imports producer models/tables.
 */
class NotificationEventSubscriber
{
    public function __construct(private readonly NotificationDispatcher $dispatcher) {}

    public function onUserRegistered(UserRegistered $event): void
    {
        $this->dispatcher->dispatchToUserId($event->user->id, NotificationCategory::Account, 'welcome', ['name' => $event->user->name]);
    }

    public function onUserEnrolled(UserEnrolled $event): void
    {
        if ($event->enrollment->user_id !== null) {
            $this->dispatcher->dispatchToUserId($event->enrollment->user_id, NotificationCategory::Learning, 'enrollment_confirmed', []);
        }
    }

    public function onCourseCompleted(CourseCompleted $event): void
    {
        if ($event->enrollment->user_id !== null) {
            $this->dispatcher->dispatchToUserId($event->enrollment->user_id, NotificationCategory::Learning, 'course_completed', []);
        }
    }

    public function onOrderPaid(OrderPaid $event): void
    {
        if ($event->order->user_id !== null) {
            $this->dispatcher->dispatchToUserId($event->order->user_id, NotificationCategory::Commerce, 'order_receipt', ['total' => $event->order->total_minor]);
        }
    }

    public function onCertificateIssued(CertificateIssued $event): void
    {
        if ($event->certificate->user_id !== null) {
            $this->dispatcher->dispatchToUserId($event->certificate->user_id, NotificationCategory::Certification, 'certificate_ready', ['number' => $event->certificate->number]);
        }
    }

    public function onSessionScheduled(SessionScheduled $event): void
    {
        // Announce to registered participants.
        foreach ($event->session->registrations()->where('status', 'registered')->get() as $registration) {
            if ($registration->user_id !== null) {
                $this->dispatcher->dispatchToUserId($registration->user_id, NotificationCategory::Live, 'session_scheduled', ['title' => $event->session->title]);
            }
        }
    }

    public function onConsultingRequestCreated(ConsultingRequestCreated $event): void
    {
        if ($event->request->requested_by !== null) {
            $this->dispatcher->dispatchToUserId($event->request->requested_by, NotificationCategory::Crm, 'consulting_ack', ['subject' => $event->request->subject]);
        }
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            UserRegistered::class => 'onUserRegistered',
            UserEnrolled::class => 'onUserEnrolled',
            CourseCompleted::class => 'onCourseCompleted',
            OrderPaid::class => 'onOrderPaid',
            CertificateIssued::class => 'onCertificateIssued',
            SessionScheduled::class => 'onSessionScheduled',
            ConsultingRequestCreated::class => 'onConsultingRequestCreated',
        ];
    }
}
