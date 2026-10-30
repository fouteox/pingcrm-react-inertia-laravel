import { vitePreprocess } from '@sveltejs/vite-plugin-svelte';

/** @type {import('@sveltejs/vite-plugin-svelte').SvelteConfig} */
export default {
    // Lets `<script lang="ts">` use TypeScript-only syntax inside .svelte files.
    preprocess: vitePreprocess(),
};
