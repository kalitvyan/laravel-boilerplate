import { z } from "zod";

const schema = z.object({
  API_URL: z.url(),
  SESSION_SECRET: z.string().min(32, "SESSION_SECRET must be at least 32 characters"),
  SESSION_COOKIE_NAME: z.string().default("lb_session"),
  NODE_ENV: z.enum(["development", "production", "test"]).default("development"),
});

type Env = z.infer<typeof schema>;

let cached: Env | null = null;

/**
 * Валидация ленивая: на этапе сборки переменных рантайма ещё нет,
 * а падать приложение должно при первом обращении, а не при импорте модуля.
 */
function load(): Env {
  if (cached) {
    return cached;
  }

  const parsed = schema.safeParse({
    API_URL: process.env.API_URL,
    SESSION_SECRET: process.env.SESSION_SECRET,
    SESSION_COOKIE_NAME: process.env.SESSION_COOKIE_NAME,
    NODE_ENV: process.env.NODE_ENV,
  });

  if (!parsed.success) {
    throw new Error(`Invalid environment: ${z.prettifyError(parsed.error)}`);
  }

  cached = parsed.data;

  return cached;
}

export const env = new Proxy({} as Env, {
  get: (_target, property: string) => load()[property as keyof Env],
});

export function isProduction(): boolean {
  return load().NODE_ENV === "production";
}
