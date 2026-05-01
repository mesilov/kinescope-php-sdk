FROM node:24-alpine

ARG OPENSPEC_VERSION=1.3.1

RUN npm install -g "@fission-ai/openspec@${OPENSPEC_VERSION}" \
    && npm cache clean --force

WORKDIR /workspace

ENTRYPOINT ["openspec"]
