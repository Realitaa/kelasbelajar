# TextStyle extension

This mark renders a `<span>` HTML tag and enables you to add a list of styling related attributes, for example font-family, font-size, or color. The extension doesn’t add any styling attribute by default, but other extensions use it as the foundation, for example [`FontFamily`](/docs/editor/extensions/functionality/fontfamily) or [`Color`](/docs/editor/extensions/functionality/color).

```index.vue
<template>
  <div v-if="editor">
    <editor-content :editor="editor" />
  </div>
</template>

<script>
import Bold from '@tiptap/extension-bold'
import Document from '@tiptap/extension-document'
import Paragraph from '@tiptap/extension-paragraph'
import Text from '@tiptap/extension-text'
import { TextStyleKit } from '@tiptap/extension-text-style'
import { Editor, EditorContent } from '@tiptap/vue-3'

export default {
  components: {
    EditorContent,
  },

  data() {
    return {
      editor: null,
    }
  },

  mounted() {
    this.editor = new Editor({
      extensions: [Document, Paragraph, Text, TextStyleKit, Bold],
      content: `
        <p><span>This has a &lt;span&gt; tag without a style attribute, so it’s thrown away.</span></p>
        <p><span style="">But this one is wrapped in a &lt;span&gt; tag with an inline style attribute, so it’s kept - even if it’s empty for now.</span></p>


        <p>--- merge nested span styles option enabled ---</p>

        <p>
          <span style="color: #FF0000;">
            <span style="font-family: serif;">
              red serif
            </span>
          </span>
        </p>

        <p>
          <span style="color: #FF0000;">
            <span style="font-family: serif;">
              <span style="color: #0000FF;">
                blue serif
              </span>
            </span>
          </span>
        </p>

        <p>
          <span style="color: #00FF00;">
            <span style="font-family: serif;">green serif </span>
            <span style="font-family: serif;color: #FF0000;">red serif</span>
          </span>
        </p>

        <p>
          <span>
            plain
            <span style="color: #0000FF;">blue</span>
            plain
            <span style="color: #00FF00;">
              green
              <span style="font-family: serif;">green serif</span>
            </span>
            plain
          </span>
        </p>

        <p>
            <span style="color: #0000FF;">
              blue
              <span style="color: #00FF00;">
                green
                <span style="font-family: serif;">
                  green serif
                  <span style="color: #0000FF;">blue serif</span>
                </span>
              </span>
            </span>
        </p>

        <p>
          <strong>
            strong
            <span style="color: #0000FF;">
              <strong>
                strong blue
                <span style="font-size: 24px;font-family: serif;">strong blue serif </span>
                <span style="color: #00FF00;">
                  strong green
                  <span style="font-family: serif;">strong green serif</span>
                </span>
              </strong>
            </span>
          </strong>
        </p>
      `,
    })
  },

  beforeUnmount() {
    this.editor.destroy()
  },
}
</script>

<style lang="scss">
/* Basic editor styles */
.tiptap {
  :first-child {
    margin-top: 0;
  }
}
</style>
```

## [](#install)Install

```
pnpm add @tiptap/extension-text-style
```

## [](#commands)Commands

### [](#removeemptytextstyle)removeEmptyTextStyle()

Remove `<span>` tags without an inline style.

```
editor.command.removeEmptyTextStyle()
```

## [](#source-code)Source code

[packages/extension-text-style/](https://github.com/ueberdosis/tiptap/blob/main/packages/extension-text-style/)