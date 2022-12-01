/**
 * Valid argument argument type from which to derive viewport dimensions.
 *
 * @typedef {WPDimensionsName|WPViewportDimensions} WPViewport
 */
/**
 * Sets browser viewport to specified type.
 *
 * @this {import('./').PageUtils}
 * @param {WPViewport} viewport Viewport name or dimensions object to assign.
 */
export function setBrowserViewport(viewport: WPViewport): Promise<void>;
/**
 * Valid argument argument type from which to derive viewport dimensions.
 */
export type WPViewport = WPDimensionsName | WPViewportDimensions;
/**
 * Named viewport options.
 */
export type WPDimensionsName = "large" | "medium" | "small";
/**
 * Viewport dimensions object.
 */
export type WPViewportDimensions = {
    /**
     * Width, in pixels.
     */
    width: number;
    /**
     * Height, in pixels.
     */
    height: number;
};
//# sourceMappingURL=set-browser-viewport.d.ts.map