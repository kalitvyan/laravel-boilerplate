import type { NextConfig } from "next";

const config: NextConfig = {
  // Docker-образ на этапе сборки: копируется только нужное, без node_modules целиком
  output: "standalone",
  // Монорепо: трейсинг файлов должен начинаться от корня воркспейса
  outputFileTracingRoot: new URL("../../", import.meta.url).pathname,
  reactStrictMode: true,
  poweredByHeader: false,
  eslint: {
    ignoreDuringBuilds: true,
  },
};

export default config;
