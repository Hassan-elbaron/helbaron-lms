"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { Award, BookOpenCheck, Clock, Layers, ShoppingCart } from "lucide-react";
import type { CoursePurchase } from "@/lib/catalog/api";
import { errorMessage, isEmailVerificationRequired } from "@/lib/api/errors";
import { useAuth } from "@/lib/auth/auth-context";
import { useAddToCart } from "@/lib/commerce/hooks";
import { useEnroll } from "@/lib/catalog/hooks";
import { accessLabel, certificateLabel, coursePurchasePrice } from "@/lib/commerce/sales-format";
import { useI18n } from "@/lib/i18n/i18n-context";
import { Button } from "@/components/ui/button";
import { PriceTag } from "@/components/commerce/price-tag";
import { toast } from "@/components/ui/toast";

/**
 * The course action panel mirrors the backend entitlement rule, which has THREE outcomes:
 * an active product uses checkout; no product of any status means free enrolment; a product that
 * exists but is draft or archived means the course is not available yet — neither buyable nor free.
 *
 * The third case is why this panel branches on `purchase.free` and not on `!purchase.purchasable`.
 * Deriving freeness from the absence of an ACTIVE product put a one-click "Enroll for free" button
 * on every paid course for as long as an admin had its product parked in Draft, and the grant it
 * produced was a lifetime one. The API refuses it with 402 now; the button must not appear either.
 *
 * A guest is sent to sign-in with a redirect back to this course, so the intent survives the round
 * trip and the buy button is still waiting on return.
 */
export function CoursePurchasePanel({
  courseId,
  purchase,
  compact = false,
}: {
  courseId: string;
  purchase: CoursePurchase | null | undefined;
  compact?: boolean;
}) {
  const { t, locale } = useI18n();
  const router = useRouter();
  const { status, user } = useAuth();
  const add = useAddToCart();
  const enroll = useEnroll();
  const authed = status === "authenticated";

  const sellable = purchase?.purchasable === true ? purchase : null;
  // Only an explicit `free: true` authorises the payment-free path. An absent summary is treated as
  // not-free, which fails closed rather than giving a course away on a payload we cannot read.
  const free = purchase?.free === true;
  const price = coursePurchasePrice(purchase, locale);

  const requireAccount = () => {
    if (!authed) {
      router.push(`/login?redirect=/courses/${courseId}`);
      return false;
    }
    if (user?.email_verified === false) {
      router.push(`/verify-email?redirect=${encodeURIComponent(`/courses/${courseId}`)}`);
      return false;
    }
    return true;
  };

  /*
   * The client-side check above reads a CACHED profile, so it goes stale: a session that verified
   * (or was un-verified) elsewhere still carries the old flag. When that happens the server is the
   * one that refuses, with 403 EMAIL_VERIFICATION_REQUIRED — a code nothing on the client used to
   * read, so it surfaced as a generic "something went wrong" toast with no way forward.
   *
   * Route it to the page that actually resolves it, carrying the course as the return target so the
   * learner lands back on the button they pressed.
   */
  const onActionError = (e: unknown) => {
    if (isEmailVerificationRequired(e)) {
      router.push(`/verify-email?redirect=${encodeURIComponent(`/courses/${courseId}`)}`);
      return;
    }
    toast.error(errorMessage(e, t("common.error")));
  };

  // Sold, but not on sale right now (draft or archived product). Not buyable, and emphatically not
  // free — this is the state the previous `!sellable` branch silently handed out for nothing.
  if (!sellable && !free) {
    return (
      <div className={compact ? "" : "space-y-2"}>
        <Button className="w-full" size={compact ? "default" : "lg"} disabled>
          {t("catalog.course.notAvailable")}
        </Button>
        {compact ? null : (
          <p className="px-1 text-center text-xs text-muted-foreground">
            {t("catalog.course.notAvailableHint")}
          </p>
        )}
      </div>
    );
  }

  // Nothing sells this course at any status — the API-supported free-enrollment path.
  if (!sellable) {
    const onEnroll = () => {
      if (!requireAccount()) return;
      enroll.mutate(courseId, {
        onSuccess: () => {
          toast.success(t("catalog.course.enrolled"));
          router.push(`/learn/${courseId}`);
        },
        onError: onActionError,
      });
    };

    return (
      <div className={compact ? "" : "space-y-2"}>
        <Button className="w-full" size={compact ? "default" : "lg"} loading={enroll.isPending} onClick={onEnroll}>
          <BookOpenCheck className="size-4" aria-hidden />
          {authed ? t("catalog.course.enrollFree") : t("catalog.course.signInToEnroll")}
        </Button>
        {compact ? null : (
          <p className="px-1 text-center text-xs text-muted-foreground">
            {t("catalog.course.freeHint")}
          </p>
        )}
      </div>
    );
  }

  const onAdd = () => {
    if (!requireAccount()) return;
    add.mutate(
      { product: sellable.product_id },
      {
        onSuccess: () =>
          toast.success(t("catalog.course.addedToCart"), {
            action: { label: t("catalog.course.goToCart"), onClick: () => router.push("/cart") },
          }),
        onError: onActionError,
      },
    );
  };

  const cta = (
    <Button
      className="w-full shine relative overflow-hidden"
      size={compact ? "default" : "lg"}
      loading={add.isPending}
      onClick={onAdd}
    >
      <ShoppingCart className="size-4" aria-hidden />
      {authed ? t("catalog.course.addToCart") : t("catalog.course.signInToBuy")}
    </Button>
  );

  // The mobile sticky bar only has room for the price and the button.
  if (compact) {
    return (
      <div className="flex items-center gap-3">
        <PriceTag price={price} size="sm" />
        <div className="ms-auto">{cta}</div>
      </div>
    );
  }

  const access = accessLabel(sellable.access, locale);
  const certificate = certificateLabel(sellable.certificate, locale);

  return (
    <div className="space-y-4">
      <PriceTag price={price} size="lg" />
      {cta}

      <ul className="space-y-2 text-sm text-muted-foreground">
        {access ? (
          <li className="flex items-center gap-2">
            <Clock className="size-4 text-copper" aria-hidden />
            {access}
          </li>
        ) : null}
        {certificate ? (
          <li className="flex items-center gap-2">
            <Award className="size-4 text-copper" aria-hidden />
            {certificate}
          </li>
        ) : null}
        {sellable.included_in_bundles.length > 0 ? (
          <li className="flex items-center gap-2">
            <Layers className="size-4 text-copper" aria-hidden />
            <Link href="/bundles" className="underline underline-offset-4 hover:text-foreground">
              {t("catalog.course.alsoInBundles")}
            </Link>
          </li>
        ) : null}
      </ul>
    </div>
  );
}
