export function cleanupMobileNavigation(): void {
    // Remove pointer-events style from body...
    document.body.style.removeProperty('pointer-events');
}
