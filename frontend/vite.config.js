import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  return {
    plugins: [react()],
    // In dev, /api calls go to the Laravel server so no CORS setup is needed.
    server: { proxy: { '/api': env.VITE_API_URL || 'http://localhost:8000' } },
  };
});
