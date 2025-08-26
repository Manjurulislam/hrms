
<template>
  <el-popconfirm
      cancel-button-text="No"
      confirm-button-text="Yes"
      title="Are you sure to restore this?"
      width="220"
      @confirm="confirmEvent"
  >
    <template #reference>
      <v-btn
          :title="buttonTitle"
          class="mx-1"
          color="success"
          icon="mdi-format-rotate-90"
          size="x-small"
          @mouseleave="buttonTitle = ''"
          @mouseover="buttonTitle = 'Restore'"
      ></v-btn>
    </template>
  </el-popconfirm>
</template>

<script setup>
import { useToast } from "vue-toastification";
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";

const toast = useToast();
const props = defineProps({
  route: String,
});

const linkHrf = computed(() => {
  return props.route != null ? props.route : '';
});
const buttonTitle = ref('');

const confirmEvent = () => {
  router.put(linkHrf.value, {}, {
    preserveState: false,
    onSuccess: () => {
      toast.success('Data has been Restored successfully.');
    },
    onError: (error) => {
      console.error('Restore failed:', error);
    },
  });
};
</script>

<style scoped>
.v-btn--icon.v-btn--density-default {
  width: calc(var(--v-btn-height) + 7px);
  height: calc(var(--v-btn-height) + 7px);
}

</style>
