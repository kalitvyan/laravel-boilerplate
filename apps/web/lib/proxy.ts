import { NextResponse } from "next/server";
import { env } from "@/lib/env";
import { clearSession, writeSession, type Session } from "@/lib/session";
import { needsRefresh, refreshSession } from "@/lib/tokens";

/** Заголовки, которые нельзя пробрасывать: их выставляет сам fetch или они принадлежат BFF */
const STRIPPED_REQUEST_HEADERS = new Set([
  "host",
  "connection",
  "content-length",
  "cookie",
  "authorization",
  "accept-encoding",
]);

const STRIPPED_RESPONSE_HEADERS = new Set([
  "content-encoding",
  "content-length",
  "transfer-encoding",
  "connection",
]);

export interface ProxyResult {
  response: Response;
  /** Сессия после возможного обновления; null — её нужно сбросить */
  session: Session | null;
}

export async function proxyToApi(request: Request, path: string, session: Session): Promise<ProxyResult> {
  let current: Session | null = session;

  if (needsRefresh(session)) {
    current = await refreshSession(session);

    if (!current) {
      return { response: unauthorized(), session: null };
    }
  }

  // Тело читаем один раз: для повтора после 401 нужен буфер
  const body = request.method === "GET" || request.method === "HEAD" ? undefined : await request.arrayBuffer();

  let response = await forward(request, path, current, body);

  // Токен мог истечь между проверкой и запросом, либо был отозван
  if (response.status === 401) {
    const refreshed = await refreshSession(current);

    if (!refreshed) {
      return { response: unauthorized(), session: null };
    }

    current = refreshed;
    response = await forward(request, path, current, body);
  }

  return { response, session: current };
}

async function forward(
  request: Request,
  path: string,
  session: Session,
  body: ArrayBuffer | undefined,
): Promise<Response> {
  const incoming = new URL(request.url);
  const target = new URL(`/api/v1/${path}`, env.API_URL);
  target.search = incoming.search;

  const headers = new Headers();

  for (const [name, value] of request.headers) {
    if (!STRIPPED_REQUEST_HEADERS.has(name.toLowerCase())) {
      headers.set(name, value);
    }
  }

  headers.set("authorization", `Bearer ${session.accessToken}`);
  headers.set("accept", "application/json");

  return fetch(target, {
    method: request.method,
    headers,
    body,
    redirect: "manual",
    cache: "no-store",
  });
}

export function buildClientResponse(upstream: Response): NextResponse {
  const headers = new Headers();

  for (const [name, value] of upstream.headers) {
    if (!STRIPPED_RESPONSE_HEADERS.has(name.toLowerCase())) {
      headers.set(name, value);
    }
  }

  return new NextResponse(upstream.body, { status: upstream.status, headers });
}

export async function applySession(session: Session | null): Promise<void> {
  if (session) {
    await writeSession(session);
  } else {
    await clearSession();
  }
}

export function unauthorized(): NextResponse {
  return NextResponse.json(
    {
      type: "about:blank",
      title: "Unauthenticated",
      status: 401,
      code: "unauthenticated",
    },
    { status: 401, headers: { "content-type": "application/problem+json" } },
  );
}
