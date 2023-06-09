/**
 * Creates new post.
 *
 * @this {import('.').Editor}
 * @param {Object}  object                    Object to create new post, along with tips enabling option.
 * @param {string}  [object.postType]         Post type of the new post.
 * @param {string}  [object.title]            Title of the new post.
 * @param {string}  [object.content]          Content of the new post.
 * @param {string}  [object.excerpt]          Excerpt of the new post.
 * @param {boolean} [object.showWelcomeGuide] Whether to show the welcome guide.
 */
export function createNewPost({ postType, title, content, excerpt, showWelcomeGuide, }?: {
    postType?: string | undefined;
    title?: string | undefined;
    content?: string | undefined;
    excerpt?: string | undefined;
    showWelcomeGuide?: boolean | undefined;
}): Promise<void>;
//# sourceMappingURL=create-new-post.d.ts.map