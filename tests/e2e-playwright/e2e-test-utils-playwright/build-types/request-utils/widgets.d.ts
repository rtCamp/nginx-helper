/**
 * Delete all the widgets in the widgets screen.
 *
 * @this {import('./index').RequestUtils}
 */
export function deleteAllWidgets(): Promise<void>;
/**
 * Add a widget block to the widget area.
 *
 * @this {import('./index').RequestUtils}
 * @param {string} serializedBlock The serialized content of the inserted block HTML.
 * @param {string} widgetAreaId    The ID of the widget area.
 */
export function addWidgetBlock(serializedBlock: string, widgetAreaId: string): Promise<void>;
//# sourceMappingURL=widgets.d.ts.map