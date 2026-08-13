import { defineConfig } from "vitest/config";
import path from "path";

export default defineConfig({
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "src"),
    },
  },
  test: {
    globals: true,
    environment: "node",
    include: ["tests/**/*.test.ts"],
    coverage: {
      provider: "v8",
      reporter: ["text", "html"],
      include: ["src/lib/**/*.ts"],
      // Without these, coverage was reported and enforced nothing — it could
      // fall to zero and CI would stay green. Set just under the measured
      // 73.95 / 63.41 / 68.14 / 74.89 so they catch erosion without turning
      // every harmless refactor red. Raise them when coverage genuinely rises.
      thresholds: {
        statements: 70,
        branches: 60,
        functions: 65,
        lines: 70,
      },
    },
    testTimeout: 30000,
    setupFiles: ["tests/setup.ts"],
  },
});
