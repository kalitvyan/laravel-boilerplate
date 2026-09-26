import { createApiClient, type Session as ApiSession } from "@laravel-boilerplate/api-client";
import { env } from "@/lib/env";
import { Agent, setGlobalDispatcher } from "undici";

// Переиспользуем соединения до API: иначе TCP-хендшейк на каждый запрос
setGlobalDispatcher(new Agent({ keepAliveTimeout: 30_000, connections: 64 }));

export const api = createApiClient({ baseUrl: env.API_URL });

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
