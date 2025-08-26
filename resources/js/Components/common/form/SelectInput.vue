<template>
    <v-select
        :disabled="disabled"
        :error-messages="errorMessage"
        :item-title="itemKey"
        :items="items"
        :label="label"
        :model-value="modelValue"
        :multiple="multiple"
        :prefix="prefix"
        :required="required"
        class="pa-2"
        density="compact"
        item-value="id"
        variant="outlined"
    ></v-select>
</template>

<script>
import {commonProps} from "./commonProps";
</script>

<script setup>

const props = defineProps({
    commonProps,
    multiple: {
        type: Boolean,
        required: false,
        default: false
    },
    items: {
        type: Array,
        required: false,
    },
    itemKey: {
        type: [String, Number],
        required: false,
    },
})

</script>
