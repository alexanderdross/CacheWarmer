import { NextRequest, NextResponse } from "next/server";
import crypto from "crypto";
import { getConfig } from "@/lib/config";
import logger from "@/lib/logger";

export function authenticateRequest(request: NextRequest): NextResponse | null {
  const config = getConfig();
  const apiKey = config.server.apiKey;

  if (!apiKey || apiKey === "change-me-in-production") {
    logger.warn("API authentication is disabled — no API key configured or using default key");
    return null; // No auth configured, allow all
  }

  const authHeader = request.headers.get("authorization");
  if (!authHeader || !authHeader.startsWith("Bearer ")) {
    return NextResponse.json({ error: "Missing or invalid Authorization header" }, { status: 401 });
  }

  const token = authHeader.slice(7);
  const tokenBuffer = Buffer.from(token);
  const apiKeyBuffer = Buffer.from(apiKey);
  if (tokenBuffer.length !== apiKeyBuffer.length || !crypto.timingSafeEqual(tokenBuffer, apiKeyBuffer)) {
    return NextResponse.json({ error: "Invalid API key" }, { status: 403 });
  }

  return null; // Auth OK
}
