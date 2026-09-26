import { api, toSessionTokens } from "@/lib/api";
import type { Session } from "@/lib/session";

/** Обновляемся заранее: иначе запрос уйдёт с токеном, истёкшим в пути */
const REFRESH_SKEW_MS = 60_000;

/**
 * Одновременные запросы в одном процессе Next должны использовать один refresh-токен:
 * он одноразовый, и параллельная ротация выглядела бы для API как утечка.
 * Между инстансами эту роль играет грейс-период на стороне API.
 */
const inFlight = new Map<string, Promise<Session | null>>();

export function needsRefresh(session: Session, now = Date.now()): boolean {
  return session.accessExpiresAt - now <= REFRESH_SKEW_MS;
}

export async function refreshSession(session: Session): Promise<Session | null> {
  const key = session.refreshToken;
  const existing = inFlight.get(key);

  if (existing) {
    return existing;
  }

  const promise = performRefresh(session).finally(() => {
    inFlight.delete(key);
  });

  inFlight.set(key, promise);

  return promise;
}

async function performRefresh(session: Session): Promise<Session | null> {
  const { data, error } = await api.POST("/api/v1/auth/refresh", {
    body: { refreshToken: session.refreshToken },
  });

  if (error || !data) {
    return null;
  }

  return toSessionTokens(data);
}
