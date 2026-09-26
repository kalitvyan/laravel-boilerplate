import { describe, expect, it } from "vitest";
import { needsRefresh } from "@/lib/tokens";
import type { Session } from "@/lib/session";

const at = (accessExpiresAt: number): Session => ({
  userId: "u",
  accessToken: "a",
  accessExpiresAt,
  refreshToken: "r",
  refreshExpiresAt: accessExpiresAt + 1_000_000,
});

describe("needsRefresh", () => {
  const now = 1_700_000_000_000;

  it("refreshes ahead of expiry", () => {
    // Токен ещё жив 30 секунд, но запрос может не успеть
    expect(needsRefresh(at(now + 30_000), now)).toBe(true);
  });

  it("leaves a fresh token alone", () => {
    expect(needsRefresh(at(now + 600_000), now)).toBe(false);
  });

  it("refreshes an expired token", () => {
    expect(needsRefresh(at(now - 1), now)).toBe(true);
  });
});
