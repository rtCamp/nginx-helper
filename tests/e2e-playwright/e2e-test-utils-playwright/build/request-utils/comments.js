"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.deleteAllComments = exports.createComment = void 0;
/**
 * Create new comment using the REST API.
 *
 * @param {} this    RequestUtils.
 * @param {} payload CreateCommentPayload.
 */
async function createComment(payload) {
    const currentUser = await this.rest({
        path: '/wp/v2/users/me',
        method: 'GET',
    });
    const author = currentUser.id;
    const comment = await this.rest({
        method: 'POST',
        path: '/wp/v2/comments',
        data: { ...payload, author },
    });
    return comment;
}
exports.createComment = createComment;
/**
 * Delete all comments using the REST API.
 *
 * @param {} this RequestUtils.
 */
async function deleteAllComments() {
    // List all comments.
    // https://developer.wordpress.org/rest-api/reference/comments/#list-comments
    const comments = await this.rest({
        path: '/wp/v2/comments',
        params: {
            per_page: 100,
            // All possible statuses.
            status: 'unapproved,approved,spam,trash',
        },
    });
    // Delete all comments one by one.
    // https://developer.wordpress.org/rest-api/reference/comments/#delete-a-comment
    // "/wp/v2/comments" doesn't support batch requests yet.
    await Promise.all(comments.map((comment) => this.rest({
        method: 'DELETE',
        path: `/wp/v2/comments/${comment.id}`,
        params: {
            force: true,
        },
    })));
}
exports.deleteAllComments = deleteAllComments;
//# sourceMappingURL=comments.js.map