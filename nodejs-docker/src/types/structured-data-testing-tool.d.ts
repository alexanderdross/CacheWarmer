declare module "structured-data-testing-tool" {
  interface TestResult {
    passed: Array<{
      schema?: string;
      test?: string;
      description?: string;
      message?: string;
    }>;
    failed: Array<{
      schema?: string;
      test?: string;
      description?: string;
      message?: string;
    }>;
    warnings: Array<{
      schema?: string;
      test?: string;
      description?: string;
      message?: string;
    }>;
    schemas: Array<{
      schema: string;
    }>;
  }

  interface TestOptions {
    presets?: unknown[];
    schemas?: unknown[];
  }

  export function structuredDataTest(
    url: string,
    options?: TestOptions
  ): Promise<TestResult>;
}
