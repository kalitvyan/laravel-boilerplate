import { toApiFailure, type ApiFailure } from "@/lib/problem";

export class ApiError extends Error {
  constructor(public readonly failure: ApiFailure) {
    super(failure.message);
    this.name = "ApiError";
  }
}

export async function bff<T>(path: string, init: RequestInit = {}): Promise<T> {
  const response = await fetch(`/bff/${path.replace(/^\//, "")}`, {
    ...init,
    headers: {
      accept: "application/json",
      ...(init.body ? { "content-type": "application/json" } : {}),
      ...init.headers,
    },
    // Cookie обязателен: без него BFF не найдёт сессию
    credentials: "same-origin",
  });

  if (response.status === 204) {
    return undefined as T;
  }

  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    throw new ApiError(toApiFailure(payload, response.status));
  }

  return payload as T;
}
