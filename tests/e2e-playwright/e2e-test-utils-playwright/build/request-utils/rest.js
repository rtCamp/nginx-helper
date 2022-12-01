"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.batchRest = exports.getMaxBatchSize = exports.rest = exports.setupRest = void 0;
/**
 * External dependencies
 */
const fs = require("fs/promises");
const path_1 = require("path");
/**
 * Internal dependencies
 */
const config_1 = require("../config");
function splitRequestsToChunks(requests, chunkSize) {
    const arr = [...requests];
    const cache = [];
    while (arr.length) {
        cache.push(arr.splice(0, chunkSize));
    }
    return cache;
}
async function getAPIRootURL(request) {
    // Discover the API root url using link header.
    // See https://developer.wordpress.org/rest-api/using-the-rest-api/discovery/#link-header
    const response = await request.head(config_1.WP_BASE_URL);
    const links = response.headers().link;
    const restLink = links?.match(/<([^>]+)>; rel="https:\/\/api\.w\.org\/"/);
    if (!restLink) {
        throw new Error(`Failed to discover REST API endpoint.
 Link header: ${links}`);
    }
    const [, rootURL] = restLink;
    return rootURL;
}
async function setupRest() {
    const [nonce, rootURL] = await Promise.all([
        this.login(),
        getAPIRootURL(this.request),
    ]);
    const { cookies } = await this.request.storageState();
    const storageState = {
        cookies,
        nonce,
        rootURL,
    };
    if (this.storageStatePath) {
        await fs.mkdir((0, path_1.dirname)(this.storageStatePath), { recursive: true });
        await fs.writeFile(this.storageStatePath, JSON.stringify(storageState), 'utf-8');
    }
    this.storageState = storageState;
    return storageState;
}
exports.setupRest = setupRest;
async function rest(options) {
    const { path, ...fetchOptions } = options;
    if (!path) {
        throw new Error('"path" is required to make a REST call');
    }
    if (!this.storageState?.nonce || !this.storageState?.rootURL) {
        await this.setupRest();
    }
    const relativePath = path.startsWith('/') ? path.slice(1) : path;
    const url = this.storageState.rootURL + relativePath;
    try {
        const response = await this.request.fetch(url, {
            ...fetchOptions,
            failOnStatusCode: false,
            headers: {
                'X-WP-Nonce': this.storageState.nonce,
                ...(fetchOptions.headers || {}),
            },
        });
        const json = await response.json();
        if (!response.ok()) {
            throw json;
        }
        return json;
    }
    catch (error) {
        // Nonce in invalid, retry again with a renewed nonce.
        if (typeof error === 'object' &&
            error !== null &&
            Object.prototype.hasOwnProperty.call(error, 'code') &&
            error.code === 'rest_cookie_invalid_nonce') {
            await this.setupRest();
            return this.rest(options);
        }
        throw error;
    }
}
exports.rest = rest;
/**
 * Get the maximum batch size for the REST API.
 *
 * @param {} this         RequestUtils.
 * @param {} forceRefetch Force revalidate the cached max batch size.
 */
async function getMaxBatchSize(forceRefetch = false) {
    if (!forceRefetch && this.maxBatchSize) {
        return this.maxBatchSize;
    }
    const response = await this.rest({
        method: 'OPTIONS',
        path: '/batch/v1',
    });
    this.maxBatchSize = response.endpoints[0].args.requests.maxItems;
    return this.maxBatchSize;
}
exports.getMaxBatchSize = getMaxBatchSize;
async function batchRest(requests) {
    const maxBatchSize = await this.getMaxBatchSize();
    if (requests.length > maxBatchSize) {
        const chunks = splitRequestsToChunks(requests, maxBatchSize);
        const chunkResponses = await Promise.all(chunks.map((chunkRequests) => this.batchRest(chunkRequests)));
        return chunkResponses.flat();
    }
    const batchResponses = await this.rest({
        method: 'POST',
        path: '/batch/v1',
        data: {
            requests,
            validation: 'require-all-validate',
        },
    });
    if (batchResponses.failed) {
        throw batchResponses;
    }
    return batchResponses.responses;
}
exports.batchRest = batchRest;
//# sourceMappingURL=rest.js.map