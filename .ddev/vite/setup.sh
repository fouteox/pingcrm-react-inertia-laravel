#!/bin/bash
#ddev-generated
CONFIG_FILE="${DDEV_APPROOT}/vite.config.js"

sed_inplace() {
  if [[ "$(uname)" == "Darwin" ]]; then
    sed -i '' "$@"
  else
    sed -i "$@"
  fi
}

if [ -f "$CONFIG_FILE" ]; then
  sed_inplace "/export default defineConfig/ i\\
const port = 5173;\\
const origin = \`\${process.env.DDEV_PRIMARY_URL}:\${port}\`;\\
\\
" "$CONFIG_FILE"

  sed_inplace "s/export default defineConfig({/export default defineConfig({\\
    server: {\\
        host: '0.0.0.0',\\
        port: port,\\
        strictPort: true,\\
        origin: origin,\\
        cors: {\\
            origin: { origin: process.env.DDEV_PRIMARY_URL },\\
        },\\
    },/g" "$CONFIG_FILE"
fi
