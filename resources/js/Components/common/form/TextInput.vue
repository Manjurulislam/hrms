<template>
    <v-text-field
        :disabled="disabled"
        :error-messages="errorMessage"
        :label="label"
        :model-value="modelValue"
        :prefix="prefix"
        :required="required"
        :type="type"
        class="pb-3"
        density="compact"
        hide-details="auto"
        @input="$emit('update:modelValue', $event.target.value)"
    />
</template>

<script>
import {commonProps} from "@/Components/common/form/commonProps";

let propObject = {};
propObject = {...commonProps, ...propObject}
</script>
<script setup>
const props = defineProps(propObject)
</script>
