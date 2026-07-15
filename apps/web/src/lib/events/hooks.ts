"use client";

import { useMutation, useQuery } from "@tanstack/react-query";
import { cancelEventRegistration, getEvent, getEvents, registerForEvent, type EventsQuery } from "./api";

export const useEvents = (params: EventsQuery) =>
  useQuery({ queryKey: ["events", params], queryFn: () => getEvents(params) });

export const useEvent = (publicId: string) =>
  useQuery({ queryKey: ["event", publicId], queryFn: () => getEvent(publicId), enabled: Boolean(publicId) });

export const useRegisterForEvent = () =>
  useMutation({ mutationFn: (publicId: string) => registerForEvent(publicId) });

export const useCancelEventRegistration = () =>
  useMutation({ mutationFn: (publicId: string) => cancelEventRegistration(publicId) });
