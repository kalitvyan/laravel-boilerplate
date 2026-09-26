import { describe, expect, it } from "vitest";
import { decryptSession, encryptSession, type Session } from "@/lib/session";

const session: Session = {
  userId: "01a0dc53-68c0-7356-9795-f0838f9f91c5",
  accessToken: "plain-access-token",
  accessExpiresAt: Date.now() + 900_000,
  refreshToken: "plain-refresh-token",
  refreshExpiresAt: Date.now() + 2_592_000_000,
};

describe("session cookie", () => {
  it("round-trips through encryption", async () => {
    const restored = await decryptSession(await encryptSession(session));

    expect(restored).toEqual(session);
  });

  it("does not expose tokens in the cookie value", async () => {
    const encrypted = await encryptSession(session);

    expect(encrypted).not.toContain("plain-access-token");
    expect(encrypted).not.toContain("plain-refresh-token");
  });

  it("rejects a tampered cookie", async () => {
    const encrypted = await encryptSession(session);

    expect(await decryptSession(`${encrypted}x`)).toBeNull();
  });
});
