// Backend configuration for the KP Book local/production server.
// The original app compiles its backend settings into the frontend JS bundle.
// These values mirror that configuration so the backend can serve the same routes.

module.exports = {
  port: process.env.PORT ? Number(process.env.PORT) : 4175,

  // Convex deployment the frontend connects to (baked into assets/index-BVLawKKe.js).
  convexUrl: "https://sleek-bat-361.convex.cloud",

  // Optional: forward /_serverFn/* to the live app instead of answering locally.
  // Enable with PROXY_SERVERFN=1 when you want live auth/session behaviour.
  proxyServerFns: process.env.PROXY_SERVERFN === "1",
  upstream: "https://kipanya-digital-books-552698.onhercules.app",

  // Frontend build output served by this server.
  siteDir: require("path").join(__dirname, "..", "site"),
};
