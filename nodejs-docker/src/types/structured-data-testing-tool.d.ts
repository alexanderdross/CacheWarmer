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

  /**
   * Validate HTML that has already been fetched.
   *
   * Preferred over structuredDataTest(url): passing a URL makes the library
   * fetch the page twice — once to check the body is non-empty and again
   * inside structuredDataTestUrl. It also skips structuredDataTestString's
   * JSON.parse guess, which would mis-wrap a document that happens to parse
   * as JSON.
   */
  export function structuredDataTestHtml(
    html: string,
    options?: TestOptions & { url?: string }
  ): Promise<TestResult>;
}
