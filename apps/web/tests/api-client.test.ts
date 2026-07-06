import { afterEach, describe, expect, it, vi } from "vitest";
import { apiFetch, ApiRequestError, setToken } from "@/lib/api/client";

function mockFetch(status: number, body: unknown) {
  return vi.fn().mockResolvedValue({
    ok: status >= 200 && status < 300,
    status,
    statusText: "",
    json: async () => body,
  });
}

afterEach(() => {
  vi.restoreAllMocks();
  setToken(null);
});

describe("apiFetch", () => {
  it("returns the parsed success envelope", async () => {
    vi.stubGlobal("fetch", mockFetch(200, { data: { id: "1" } }));
    const res = await apiFetch<{ data: { id: string } }>("courses", { auth: false });
    expect(res.data.id).toBe("1");
  });

  it("throws a typed ApiRequestError on the standard error envelope", async () => {
    vi.stubGlobal(
      "fetch",
      mockFetch(422, { error: { code: "VALIDATION", message: "Invalid", correlation_id: "c1", timestamp: "t" } }),
    );
    await expect(apiFetch("courses", { auth: false })).rejects.toMatchObject({
      name: "ApiRequestError",
      status: 422,
      code: "VALIDATION",
    });
  });

  it("attaches the bearer token when present", async () => {
    const fetchMock = mockFetch(200, { data: null });
    vi.stubGlobal("fetch", fetchMock);
    setToken("tok123");
    await apiFetch("profile");
    const headers = fetchMock.mock.calls[0][1].headers as Record<string, string>;
    expect(headers.Authorization).toBe("Bearer tok123");
  });
});

describe("ApiRequestError", () => {
  it("carries status and code", () => {
    const e = new ApiRequestError(404, "NOT_FOUND", "Missing");
    expect(e.status).toBe(404);
    expect(e.code).toBe("NOT_FOUND");
  });
});
