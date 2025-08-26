<template>
    <v-switch
        :disabled="disabled"
        hide-details
        inset
        :error-messages="errorMessage"
        :label="label"
        :model-value="modelValue"
        :prefix="prefix"
        color="success"
        :required="required"
    ></v-switch>
</template>
<script>
import {commonProps} from "@/Components/common/form/commonProps";

let propObject = {};
propObject = {...commonProps, ...propObject}
</script>
<script setup>
const props = defineProps(propObject)
</script>

<style scoped>

</style>
