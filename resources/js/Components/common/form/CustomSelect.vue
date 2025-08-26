<template>
  <div class="floating-label-select">
    <label :class="{ 'float-up': isFocused || value }">{{ label }}</label>
    <el-select
        v-model="value"
        clearable
        @focus="handleFocus"
        @blur="handleBlur"
        placeholder=" "
        style="width: 100%; height: 42px;"
    >
    <el-option
        v-for="item in options"
        :key="item.id"
        :label="item.label"
        :item-title="item.title_en"
        :value="item.id"
    />
    </el-select>
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const props = defineProps({
  label: {
    type: String,
    required: true,
  },
  options: {
    type: Array,
    required: true,
  },
});

const value = ref(''); // This will store the selected option's ID
const isFocused = ref(false);

const handleFocus = () => {
  isFocused.value = true;
};

const handleBlur = () => {
  isFocused.value = false;
};
</script>

<style scoped>
.floating-label-select {
  position: relative;
  margin-bottom: 20px; /* Space below the input */
}

.floating-label-select label {
  position: absolute;
  top: 12px; /* Position label inside select box */
  left: 12px; /* Left padding for label */
  transition: 0.2s ease-in-out;
  color: #888; /* Placeholder color */
  pointer-events: none; /* Prevent label from being interactive */
  background-color: white; /* Background to cover input */
  padding: 0 5px; /* Padding for the label */
  font-size: 14px; /* Normal font size */
  z-index: 1; /* Ensure label is above other elements */
}

.floating-label-select label.float-up {
  top: -8px; /* Adjust this to control the height of floating label */
  left: 10px; /* Adjust left position for floating label */
  font-size: 12px; /* Smaller font size when floating */
  color: #3f51b5; /* Color for floating label */
}


.el-select__wrapper {
  height: 42px ! important;
}

</style>
