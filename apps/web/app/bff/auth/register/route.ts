import { NextResponse } from "next/server";
import { api, toSessionTokens } from "@/lib/api";
import { problemResponse, toApiFailure } from "@/lib/problem";
import { writeSession } from "@/lib/session";

export const dynamic = "force-dynamic";

export async function POST(request: Request) {
  const payload = await request.json().catch(() => null);

  if (!isCredentials(payload)) {
    return problemResponse({
      code: "validation_failed",
      message: "Email and password are required",
      status: 422,
      fieldErrors: [],
    });
  }

  const registered = await api.POST("/api/v1/users", { body: payload });

  if (registered.error || !registered.data) {
    return problemResponse(toApiFailure(registered.error, registered.response.status));
  }

  const loggedIn = await api.POST("/api/v1/auth/login", { body: payload });

  if (loggedIn.error || !loggedIn.data) {
    // Аккаунт создан, но автоматический вход не удался: пусть залогинится вручную
    return NextResponse.json({ userId: registered.data.id, requiresLogin: true }, { status: 201 });
  }

  // Токены остаются на сервере: браузер получает только зашифрованный cookie
  await writeSession(toSessionTokens(loggedIn.data));

  return NextResponse.json({ userId: registered.data.id }, { status: 201 });
}

function isCredentials(value: unknown): value is { email: string; password: string } {
  return (
    typeof value === "object" &&
    value !== null &&
    typeof (value as { email?: unknown }).email === "string" &&
    typeof (value as { password?: unknown }).password === "string"
  );
}
