<script lang="ts">
    import AppLogoIcon from './app-logo-icon.svelte';
</script>

<!-- No fragment needed: a Svelte component can have several root elements. -->
<div class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
    <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
</div>
<div class="ml-1 grid flex-1 text-left text-sm">
    <span class="mb-0.5 truncate font-semibold">Ping CRM</span>
</div>
