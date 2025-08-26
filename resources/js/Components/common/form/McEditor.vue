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
import Editor from '@tinymce/tinymce-vue'
import {ref, watch} from "vue";

const props = defineProps({
    modelValue: {
        type: Object,
        required: true
    },
    label: {
        type: String,
        default: '' // Default empty if no label provided
    }
})

const emit = defineEmits(['update:modelValue'])
const editorRef = ref(null)

// Create a config with the setup function that handles content updates properly
const config = {
    selector: 'textarea',
    content_css: false,
    skin: false,
    height: 350,
    branding: false,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'visualblocks', 'code', 'media', 'table'
    ],
    toolbar: 'undo redo | blocks | ' +
        'bold italic backcolor | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | ' +
        'removeformat | image|formatselect fontselect fontsizeselect',
    relative_urls: false,
    remove_script_host: false,
    convert_urls: false,
    urlconverter_callback: function (url, node, on_save, name) {
        return url;
    },
    setup: function (editor) {
        editorRef.value = editor;

        // Handle content updates through a direct binding in the setup function
        editor.on('init', function () {
            editor.setContent(props.modelValue || '');
        });

        // Use input event for real-time updates
        editor.on('input', function () {
            emit('update:modelValue', editor.getContent());
        });

        // Also catch keyup for real-time typing
        editor.on('keyup', function () {
            emit('update:modelValue', editor.getContent());
        });

        // And handle undo/redo operations
        editor.on('undo redo', function () {
            emit('update:modelValue', editor.getContent());
        });
    }
}

// Watch for changes to the model from outside
watch(() => props.modelValue, (newValue) => {
    if (editorRef.value && editorRef.value.getContent() !== newValue) {
        editorRef.value.setContent(newValue || '');
    }
}, {deep: true});
</script>

<template>
    <div class="mt-3">
        <label v-if="label" class="block text-sm font-medium mb-2">{{ label }}</label>
        <Editor
            :init="config"
            :value="modelValue"
            api-key="no-api-key"
        />
    </div>
</template>
