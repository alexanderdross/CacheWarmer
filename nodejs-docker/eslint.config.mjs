import nextConfig from "eslint-config-next";

const config = [
  ...nextConfig,
  {
    ignores: ["node_modules/", ".next/", "coverage/"],
  },
  {
    files: ["src/**/*.{ts,tsx}"],
    rules: {
      "react-hooks/set-state-in-effect": "off",
      "react-hooks/error-boundaries": "off",
    },
  },
  {
    files: ["tests/**/*.ts"],
    rules: {
      "@next/next/no-assign-module-variable": "off",
    },
  },
];

export default config;
