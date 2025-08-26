<script setup>
import "tinymce/tinymce";
import "tinymce/themes/silver";
import "tinymce/icons/default";
import "tinymce/skins/ui/oxide/skin.css";
//plugins
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/code';
import 'tinymce/plugins/preview';
import 'tinymce/plugins/visualblocks';
import 'tinymce/plugins/wordcount';
import Editor from '@tinymce/tinymce-vue'
import {computed} from "vue";

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    errorMessages: {
        type: Array,
        default: () => []
    },
    label: {
        type: String,
        default: 'Terms & Conditions'
    },
    height: {
        type: Number,
        default: 300
    }
})

const emit = defineEmits(['update:modelValue'])

const config = {
    selector: 'textarea',
    content_css: false,
    skin: false,
    height: props.height,
    branding: false,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'code', 'preview',
        'visualblocks', 'wordcount'
    ],
    toolbar: 'undo redo | formatselect | ' +
        'bold italic underline | ' +
        'alignleft aligncenter alignright alignjustify | ' +
        'bullist numlist outdent indent | ' +
        'link | code preview | ' +
        'removeformat',
    menubar: false,
    // Add these settings to fix the URL conversion issue
    relative_urls: false,
    remove_script_host: false,
    convert_urls: false,
    // Optional: Add a custom URL converter if you need more control
    urlconverter_callback: function (url, node, on_save, name) {
        // This ensures that all URLs remain as they were entered
        return url;
    },
    // Content styling for terms
    content_style: `
        body {
            font-family: 'Roboto', Arial, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            margin: 10px;
            color: #424242;
        }
        p { margin-bottom: 8px; }
        h1, h2, h3, h4, h5, h6 {
            margin-top: 16px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        ul, ol { margin-bottom: 12px; }
        li { margin-bottom: 4px; }
    `,
    // Format options suitable for terms
    block_formats: 'Paragraph=p; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6'
}

const content = computed({
    get: () => props.modelValue,
    set: (newValue) => emit('update:modelValue', newValue)
})
</script>

<template>
    <div class="mt-3">
        <v-label v-if="label" class="mb-2 font-weight-medium">{{ label }}</v-label>
        <div class="tiny-editor-wrapper terms-editor">
            <Editor v-model="content" :init="config" api-key="no-api-key"/>
        </div>
        <div v-if="errorMessages && errorMessages.length > 0" class="error-messages mt-1">
            <div v-for="error in errorMessages" :key="error" class="text-error text-caption">
                {{ error }}
            </div>
        </div>
    </div>
</template>
