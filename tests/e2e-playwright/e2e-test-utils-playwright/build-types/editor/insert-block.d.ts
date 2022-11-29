/**
 * Internal dependencies
 */
import type { Editor } from './index';
interface BlockRepresentation {
    name: string;
    attributes: Object;
    innerBlocks: BlockRepresentation[];
}
/**
 * Insert a block.
 *
 * @param {Editor}              this
 * @param {BlockRepresentation} blockRepresentation Inserted block representation.
 */
declare function insertBlock(this: Editor, blockRepresentation: BlockRepresentation): Promise<void>;
export type { BlockRepresentation };
export { insertBlock };
//# sourceMappingURL=insert-block.d.ts.map