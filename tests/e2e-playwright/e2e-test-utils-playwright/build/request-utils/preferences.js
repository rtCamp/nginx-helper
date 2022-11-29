"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.resetPreferences = void 0;
/**
 * Reset user preferences
 *
 * @param {this} this Request utils.
 */
async function resetPreferences() {
    await this.rest({
        path: '/wp/v2/users/me',
        method: 'PUT',
        data: {
            meta: {
                persisted_preferences: {},
            },
        },
    });
}
exports.resetPreferences = resetPreferences;
//# sourceMappingURL=preferences.js.map