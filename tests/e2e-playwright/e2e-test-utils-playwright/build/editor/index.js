"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.Editor = void 0;
/**
 * Internal dependencies
 */
const click_block_options_menu_item_1 = require("./click-block-options-menu-item");
const click_block_toolbar_button_1 = require("./click-block-toolbar-button");
const get_edited_post_content_1 = require("./get-edited-post-content");
const insert_block_1 = require("./insert-block");
const open_document_settings_sidebar_1 = require("./open-document-settings-sidebar");
const preview_1 = require("./preview");
const publish_post_1 = require("./publish-post");
const select_blocks_1 = require("./select-blocks");
const show_block_toolbar_1 = require("./show-block-toolbar");
const site_editor_1 = require("./site-editor");
const transform_block_to_1 = require("./transform-block-to");
class Editor {
    browser;
    page;
    context;
    #hasIframe;
    constructor({ page, hasIframe = false }) {
        this.page = page;
        this.context = page.context();
        this.browser = this.context.browser();
        this.#hasIframe = hasIframe;
    }
    get canvas() {
        let frame;
        if (this.#hasIframe) {
            frame = this.page.frame('editor-canvas');
        }
        else {
            frame = this.page;
        }
        if (!frame) {
            throw new Error('EditorUtils: unable to find editor canvas iframe or page');
        }
        return frame;
    }
    clickBlockOptionsMenuItem = click_block_options_menu_item_1.clickBlockOptionsMenuItem.bind(this);
    clickBlockToolbarButton = click_block_toolbar_button_1.clickBlockToolbarButton.bind(this);
    getEditedPostContent = get_edited_post_content_1.getEditedPostContent.bind(this);
    insertBlock = insert_block_1.insertBlock.bind(this);
    openDocumentSettingsSidebar = open_document_settings_sidebar_1.openDocumentSettingsSidebar.bind(this);
    openPreviewPage = preview_1.openPreviewPage.bind(this);
    publishPost = publish_post_1.publishPost.bind(this);
    saveSiteEditorEntities = site_editor_1.saveSiteEditorEntities.bind(this);
    selectBlocks = select_blocks_1.selectBlocks.bind(this);
    showBlockToolbar = show_block_toolbar_1.showBlockToolbar.bind(this);
    transformBlockTo = transform_block_to_1.transformBlockTo.bind(this);
}
exports.Editor = Editor;
//# sourceMappingURL=index.js.map