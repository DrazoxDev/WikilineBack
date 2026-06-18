import { defineConfig, loadEnv } from "vite";
import react from '@vitejs/plugin-react';
import symfonyPlugin from "vite-plugin-symfony";

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '')
    const BASE = env.VITE_BASE_URL || '/'
	return {
		base: BASE + "build/",
		plugins: [
			react(),
			symfonyPlugin(),
		],
		build: {
			outDir: "public/build",
			emptyOutDir: true,
			rollupOptions: {
				input: {
					app: "./assets/app.jsx"
				},
			}
		},
	}
});
