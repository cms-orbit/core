/**
 * Type surface for the plain-JS filter query helpers.
 *
 * The implementation stays `.mjs` because `inline-filter-query.test.mjs` runs
 * it directly under node without a build step. This declaration is what lets
 * the `.tsx` callers type-check against it instead of silently degrading to
 * `any` (TS7016).
 *
 * `normalizeFilterParamName` returns its argument unchanged when falsy, which
 * is why callers write `normalizeFilterParamName(x) ?? x`.
 */
export declare function normalizeFilterParamName(
    name: string | null | undefined,
): string | null | undefined;

export declare function uniqueFilterValues(values: readonly string[]): string[];

export declare function readFilterValuesFromQuery(
    name: string | null | undefined,
    search: string,
): string[];

export declare function readInlineFilterFromSearch(
    name: string | null | undefined,
    pageUrl?: string,
): string[];

export declare function readLegacyIndexedFilterParams(
    params: URLSearchParams,
    name: string,
): string[];
