# syntax=docker/dockerfile:1
#checkov:skip=CKV_DOCKER_2
#checkov:skip=CKV_DOCKER_3
FROM alpine

LABEL org.opencontainers.image.title=SolidInvoice
LABEL org.opencontainers.image.description="Simple and elegant invoicing solution"
LABEL org.opencontainers.image.url=https://solidinvoice.co
LABEL org.opencontainers.image.source=https://github.com/SolidInvoice/SolidInvoice
LABEL org.opencontainers.image.licenses=MIT
LABEL org.opencontainers.image.vendor="SolidWorx"

ARG AUGIAS_VERSION=''
ENV AUGIAS_VERSION=${AUGIAS_VERSION}
ENV AUGIAS_ENV=prod
ENV AUGIAS_DEBUG=0
ENV AUGIAS_CONFIG_DIR=/etc/augias
ENV AUGIAS_DOCKER=true

EXPOSE 8765

VOLUME ["/etc/augias"]

COPY augias /usr/local/bin/augias

ENTRYPOINT ["/usr/local/bin/augias"]

CMD ["run", "--disable-https"]
