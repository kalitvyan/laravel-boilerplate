import createClient from "openapi-fetch";
import type { components, paths } from "./schema";

export type { paths, components };

export type Session = components["schemas"]["Session"];
export type User = components["schemas"]["User"];
export type ProblemDetails = components["schemas"]["Problem"];
export type ValidationProblem = components["schemas"]["ValidationProblem"];

export const PROBLEM_CONTENT_TYPE = "application/problem+json";

export interface ApiClientOptions {
  baseUrl: string;
  /** Переопределяется на сервере, чтобы прокинуть заголовки и таймауты */
  fetch?: typeof globalThis.fetch;
  headers?: Record<string, string>;
}

export function createApiClient({ baseUrl, fetch, headers }: ApiClientOptions) {
  return createClient<paths>({ baseUrl, fetch, headers });
}

export function isProblemDetails(value: unknown): value is ProblemDetails {
  return (
    typeof value === "object" &&
    value !== null &&
    "code" in value &&
    "status" in value &&
    typeof (value as ProblemDetails).code === "string"
  );
}

export function isValidationProblem(value: unknown): value is ValidationProblem {
  return isProblemDetails(value) && Array.isArray((value as ValidationProblem).errors);
}
