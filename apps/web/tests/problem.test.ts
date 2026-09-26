import { describe, expect, it } from "vitest";
import { toApiFailure } from "@/lib/problem";

describe("toApiFailure", () => {
  it("maps validation pointers to field names", () => {
    const failure = toApiFailure({
      type: "about:blank",
      title: "Validation failed",
      status: 422,
      code: "validation_failed",
      errors: [{ pointer: "/email", code: "email", message: "Invalid email" }],
    });

    expect(failure.fieldErrors).toEqual([{ field: "email", message: "Invalid email" }]);
  });

  it("keeps the problem code for non-validation errors", () => {
    const failure = toApiFailure({
      type: "about:blank",
      title: "Invalid credentials",
      status: 401,
      code: "identity.invalid_credentials",
      detail: "Invalid email or password",
    });

    expect(failure).toMatchObject({
      code: "identity.invalid_credentials",
      message: "Invalid email or password",
      status: 401,
      fieldErrors: [],
    });
  });

  it("falls back on anything that is not a problem document", () => {
    expect(toApiFailure("<html>502</html>", 502)).toMatchObject({ code: "internal_error", status: 502 });
  });
});
