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

const props = defineProps({
  model: Object
})


const config = {
  selector: 'textarea',
  content_css: false,
  skin: false,
  height: 250,
  branding: false,
  plugins: [
    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
    'visualblocks', 'code', 'media', 'table'
  ],
  toolbar: 'undo redo | blocks | ' +
      'bold italic backcolor | alignleft aligncenter ' +
      'alignright alignjustify | bullist numlist outdent indent | ' +
      'removeformat | image|formatselect fontselect fontsizeselect',
}
</script>

<template>
  <div class="mt-3">
    <Editor v-model="model.content_bn" :init="config" api-key="no-api-key"/>
  </div>
<!--  <div v-if="model.errors.content_bn" style="color:rgb(176, 0, 32);font-size:12px;" class="pl-4 mt-2 mb-2 error-message">{{ model.errors.content_bn }}</div>-->
</template>

<style scoped>

</style>
