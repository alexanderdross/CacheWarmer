import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  output: "standalone",
  serverExternalPackages: ["better-sqlite3", "puppeteer-core", "pino", "bullmq"],
};

export default nextConfig;
