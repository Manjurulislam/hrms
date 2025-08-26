<template>
    <v-textarea
        :disabled="disabled"
        :error-messages="errorMessage"
        :label="label"
        :model-value="modelValue"
        :prefix="prefix"
        :required="required"
        density="compact"
        variant="outlined"
        @input="$emit('update:modelValue', $event.target.value)"
    ></v-textarea>
</template>

<script>
import {commonProps} from "./commonProps";

let propObject = {};
propObject = {...commonProps, ...propObject}
</script>
<script setup>
const props = defineProps(propObject)
</script>

<style scoped>

</style>
