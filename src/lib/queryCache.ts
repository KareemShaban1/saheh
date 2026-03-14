import type { QueryKey } from "@tanstack/react-query";

export const QUERY_CACHE_DEFAULTS = {
  staleTime: 60 * 1000,
  gcTime: 10 * 60 * 1000,
  refetchOnWindowFocus: false,
  refetchOnReconnect: true,
  retry: 1,
} as const;

export function withCachedQuery<T extends { queryKey: QueryKey; queryFn: () => Promise<unknown> }>(
  options: T,
): T & typeof QUERY_CACHE_DEFAULTS {
  return {
    ...QUERY_CACHE_DEFAULTS,
    ...options,
  };
}

export function withCachedFetch<TData>(
  queryKey: QueryKey,
  queryFn: () => Promise<TData>,
  overrides?: Partial<Pick<typeof QUERY_CACHE_DEFAULTS, "staleTime" | "gcTime">>,
): { queryKey: QueryKey; queryFn: () => Promise<TData>; staleTime: number; gcTime: number } {
  return {
    queryKey,
    queryFn,
    staleTime: overrides?.staleTime ?? QUERY_CACHE_DEFAULTS.staleTime,
    gcTime: overrides?.gcTime ?? QUERY_CACHE_DEFAULTS.gcTime,
  };
}
