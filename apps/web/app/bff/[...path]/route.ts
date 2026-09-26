import { NextResponse } from "next/server";
import { applySession, buildClientResponse, proxyToApi, unauthorized } from "@/lib/proxy";
import { readSession } from "@/lib/session";

export const dynamic = "force-dynamic";

async function handle(request: Request, context: { params: Promise<{ path: string[] }> }) {
  const session = await readSession();

  if (!session) {
    return unauthorized();
  }

  const { path } = await context.params;
  const { response, session: updated } = await proxyToApi(request, path.join("/"), session);

  // Сессия не менялась — лишнее шифрование и Set-Cookie ни к чему
  if (updated !== session) {
    await applySession(updated);
  }

  await applySession(updated);

  return buildClientResponse(response);
}

export const GET = handle;
export const POST = handle;
export const PATCH = handle;
export const PUT = handle;
export const DELETE = handle;
