import { mergeAttributes, Node } from '@tiptap/core';
import { VueNodeViewRenderer } from '@tiptap/vue-3';
import SlideshowNode from '../SlideshowNode.vue';

export const SlideshowExtension = Node.create({
    name: 'slideshow',

    group: 'block',
    atom: true,

    addAttributes() {
        return {
            images: {
                default: [],
            },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'slideshow-node',
            },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return ['slideshow-node', mergeAttributes(HTMLAttributes)];
    },

    addNodeView() {
        return VueNodeViewRenderer(SlideshowNode);
    },
});
