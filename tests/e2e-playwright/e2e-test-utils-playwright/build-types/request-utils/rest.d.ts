import type { APIRequestContext } from '@playwright/test';
import type { RequestUtils, StorageState } from './index';
declare function setupRest(this: RequestUtils): Promise<StorageState>;
declare type RequestFetchOptions = Exclude<Parameters<APIRequestContext['fetch']>[1], undefined>;
export interface RestOptions extends RequestFetchOptions {
    path: string;
}
declare function rest<RestResponse = any>(this: RequestUtils, options: RestOptions): Promise<RestResponse>;
/**
 * Get the maximum batch size for the REST API.
 *
 * @param {} this         RequestUtils.
 * @param {} forceRefetch Force revalidate the cached max batch size.
 */
declare function getMaxBatchSize(this: RequestUtils, forceRefetch?: boolean): Promise<number>;
export interface BatchRequest {
    method?: string;
    path: string;
    headers?: Record<string, string | string[]>;
    body?: any;
}
declare function batchRest<BatchResponse>(this: RequestUtils, requests: BatchRequest[]): Promise<BatchResponse[]>;
export { setupRest, rest, getMaxBatchSize, batchRest };
//# sourceMappingURL=rest.d.ts.map