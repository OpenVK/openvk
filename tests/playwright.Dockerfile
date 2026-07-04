FROM mcr.microsoft.com/playwright:v1.60.0

RUN apt-get update -qq \
 && DEBIAN_FRONTEND=noninteractive apt-get install -y -qq \
      default-mysql-client \
      curl \
      fonts-noto-color-emoji \
 && rm -rf /var/lib/apt/lists/*
