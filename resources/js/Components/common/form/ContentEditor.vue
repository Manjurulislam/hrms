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
import 'tinymce/plugins/image';
import 'tinymce/plugins/code';
import 'tinymce/plugins/charmap';
import 'tinymce/plugins/preview';
import 'tinymce/plugins/table';
import 'tinymce/plugins/media';
import 'tinymce/plugins/visualblocks';
import 'tinymce/plugins/wordcount';
import 'tinymce/plugins/fullscreen';
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
        default: 'Content'
    },
    height: {
        type: Number,
        default: 400
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
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'visualblocks', 'code', 'media', 'table', 'wordcount', 'fullscreen'
    ],
    toolbar: 'undo redo | blocks formatselect | ' +
        'bold italic underline strikethrough | forecolor backcolor | ' +
        'alignleft aligncenter alignright alignjustify | ' +
        'bullist numlist outdent indent | ' +
        'link image media table | ' +
        'code preview fullscreen | ' +
        'removeformat',
    menubar: 'edit view insert format tools table',
    // Add these settings to fix the URL conversion issue
    relative_urls: false,
    remove_script_host: false,
    convert_urls: false,
    // Optional: Add a custom URL converter if you need more control
    urlconverter_callback: function (url, node, on_save, name) {
        // This ensures that all URLs remain as they were entered
        return url;
    },
    // Content styling
    content_style: `
        body {
            font-family: 'Roboto', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            margin: 10px;
        }
        p { margin-bottom: 10px; }
        h1, h2, h3, h4, h5, h6 { margin-top: 20px; margin-bottom: 10px; }
    `
}

const content = computed({
    get: () => props.modelValue,
    set: (newValue) => emit('update:modelValue', newValue)
})
</script>

<template>
    <div class="mt-3">
        <v-label v-if="label" class="mb-2 font-weight-medium">{{ label }}</v-label>
        <div class="tiny-editor-wrapper">
            <Editor v-model="content" :init="config" api-key="no-api-key"/>
        </div>
        <div v-if="errorMessages && errorMessages.length > 0" class="error-messages mt-1">
            <div v-for="error in errorMessages" :key="error" class="text-error text-caption">
                {{ error }}
            </div>
        </div>
    </div>
</template>
