import { NextResponse, type NextRequest } from "next/server";

const SESSION_COOKIE = process.env.SESSION_COOKIE_NAME ?? "lb_session";

const PUBLIC_PATHS = ["/login", "/register"];

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // CSRF: браузер всегда шлёт Origin для небезопасных методов.
  // Сравниваем с Host из этого же запроса, а не с nextUrl.origin:
  // за прокси и внутри docker-сети они не совпадают
  if (!["GET", "HEAD", "OPTIONS"].includes(request.method)) {
    const origin = request.headers.get("origin");

    if (origin !== null) {
      const host = request.headers.get("x-forwarded-host") ?? request.headers.get("host");

      if (host === null || new URL(origin).host !== host) {
        return NextResponse.json({ code: "forbidden", status: 403 }, { status: 403 });
      }
    }
  }

  if (PUBLIC_PATHS.some((path) => pathname.startsWith(path)) || pathname.startsWith("/bff/auth/")) {
    return NextResponse.next();
  }

  // Наличие cookie, а не его валидность: расшифровка — работа Route Handler'а
  const hasSession = request.cookies.has(SESSION_COOKIE);

  if (!hasSession && pathname !== "/") {
    const url = new URL("/login", request.url);
    url.searchParams.set("next", pathname);

    return NextResponse.redirect(url);
  }

  return NextResponse.next();
}

export const config = {
  matcher: ["/((?!_next/static|_next/image|favicon.ico).*)"],
};
