<template>
  <div class="tag-input-container">
    <el-tag
        v-for="tag in tags"
        :key="tag"
        closable
        :disable-transitions="false"
        @close="handleClose(tag)"
    >
      {{ tag }}
    </el-tag>

    <el-input
        v-if="inputVisible"
        ref="inputRef"
        v-model="inputValue"
        class="tag-input-field"
        size="small"
        @keydown.enter.prevent="handleInputConfirm"
        @blur="handleInputConfirm"
    />

    <el-button v-else class="button-new-tag" size="small" @click="showInput">
      + Add Variant Value
    </el-button>
  </div>
</template>

<script setup>
import { ref, nextTick, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update:modelValue']);

const inputValue = ref('');
const tags = ref([...props.modelValue]);
const inputVisible = ref(false);
const inputRef = ref(null);


watch(
    () => props.modelValue,
    (newTags) => {
      tags.value = [...newTags];
    },
    { immediate: true }
);


const updateTags = () => {
  emit('update:modelValue', tags.value);
};

const handleClose = (tag) => {
  const index = tags.value.indexOf(tag);
  if (index !== -1) {
    tags.value.splice(index, 1);
    updateTags();
  }
};

const showInput = () => {
  inputVisible.value = true;
  nextTick(() => {
    inputRef.value?.focus();
  });
};

const handleInputConfirm = () => {
  const trimmedTag = inputValue.value.trim();
  if (trimmedTag && !tags.value.includes(trimmedTag)) {
    tags.value.push(trimmedTag);
    updateTags();
  } else if (tags.value.includes(trimmedTag)) {
    alert("Duplicate tags are not allowed!");
  }
  inputVisible.value = false;
  inputValue.value = '';
};
</script>

<style scoped>
.tag-input-container {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
}
.tag-input-field {
  border: none;
  outline: none;
  width: 5rem;
}
.button-new-tag {
  font-weight: bold;
}
</style>
