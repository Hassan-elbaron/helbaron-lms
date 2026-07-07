<?php

namespace App\Domains\Notifications\Listeners;

use App\Domains\Certification\Events\CertificateIssued;
use App\Domains\Commerce\Events\OrderPaid;
use App\Domains\Crm\Events\ConsultingRequestCreated;
use App\Platform\Identity\Events\UserRegistered;
use App\Domains\Learning\Events\CourseCompleted;
use App\Domains\Learning\Events\UserEnrolled;
use App\Domains\Live\Events\SessionScheduled;
use App\Domains\Notifications\Enums\NotificationCategory;
use App\Domains\Notifications\Services\NotificationDispatcher;
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
        $this->dispatcher->dispatch($event->user, NotificationCategory::Account, 'welcome', ['name' => $event->user->name]);
    }

    public function onUserEnrolled(UserEnrolled $event): void
    {
        if ($event->enrollment->user) {
            $this->dispatcher->dispatch($event->enrollment->user, NotificationCategory::Learning, 'enrollment_confirmed', []);
        }
    }

    public function onCourseCompleted(CourseCompleted $event): void
    {
        if ($event->enrollment->user) {
            $this->dispatcher->dispatch($event->enrollment->user, NotificationCategory::Learning, 'course_completed', []);
        }
    }

    public function onOrderPaid(OrderPaid $event): void
    {
        if ($event->order->user) {
            $this->dispatcher->dispatch($event->order->user, NotificationCategory::Commerce, 'order_receipt', ['total' => $event->order->total_minor]);
        }
    }

    public function onCertificateIssued(CertificateIssued $event): void
    {
        if ($event->certificate->user) {
            $this->dispatcher->dispatch($event->certificate->user, NotificationCategory::Certification, 'certificate_ready', ['number' => $event->certificate->number]);
        }
    }

    public function onSessionScheduled(SessionScheduled $event): void
    {
        // Announce to registered participants.
        foreach ($event->session->registrations()->where('status', 'registered')->with('user')->get() as $registration) {
            if ($registration->user) {
                $this->dispatcher->dispatch($registration->user, NotificationCategory::Live, 'session_scheduled', ['title' => $event->session->title]);
            }
        }
    }

    public function onConsultingRequestCreated(ConsultingRequestCreated $event): void
    {
        if ($event->request->requester) {
            $this->dispatcher->dispatch($event->request->requester, NotificationCategory::Crm, 'consulting_ack', ['subject' => $event->request->subject]);
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
