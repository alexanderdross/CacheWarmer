import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // "standalone" for Docker self-hosting; remove for Vercel
  output: process.env.STANDALONE === "true" ? "standalone" : undefined,
  serverExternalPackages: ["better-sqlite3", "puppeteer-core", "pino", "bullmq"],
};

export default nextConfig;
