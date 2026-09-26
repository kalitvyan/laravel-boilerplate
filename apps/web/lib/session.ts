import { cookies } from "next/headers";
import { EncryptJWT, jwtDecrypt } from "jose";
import { env, isProduction } from "@/lib/env";

export interface Session {
  userId: string;
  accessToken: string;
  /** Unix ms: когда истекает access-токен */
  accessExpiresAt: number;
  refreshToken: string;
  /** Unix ms: когда истекает refresh-токен, он же срок жизни cookie */
  refreshExpiresAt: number;
}

const ALG = "dir";
const ENC = "A256GCM";

/** A256GCM требует ровно 32 байта ключа */
const key = new TextEncoder().encode(env.SESSION_SECRET).slice(0, 32);

export async function encryptSession(session: Session): Promise<string> {
  return new EncryptJWT({ ...session })
    .setProtectedHeader({ alg: ALG, enc: ENC })
    .setIssuedAt()
    .setExpirationTime(new Date(session.refreshExpiresAt))
    .encrypt(key);
}

export async function decryptSession(value: string): Promise<Session | null> {
  try {
    const { payload } = await jwtDecrypt(value, key);

    return toSession(payload);
  } catch {
    // Истёкший, подделанный или зашифрованный другим ключом cookie — просто нет сессии
    return null;
  }
}

export async function readSession(): Promise<Session | null> {
  const cookie = (await cookies()).get(env.SESSION_COOKIE_NAME);

  return cookie ? decryptSession(cookie.value) : null;
}

export async function writeSession(session: Session): Promise<void> {
  const store = await cookies();

  store.set(env.SESSION_COOKIE_NAME, await encryptSession(session), {
    httpOnly: true,
    secure: isProduction,
    sameSite: "lax",
    path: "/",
    expires: new Date(session.refreshExpiresAt),
  });
}

export async function clearSession(): Promise<void> {
  (await cookies()).delete(env.SESSION_COOKIE_NAME);
}

function toSession(payload: Record<string, unknown>): Session | null {
  const { userId, accessToken, accessExpiresAt, refreshToken, refreshExpiresAt } = payload;

  if (
    typeof userId !== "string" ||
    typeof accessToken !== "string" ||
    typeof accessExpiresAt !== "number" ||
    typeof refreshToken !== "string" ||
    typeof refreshExpiresAt !== "number"
  ) {
    return null;
  }

  return { userId, accessToken, accessExpiresAt, refreshToken, refreshExpiresAt };
}
