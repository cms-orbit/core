/**
 * The package renders inside a host application and consumes a few host-owned
 * modules. Those resolve through the host's own Vite/tsconfig aliases, so they
 * do not exist when the package type-checks itself. Declare the surface the
 * package actually uses so `tsc --noEmit` can run standalone.
 *
 * Keep these declarations as narrow as what the package calls — a wider shape
 * would let real misuse through.
 */
declare module '@/routes/orbit' {
    export const logout: { url: () => string };
    export const profile: { url: () => string };
}
