import assert from 'node:assert/strict';
import {
    normalizeFilterParamName,
    readFilterValuesFromQuery,
    readInlineFilterFromSearch,
    readLegacyIndexedFilterParams,
    uniqueFilterValues,
} from './inline-filter-query.mjs';

const filterName = 'filter[user_id]';
const filterFieldName = 'filter[user_id][]';

assert.equal(normalizeFilterParamName(filterFieldName), filterName);

assert.deepEqual(uniqueFilterValues(['1', '1', '2']), ['1', '2']);

assert.deepEqual(readFilterValuesFromQuery(filterName, 'filter%5Buser_id%5D=2'), ['2']);
assert.deepEqual(readFilterValuesFromQuery(filterFieldName, 'filter%5Buser_id%5D=2'), ['2']);
assert.deepEqual(readFilterValuesFromQuery(filterName, 'filter[user_id]=2'), ['2']);
assert.deepEqual(readFilterValuesFromQuery(filterName, 'filter[user_id]=1,2'), ['1', '2']);
assert.deepEqual(readFilterValuesFromQuery(filterFieldName, 'filter[user_id][]=2'), ['2']);

const legacyParams = new URLSearchParams('filter[user_id][0]=1&filter[user_id][1]=2');
assert.deepEqual(readLegacyIndexedFilterParams(legacyParams, filterName), ['1', '2']);

const nestedParams = new URLSearchParams('filter[user_id][0]=1&filter[user_id][0][0]=1');
assert.deepEqual(readLegacyIndexedFilterParams(nestedParams, filterName), ['1']);

assert.deepEqual(
    readInlineFilterFromSearch(filterFieldName, '/entities/visitor-records?filter%5Buser_id%5D=2'),
    ['2'],
);

assert.deepEqual(readInlineFilterFromSearch(filterName, '/entities/visitor-records'), []);

console.log('ok');
