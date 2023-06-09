/// <reference types="node" />
/**
 * External dependencies
 */
import * as fs from 'fs';
/**
 * Internal dependencies
 */
import type { RequestUtils } from './index';
export interface Media {
    id: number;
    title: {
        raw: string;
        rendered: string;
    };
    source_url: string;
    slug: string;
    alt_text: string;
    caption: {
        rendered: string;
    };
    link: string;
}
/**
 * List all media files.
 *
 * @see https://developer.wordpress.org/rest-api/reference/media/#list-media
 * @param  this
 */
declare function listMedia(this: RequestUtils): Promise<Media[]>;
/**
 * Upload a media file.
 *
 * @see https://developer.wordpress.org/rest-api/reference/media/#create-a-media-item
 * @param  this
 * @param  filePathOrData The path or data of the file being uploaded.
 */
declare function uploadMedia(this: RequestUtils, filePathOrData: string | fs.ReadStream): Promise<Media>;
/**
 * delete a media file.
 *
 * @see https://developer.wordpress.org/rest-api/reference/media/#delete-a-media-item
 * @param  this
 * @param  mediaId The ID of the media file.
 */
declare function deleteMedia(this: RequestUtils, mediaId: number): Promise<any>;
/**
 * delete all media files.
 *
 * @param  this
 */
declare function deleteAllMedia(this: RequestUtils): Promise<any[]>;
export { listMedia, uploadMedia, deleteMedia, deleteAllMedia };
//# sourceMappingURL=media.d.ts.map