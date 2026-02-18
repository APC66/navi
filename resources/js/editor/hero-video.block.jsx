import { InnerBlocks, useBlockProps } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";

/* Block name */
export const name = `radicle/hero-video`;

/* Block title */
export const title = __(`HeroVideo`, `radicle`);

/* Block category */
export const category = `design`;

/* Block attributes */
export const attributes = {};

/* Block edit */
export const edit = () => {
  const props = useBlockProps();

  return (
    <div {...props}>
      <InnerBlocks />
    </div>
  );
};

/* Block save */
export const save = () => <InnerBlocks.Content />;
