"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.transformBlockTo = void 0;
/**
 * Clicks the default block appender.
 *
 * @param {Editor} this
 * @param {string} name Block name.
 */
async function transformBlockTo(name) {
    await this.page.evaluate(([blockName]) => {
        // @ts-ignore (Reason: wp isn't typed)
        const clientIds = window.wp.data
            .select('core/block-editor')
            .getSelectedBlockClientIds();
        // @ts-ignore (Reason: wp isn't typed)
        const blocks = window.wp.data
            .select('core/block-editor')
            .getBlocksByClientId(clientIds);
        // @ts-ignore (Reason: wp isn't typed)
        window.wp.data.dispatch('core/block-editor').replaceBlocks(clientIds, 
        // @ts-ignore (Reason: wp isn't typed)
        window.wp.blocks.switchToBlockType(blocks, blockName));
    }, [name]);
}
exports.transformBlockTo = transformBlockTo;
//# sourceMappingURL=transform-block-to.js.map