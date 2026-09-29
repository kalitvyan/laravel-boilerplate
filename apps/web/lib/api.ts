import { Agent, setGlobalDispatcher } from "undici";
import { createApiClient, type Session as ApiSession } from "@laravel-boilerplate/api-client";
import { env } from "@/lib/env";

let client: ReturnType<typeof createApiClient> | null = null;

/**
 * Клиент создаётся при первом вызове, а не на импорте модуля:
 * иначе Next обращается к переменным окружения на этапе сборки.
 */
export function api() {
  if (!client) {
    // Переиспользуем соединения до API: иначе TCP-хендшейк на каждый запрос
    setGlobalDispatcher(new Agent({ keepAliveTimeout: 30_000, connections: 64 }));

    client = createApiClient({ baseUrl: env.API_URL });
  }

  return client;
}

export interface SessionTokens {
  userId: string;
  accessToken: string;
  accessExpiresAt: number;
  refreshToken: string;
  refreshExpiresAt: number;
}

export function toSessionTokens(payload: ApiSession): SessionTokens {
  return {
    userId: payload.userId,
    accessToken: payload.accessToken,
    accessExpiresAt: Date.parse(payload.accessTokenExpiresAt),
    refreshToken: payload.refreshToken,
    refreshExpiresAt: Date.parse(payload.refreshTokenExpiresAt),
  };
}
