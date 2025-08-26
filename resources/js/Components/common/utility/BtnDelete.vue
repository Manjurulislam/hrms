<script setup>
import {useToast} from "vue-toastification";
import {computed} from "vue";
import {router} from "@inertiajs/vue3";

const toast = useToast();
const props = defineProps({
    route: String,
})

const linkHrf = computed(() => {
    return props.route != null ? props.route : '';
})

const confirmEvent = () => {
    router.delete(linkHrf.value, {
        preserveState: false,
        onSuccess: page => {
            toast.success('Data has been Deleted successfully.');
        },
    });
}
</script>

<template>
    <el-popconfirm
        cancel-button-text="No"
        confirm-button-text="Yes"
        title="Are you sure to delete this?"
        width="250"
        @confirm="confirmEvent"
    >
        <template #reference>
            <v-btn color="error" icon="mdi-delete" size="x-small"/>
        </template>
    </el-popconfirm>
</template>
