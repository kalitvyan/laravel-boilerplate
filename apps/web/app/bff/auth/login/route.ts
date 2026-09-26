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

  const { data, error, response } = await api.POST("/api/v1/auth/login", { body: payload });

  if (error || !data) {
    return problemResponse(toApiFailure(error, response.status));
  }

  // Токены остаются на сервере: браузер получает только зашифрованный cookie
  await writeSession(toSessionTokens(data));

  return NextResponse.json({ userId: data.userId });
}

function isCredentials(value: unknown): value is { email: string; password: string } {
  return (
    typeof value === "object" &&
    value !== null &&
    typeof (value as { email?: unknown }).email === "string" &&
    typeof (value as { password?: unknown }).password === "string"
  );
}
