# syntax=docker/dockerfile:1

ARG NODE_VERSION=24

FROM node:${NODE_VERSION}-alpine AS base

ENV PNPM_HOME=/pnpm \
    PATH=/pnpm:$PATH \
    COREPACK_ENABLE_DOWNLOAD_PROMPT=0 \
    NEXT_TELEMETRY_DISABLED=1

WORKDIR /repo

RUN corepack enable

# ---------------------------------------------------------------- deps

FROM base AS deps

# Только манифесты: слой зависимостей меняется реже, чем код
COPY pnpm-lock.yaml pnpm-workspace.yaml package.json ./
COPY apps/web/package.json apps/web/
COPY packages/api-client/package.json packages/api-client/

RUN --mount=type=cache,target=/pnpm/store \
    pnpm install --frozen-lockfile

# ---------------------------------------------------------------- build

FROM base AS build

COPY --from=deps /repo/node_modules ./node_modules
COPY --from=deps /repo/apps/web/node_modules ./apps/web/node_modules
COPY --from=deps /repo/packages/api-client/node_modules ./packages/api-client/node_modules

COPY packages/api-client packages/api-client
COPY apps/web apps/web
COPY pnpm-lock.yaml pnpm-workspace.yaml package.json ./

# Клиент генерируется из бандла спеки: он не в git, поэтому приезжает контекстом сборки
COPY docs/api/dist/openapi.yaml docs/api/dist/openapi.yaml
RUN pnpm --filter @laravel-boilerplate/api-client generate

RUN pnpm --filter @laravel-boilerplate/web build

# ---------------------------------------------------------------- prod

FROM node:${NODE_VERSION}-alpine AS prod

ENV NODE_ENV=production \
    NEXT_TELEMETRY_DISABLED=1 \
    PORT=3000 \
    HOSTNAME=0.0.0.0

WORKDIR /app

RUN addgroup -g 10001 app && adduser -u 10001 -G app -s /sbin/nologin -D app

# standalone содержит сервер и только те зависимости, которые реально используются
COPY --from=build --chown=10001:10001 /repo/apps/web/.next/standalone ./
COPY --from=build --chown=10001:10001 /repo/apps/web/.next/static ./apps/web/.next/static
COPY --from=build --chown=10001:10001 /repo/apps/web/public ./apps/web/public

USER 10001:10001

EXPOSE 3000

HEALTHCHECK --interval=10s --timeout=3s --start-period=15s --retries=3 \
  CMD node -e "fetch('http://127.0.0.1:3000/login').then(r => process.exit(r.ok ? 0 : 1)).catch(() => process.exit(1))"

CMD ["node", "apps/web/server.js"]
