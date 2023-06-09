/// <reference types="node" />
import type { APIRequestContext, Cookie } from '@playwright/test';
import type { User } from './login';
import { rest, batchRest } from './rest';
import { deleteAllBlocks } from './blocks';
import { deleteAllPosts } from './posts';
import { deleteAllWidgets, addWidgetBlock } from './widgets';
interface StorageState {
    cookies: Cookie[];
    nonce: string;
    rootURL: string;
}
declare class RequestUtils {
    request: APIRequestContext;
    user: User;
    maxBatchSize?: number;
    storageState?: StorageState;
    storageStatePath?: string;
    baseURL?: string;
    pluginsMap: Record<string, string> | null;
    static setup({ user, storageStatePath, baseURL, }: {
        user?: User;
        storageStatePath?: string;
        baseURL?: string;
    }): Promise<RequestUtils>;
    constructor(requestContext: APIRequestContext, { user, storageState, storageStatePath, baseURL, }?: {
        user?: User;
        storageState?: StorageState;
        storageStatePath?: string;
        baseURL?: string;
    });
    login: (user?: User | undefined) => Promise<string>;
    setupRest: () => Promise<StorageState>;
    rest: typeof rest;
    getMaxBatchSize: (forceRefetch?: boolean | undefined) => Promise<number>;
    batchRest: typeof batchRest;
    getPluginsMap: (forceRefetch?: boolean | undefined) => Promise<Record<string, string>>;
    activatePlugin: (slug: string) => Promise<void>;
    deactivatePlugin: (slug: string) => Promise<void>;
    activateTheme: (themeSlug: string) => Promise<void>;
    deleteAllBlocks: typeof deleteAllBlocks;
    deleteAllPosts: typeof deleteAllPosts;
    createComment: (payload: import("./comments").CreateCommentPayload) => Promise<import("./comments").Comment>;
    deleteAllComments: () => Promise<void>;
    deleteAllWidgets: typeof deleteAllWidgets;
    addWidgetBlock: typeof addWidgetBlock;
    deleteAllTemplates: (type: "wp_template" | "wp_template_part") => Promise<void>;
    resetPreferences: () => Promise<void>;
    listMedia: () => Promise<import("./media").Media[]>;
    uploadMedia: (filePathOrData: string | import("fs").ReadStream) => Promise<import("./media").Media>;
    deleteMedia: (mediaId: number) => Promise<any>;
    deleteAllMedia: () => Promise<any[]>;
}
export type { StorageState };
export { RequestUtils };
//# sourceMappingURL=index.d.ts.map