<script setup>
import Vue3TagsInput from "vue3-tags-input";
import {reactive, watch} from "vue";

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(["update:modelValue"]);
const state = reactive({
    tags: [...props.modelValue],
});

watch(
    () => props.modelValue,
    (newValue) => {
        state.tags = [...newValue];
    },
    {immediate: true}
);

const tagValidate = (value) => {
    const regex = new RegExp(/^[a-zA-Z]+$/);
    return regex.test(value)
}

const handleChangeTag = (tag) => {
    state.tags = tag;
    emit("update:modelValue", state.tags);
}
</script>

<template>
    <div>
        <v-label>Tags</v-label>
        <vue3-tags-input
            v-model:tags="state.tags"
            :validate="tagValidate"
            placeholder="Enter tags"
            @on-tags-changed="handleChangeTag"/>
    </div>
</template>

<style scoped>

</style>
