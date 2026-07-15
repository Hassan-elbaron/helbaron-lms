"use client";

import Link from "next/link";
import { CalendarClock, Clock, MapPin, Users } from "lucide-react";
import { toast } from "sonner";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useAuth } from "@/lib/auth/auth-context";
import { errorMessage } from "@/lib/api/errors";
import { useRegisterForEvent } from "@/lib/events/hooks";
import type { EventDetail, EventStatus } from "@/lib/events/api";
import { formatEventDateTime } from "@/lib/events/format";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";

function statusVariant(status: EventStatus): "default" | "secondary" | "outline" | "destructive" {
  switch (status) {
    case "live":
      return "destructive";
    case "completed":
      return "secondary";
    case "cancelled":
      return "outline";
    default:
      return "default";
  }
}

function initials(name: string): string {
  return name
    .split(" ")
    .map((p) => p[0])
    .filter(Boolean)
    .slice(0, 2)
    .join("")
    .toUpperCase();
}

function RegisterCta({ event }: { event: EventDetail }) {
  const { t } = useI18n();
  const { status } = useAuth();
  const register = useRegisterForEvent();

  const closed = event.status === "cancelled" || event.status === "completed";

  if (closed) {
    return (
      <p className="text-sm text-muted-foreground" role="status">
        {event.status === "cancelled" ? t("events.register.cancelled") : t("events.register.ended")}
      </p>
    );
  }

  if (status !== "authenticated") {
    return (
      <Button asChild className="w-full">
        <Link href={`/login?redirect=/events/${event.id}`}>{t("events.register.signIn")}</Link>
      </Button>
    );
  }

  const onRegister = () => {
    register.mutate(event.id, {
      onSuccess: (res) => {
        if (res.status === "waitlisted") toast.success(t("events.register.waitlisted"));
        else toast.success(t("events.register.success"));
      },
      onError: (e) => toast.error(errorMessage(e, t("common.error"))),
    });
  };

  return (
    <div className="space-y-2">
      <Button className="w-full" loading={register.isPending} onClick={onRegister}>
        {event.is_full ? t("events.register.joinWaitlist") : t("events.register.cta")}
      </Button>
      {register.isSuccess ? (
        <p className="text-center text-xs text-muted-foreground" role="status" aria-live="polite">
          {register.data?.status === "waitlisted" ? t("events.register.waitlisted") : t("events.register.success")}
        </p>
      ) : null}
    </div>
  );
}

export function EventDetailsClient({ event }: { event: EventDetail }) {
  const { t, locale } = useI18n();
  const when = formatEventDateTime(event.starts_at, event.timezone, locale);
  const ends = formatEventDateTime(event.ends_at, event.timezone, locale);

  return (
    <div>
      {/* Hero */}
      <section className="mb-8 overflow-hidden rounded-3xl border bg-[radial-gradient(130%_150%_at_100%_0%,oklch(0.985_0.012_88)_0%,var(--card)_58%)] p-8 sm:p-10">
        <div className="mb-3 flex flex-wrap items-center gap-3">
          <Badge variant={statusVariant(event.status)}>{t(`events.status.${event.status}`)}</Badge>
          <span className="inline-flex items-center gap-1.5 text-sm text-muted-foreground">
            <MapPin className="size-4" aria-hidden /> {t("events.online")}
          </span>
        </div>
        <h1 className="font-serif text-3xl font-semibold leading-[1.1] tracking-tight sm:text-4xl">{event.title}</h1>
        {when ? (
          <p className="mt-4 inline-flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
            <span className="inline-flex items-center gap-1.5">
              <CalendarClock className="size-4" aria-hidden /> {when}
              {ends ? ` – ${ends}` : ""}
            </span>
            <span className="inline-flex items-center gap-1.5">
              <Clock className="size-4" aria-hidden /> {event.timezone}
            </span>
          </p>
        ) : null}
      </section>

      <div className="grid gap-8 lg:grid-cols-[1.8fr_1fr]">
        <div className="space-y-8">
          {/* About / agenda */}
          <section>
            <h2 className="mb-3 text-xl font-semibold">{t("events.agenda")}</h2>
            {event.description ? (
              <p className="whitespace-pre-line text-muted-foreground">{event.description}</p>
            ) : (
              <p className="text-muted-foreground">{t("events.noAgenda")}</p>
            )}
            <ul className="mt-4 space-y-3">
              {event.agenda.map((item, i) => (
                <li key={i} className="rounded-lg border bg-card p-4">
                  <p className="font-medium">{item.title}</p>
                  {formatEventDateTime(item.starts_at, event.timezone, locale) ? (
                    <p className="text-sm text-muted-foreground">
                      {formatEventDateTime(item.starts_at, event.timezone, locale)}
                    </p>
                  ) : null}
                </li>
              ))}
            </ul>
          </section>

          {/* Speakers */}
          {event.speakers.length > 0 ? (
            <section>
              <h2 className="mb-3 text-xl font-semibold">{t("events.speakers")}</h2>
              <div className="grid gap-4 sm:grid-cols-2">
                {event.speakers.map((s, i) => (
                  <Card key={i}>
                    <CardContent className="flex items-center gap-3 p-4">
                      <Avatar>
                        {s.avatar_path ? <AvatarImage src={s.avatar_path} alt={s.name} /> : null}
                        <AvatarFallback>{initials(s.name)}</AvatarFallback>
                      </Avatar>
                      <div className="min-w-0">
                        <p className="truncate font-medium">{s.name}</p>
                        {s.headline ? <p className="truncate text-sm text-muted-foreground">{s.headline}</p> : null}
                      </div>
                    </CardContent>
                  </Card>
                ))}
              </div>
            </section>
          ) : null}

          {/* Related course */}
          {event.related_course ? (
            <section>
              <Separator className="my-2" />
              <h2 className="mb-3 text-xl font-semibold">{t("events.relatedCourses")}</h2>
              <Link
                href={`/courses/${event.related_course.public_id}`}
                className="inline-flex items-center gap-2 rounded-lg border bg-card px-4 py-3 text-sm font-medium transition-colors hover:bg-accent"
              >
                {event.related_course.title}
              </Link>
            </section>
          ) : null}
        </div>

        {/* Register aside */}
        <aside className="lg:col-span-1">
          <Card className="sticky top-20">
            <CardContent className="space-y-4 p-5">
              <div className="space-y-1">
                <p className="inline-flex items-center gap-1.5 text-sm text-muted-foreground">
                  <Users className="size-4" aria-hidden />
                  {event.capacity != null
                    ? `${event.registered_count}/${event.capacity} ${t("events.registeredLabel")}`
                    : `${event.registered_count} ${t("events.registeredLabel")}`}
                </p>
                {event.waitlist_count > 0 ? (
                  <p className="text-xs text-muted-foreground">
                    {event.waitlist_count} {t("events.waitlistLabel")}
                  </p>
                ) : null}
              </div>
              <RegisterCta event={event} />
            </CardContent>
          </Card>
        </aside>
      </div>
    </div>
  );
}
