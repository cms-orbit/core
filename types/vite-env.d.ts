/**
 * The package reads `import.meta.env?.DEV` to gate development-only warnings.
 * Vite injects `import.meta.env` in the host build; the optional chaining is
 * deliberate so the code also survives environments that do not. Declared here
 * rather than pulling in `vite/client` because the package is not bundled and
 * has no other reason to depend on Vite.
 */
interface ImportMetaEnv {
    readonly DEV?: boolean;
}

interface ImportMeta {
    readonly env?: ImportMetaEnv;
}
