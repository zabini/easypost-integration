const { createProxyMiddleware } = require('http-proxy-middleware');

const target = process.env.API_PROXY_TARGET || 'http://localhost:3031';

module.exports = function setupProxy(app) {
  app.use(
    ['/auth', '/sanctum'],
    createProxyMiddleware({
      changeOrigin: true,
      secure: false,
      target,
    })
  );
};
