/**
 * Internal dependencies
 */
import type { RequestUtils } from './index';
declare type TemplateType = 'wp_template' | 'wp_template_part';
/**
 * Delete all the templates of given type.
 *
 * @param  this
 * @param  type - Template type to delete.
 */
declare function deleteAllTemplates(this: RequestUtils, type: TemplateType): Promise<void>;
export { deleteAllTemplates };
//# sourceMappingURL=templates.d.ts.map