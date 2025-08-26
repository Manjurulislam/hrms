<template>
  <div class="floating-label-input">
    <label :class="{ 'float-up': isFocused || inputValue }">{{ label }}</label>
    <el-input
        v-model="inputValue"
        :placeholder="isFocused ? '' : placeholder"
        :error-messages="errorMessage"
        clearable
        @focus="handleFocus"
        @blur="handleBlur"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  label: {
    type: String,
    required: true,
  },
  placeholder: {
    type: String,
    default: '',
  },
});

const inputValue = ref('');
const isFocused = ref(false);

const handleFocus = () => {
  isFocused.value = true;
};

const handleBlur = () => {
  isFocused.value = false;
};
</script>

<style scoped>
.floating-label-input {
  position: relative;
  width: 100%;
}
.floating-label-input label {
  position: absolute;
  top: 50%;
  left: 12px;
  transform: translateY(-50%);
  transition: top 0.3s cubic-bezier(0.4, 0, 0.2, 1), font-size 0.3s cubic-bezier(0.4, 0, 0.2, 1), color 0.3s ease;
  color: #888;
  pointer-events: none;
  background: white; /* Makes the label blend with the input's background */
  padding: 0 5px;
  font-size: 14px;
}
.floating-label-input label.float-up {
  top: 0; /* Move label straight up */
  font-size: 12px;
  padding: 0 4px;
  color: #3f51b5; /* Customize color as needed */
}
.floating-label-input .el-input__inner {
  padding-top: 20px; /* Adjust padding to prevent overlap */
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  transition: 0.2s ease-in-out;
  background-color: transparent;
  position: relative;
}
.floating-label-input label.float-up {
  background-color: white; /* Ensures the label background covers the border */
  z-index: 1; /* Ensures the label overlaps the border */
}
</style>
