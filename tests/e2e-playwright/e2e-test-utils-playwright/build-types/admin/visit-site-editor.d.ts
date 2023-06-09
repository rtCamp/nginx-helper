/**
 * Internal dependencies
 */
import type { Admin } from './';
export interface SiteEditorQueryParams {
    postId: string | number;
    postType: string;
}
/**
 * Visits the Site Editor main page
 *
 * By default, it also skips the welcome guide. The option can be disabled if need be.
 *
 * @param {Admin}                 this
 * @param {SiteEditorQueryParams} query            Query params to be serialized as query portion of URL.
 * @param {boolean}               skipWelcomeGuide Whether to skip the welcome guide as part of the navigation.
 */
export declare function visitSiteEditor(this: Admin, query: SiteEditorQueryParams, skipWelcomeGuide?: boolean): Promise<void>;
//# sourceMappingURL=visit-site-editor.d.ts.map