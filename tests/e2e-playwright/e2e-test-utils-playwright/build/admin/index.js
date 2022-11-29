"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.Admin = void 0;
/**
 * Internal dependencies
 */
/**
 * Internal dependencies
 */
const create_new_post_1 = require("./create-new-post");
const get_page_error_1 = require("./get-page-error");
const visit_admin_page_1 = require("./visit-admin-page");
const visit_site_editor_1 = require("./visit-site-editor");
class Admin {
    browser;
    page;
    pageUtils;
    context;
    constructor({ page, pageUtils }) {
        this.page = page;
        this.context = page.context();
        this.browser = this.context.browser();
        this.pageUtils = pageUtils;
    }
    createNewPost = create_new_post_1.createNewPost.bind(this);
    getPageError = get_page_error_1.getPageError.bind(this);
    visitAdminPage = visit_admin_page_1.visitAdminPage.bind(this);
    visitSiteEditor = visit_site_editor_1.visitSiteEditor.bind(this);
}
exports.Admin = Admin;
//# sourceMappingURL=index.js.map