import { NextResponse } from "next/server";
import { api } from "@/lib/api";
import { clearSession, readSession } from "@/lib/session";

export const dynamic = "force-dynamic";

export async function POST() {
  const session = await readSession();

  if (session) {
    // Ошибку игнорируем: локальную сессию сбрасываем в любом случае
    await api
      .POST("/api/v1/auth/logout", {
        body: { refreshToken: session.refreshToken },
        headers: { authorization: `Bearer ${session.accessToken}` },
      })
      .catch(() => undefined);
  }

  await clearSession();

  return new NextResponse(null, { status: 204 });
}
