import { NextRequest, NextResponse } from "next/server";
import { getConfig } from "@/lib/config";

export function authenticateRequest(request: NextRequest): NextResponse | null {
  const config = getConfig();
  const apiKey = config.server.apiKey;

  if (!apiKey || apiKey === "change-me-in-production") {
    return null; // No auth configured, allow all
  }

  const authHeader = request.headers.get("authorization");
  if (!authHeader || !authHeader.startsWith("Bearer ")) {
    return NextResponse.json({ error: "Missing or invalid Authorization header" }, { status: 401 });
  }

  const token = authHeader.slice(7);
  if (token !== apiKey) {
    return NextResponse.json({ error: "Invalid API key" }, { status: 403 });
  }

  return null; // Auth OK
}
