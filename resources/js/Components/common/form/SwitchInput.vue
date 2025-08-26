<template>
    <v-switch
        :disabled="disabled"
        :error-messages="errorMessage"
        :label="label"
        :model-value="modelValue"
        :prefix="prefix"
        :required="required"
        color="success"
        hide-details
        inset
    ></v-switch>
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
