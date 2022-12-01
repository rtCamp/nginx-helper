/**
 * Internal dependencies
 */
import type { Admin } from './';
/**
 * Visits admin page and handle errors.
 *
 * @param {Admin}  this
 * @param {string} adminPath String to be serialized as pathname.
 * @param {string} query     String to be serialized as query portion of URL.
 */
export declare function visitAdminPage(this: Admin, adminPath: string, query: string): Promise<void>;
//# sourceMappingURL=visit-admin-page.d.ts.map