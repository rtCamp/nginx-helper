/**
 * Internal dependencies
 */
import type { Admin } from './';
/**
 * Returns a promise resolving to one of either a string or null. A string will
 * be resolved if an error message is present in the contents of the page. If no
 * error is present, a null value will be resolved instead. This requires the
 * environment be configured to display errors.
 *
 * @see http://php.net/manual/en/function.error-reporting.php
 *
 * @param {Admin} this
 *
 * @return {Promise<?string>} Promise resolving to a string or null, depending
 *                            whether a page error is present.
 */
export declare function getPageError(this: Admin): Promise<string | null>;
//# sourceMappingURL=get-page-error.d.ts.map