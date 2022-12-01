"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.RequestUtils = void 0;
/**
 * External dependencies
 */
const fs = require("fs/promises");
const path = require("path");
const test_1 = require("@playwright/test");
/**
 * Internal dependencies
 */
const config_1 = require("../config");
const login_1 = require("./login");
const media_1 = require("./media");
const rest_1 = require("./rest");
const plugins_1 = require("./plugins");
const templates_1 = require("./templates");
const themes_1 = require("./themes");
const blocks_1 = require("./blocks");
const comments_1 = require("./comments");
const posts_1 = require("./posts");
const preferences_1 = require("./preferences");
const widgets_1 = require("./widgets");
class RequestUtils {
    request;
    user;
    maxBatchSize;
    storageState;
    storageStatePath;
    baseURL;
    pluginsMap = null;
    static async setup({ user, storageStatePath, baseURL = config_1.WP_BASE_URL, }) {
        let storageState;
        if (storageStatePath) {
            await fs.mkdir(path.dirname(storageStatePath), {
                recursive: true,
            });
            try {
                storageState = JSON.parse(await fs.readFile(storageStatePath, 'utf-8'));
            }
            catch (error) {
                if (error instanceof Error &&
                    error.code === 'ENOENT') {
                    // Ignore errors if the state is not found.
                }
                else {
                    throw error;
                }
            }
        }
        const requestContext = await test_1.request.newContext({
            baseURL,
            storageState: storageState && {
                cookies: storageState.cookies,
                origins: [],
            },
        });
        const requestUtils = new RequestUtils(requestContext, {
            user,
            storageState,
            storageStatePath,
            baseURL,
        });
        return requestUtils;
    }
    constructor(requestContext, { user = config_1.WP_ADMIN_USER, storageState, storageStatePath, baseURL = config_1.WP_BASE_URL, } = {}) {
        this.user = user;
        this.request = requestContext;
        this.storageStatePath = storageStatePath;
        this.storageState = storageState;
        this.baseURL = baseURL;
    }
    login = login_1.login.bind(this);
    setupRest = rest_1.setupRest.bind(this);
    // .bind() drops the generic types. Re-casting it to keep the type signature.
    rest = rest_1.rest.bind(this);
    getMaxBatchSize = rest_1.getMaxBatchSize.bind(this);
    // .bind() drops the generic types. Re-casting it to keep the type signature.
    batchRest = rest_1.batchRest.bind(this);
    getPluginsMap = plugins_1.getPluginsMap.bind(this);
    activatePlugin = plugins_1.activatePlugin.bind(this);
    deactivatePlugin = plugins_1.deactivatePlugin.bind(this);
    activateTheme = themes_1.activateTheme.bind(this);
    deleteAllBlocks = blocks_1.deleteAllBlocks;
    deleteAllPosts = posts_1.deleteAllPosts.bind(this);
    createComment = comments_1.createComment.bind(this);
    deleteAllComments = comments_1.deleteAllComments.bind(this);
    deleteAllWidgets = widgets_1.deleteAllWidgets.bind(this);
    addWidgetBlock = widgets_1.addWidgetBlock.bind(this);
    deleteAllTemplates = templates_1.deleteAllTemplates.bind(this);
    resetPreferences = preferences_1.resetPreferences.bind(this);
    listMedia = media_1.listMedia.bind(this);
    uploadMedia = media_1.uploadMedia.bind(this);
    deleteMedia = media_1.deleteMedia.bind(this);
    deleteAllMedia = media_1.deleteAllMedia.bind(this);
}
exports.RequestUtils = RequestUtils;
//# sourceMappingURL=index.js.map