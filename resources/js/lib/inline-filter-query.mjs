/** @typedef {string | null | undefined} MaybeName */

export function normalizeFilterParamName(name) {
    if (!name) {
        return name;
    }

    return name.endsWith('[]') ? name.slice(0, -2) : name;
}

export function uniqueFilterValues(values) {
    return [...new Set(values.filter(Boolean))];
}

function searchFromPageUrl(pageUrl) {
    if (!pageUrl?.includes('?')) {
        return '';
    }

    return pageUrl.split('?').slice(1).join('?');
}

function escapeRegExp(value) {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/** @param {string} name @param {string} search */
export function readFilterValuesFromQuery(name, search) {
    const paramName = normalizeFilterParamName(name);

    if (!paramName) {
        return [];
    }

    if (search === '') {
        return [];
    }

    const params = new URLSearchParams(search);
    const scalar = params.get(paramName);

    if (scalar !== null && scalar !== '') {
        return scalar.includes(',')
            ? uniqueFilterValues(scalar.split(',').map((item) => item.trim()))
            : [scalar];
    }

    const arrayValues = params.getAll(`${paramName}[]`);

    if (arrayValues.length > 0) {
        return uniqueFilterValues(arrayValues);
    }

    return readLegacyIndexedFilterParams(params, paramName);
}

/** @param {MaybeName} name @param {string | undefined} pageUrl */
export function readInlineFilterFromSearch(name, pageUrl) {
    if (!name) {
        return [];
    }

    return readFilterValuesFromQuery(name, searchFromPageUrl(pageUrl));
}

/** @param {MaybeName} name @param {string | undefined} search */
export function readLegacyIndexedFilterParams(params, name) {
    const indexedPattern = new RegExp(`^${escapeRegExp(name)}\\[(\\d+)]$`);
    /** @type {Array<{ index: number; value: string }>} */
    const indexed = [];

    params.forEach((value, key) => {
        if (value === '') {
            return;
        }

        const match = key.match(indexedPattern);

        if (match) {
            indexed.push({ index: Number(match[1]), value });
        }
    });

    if (indexed.length === 0) {
        return [];
    }

    return uniqueFilterValues(
        indexed.sort((left, right) => left.index - right.index).map((entry) => entry.value),
    );
}
