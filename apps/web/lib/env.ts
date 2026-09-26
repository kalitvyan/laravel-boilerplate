import { z } from "zod";

/**
 * Валидируется при старте: отсутствующий SESSION_SECRET должен ронять приложение,
 * а не всплывать 500-й на первом логине.
 */
const schema = z.object({
  API_URL: z.url(),
  SESSION_SECRET: z.string().min(32, "SESSION_SECRET must be at least 32 characters"),
  SESSION_COOKIE_NAME: z.string().default("lb_session"),
  NODE_ENV: z.enum(["development", "production", "test"]).default("development"),
});

const parsed = schema.safeParse({
  API_URL: process.env.API_URL,
  SESSION_SECRET: process.env.SESSION_SECRET,
  SESSION_COOKIE_NAME: process.env.SESSION_COOKIE_NAME,
  NODE_ENV: process.env.NODE_ENV,
});

if (!parsed.success) {
  throw new Error(`Invalid environment: ${z.prettifyError(parsed.error)}`);
}

export const env = parsed.data;
export const isProduction = env.NODE_ENV === "production";
