declare module "structured-data-testing-tool" {
  interface SdttTestResult {
    passed: boolean;
    optional?: boolean;
    type?: string;
    test: string;
    description?: string;
    error?: { type?: string; message: string };
  }
  interface SdttRunResult {
    passed: SdttTestResult[];
    failed: SdttTestResult[];
    warnings: SdttTestResult[];
  }
  export const presets: Record<string, unknown>;
  export function structuredDataTest(
    input: string,
    options?: { presets?: unknown[] }
  ): Promise<SdttRunResult>;
  const _default: {
    structuredDataTest: typeof structuredDataTest;
    presets: typeof presets;
  };
  export default _default;
}
