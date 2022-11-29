/**
 * External dependencies
 */
import type { Browser, Page, BrowserContext } from '@playwright/test';
/**
 * Internal dependencies
 */
/**
 * Internal dependencies
 */
import { createNewPost } from './create-new-post';
import type { PageUtils } from '../page-utils';
declare type AdminConstructorProps = {
    page: Page;
    pageUtils: PageUtils;
};
export declare class Admin {
    browser: Browser;
    page: Page;
    pageUtils: PageUtils;
    context: BrowserContext;
    constructor({ page, pageUtils }: AdminConstructorProps);
    createNewPost: typeof createNewPost;
    getPageError: () => Promise<string | null>;
    visitAdminPage: (adminPath: string, query: string) => Promise<void>;
    visitSiteEditor: (query: import("./visit-site-editor").SiteEditorQueryParams, skipWelcomeGuide?: boolean | undefined) => Promise<void>;
}
export {};
//# sourceMappingURL=index.d.ts.map