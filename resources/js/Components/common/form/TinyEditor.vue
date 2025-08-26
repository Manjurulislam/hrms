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
import {computed} from "vue";

const props = defineProps({
    model: Object
})

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
    // Add these settings to fix the URL conversion issue
    relative_urls: false,
    remove_script_host: false,
    convert_urls: false,
    // Optional: Add a custom URL converter if you need more control
    urlconverter_callback: function (url, node, on_save, name) {
        // This ensures that all URLs remain as they were entered
        return url;
    }
}


const content = computed({
    get: () => {
        // Return content if it exists, otherwise content_en
        return props.model.content !== undefined ? props.model.content : props.model.content_en
    },
    set: (newValue) => {
        // Update the property that actually exists in the model
        if (props.model.content !== undefined) {
            props.model.content = newValue
        } else {
            props.model.content_en = newValue
        }
    }
})

</script>
<template>
    <div class="mt-3">
        <Editor v-model="content" :init="config" api-key="no-api-key"/>
    </div>
</template>
