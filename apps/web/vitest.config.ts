import { defineConfig } from "vitest/config";
import tsconfigPaths from "vite-tsconfig-paths";

export default defineConfig({
  plugins: [tsconfigPaths()],
  test: {
    environment: "node",
    include: ["tests/**/*.test.ts"],
    env: {
      API_URL: "http://api.test",
      SESSION_SECRET: "test-secret-test-secret-test-sec",
    },
  },
});
