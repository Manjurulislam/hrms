<template>
  <div class="floating-label-textarea">
    <label :class="{ 'float-up': isFocused || textarea }">{{ label }}</label>
    <el-input
        v-model="textarea"
        type="textarea"
        :rows="3"
        :placeholder="isFocused ? '' : placeholder"
        clearable
        @focus="handleFocus"
        @blur="handleBlur"
        class="textarea-input"
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

const textarea = ref('');
const isFocused = ref(false);

const handleFocus = () => {
  isFocused.value = true;
};

const handleBlur = () => {
  isFocused.value = false;
};
</script>

<style scoped>
.floating-label-textarea {
  position: relative;
  width: 100%;
}

.floating-label-textarea label {
  position: absolute;
  top: 10px; /* Adjust this value for initial position */
  left: 7px; /* Space from the left */
  transition:
      top 0.3s cubic-bezier(0.4, 0, 0.2, 1),
      font-size 0.3s cubic-bezier(0.4, 0, 0.2, 1),
      color 0.3s ease;
  color: #888; /* Default label color */
  pointer-events: none;
  background: white; /* Blend with the input background */
  padding: 0 4px; /* Prevent clipping */
  font-size: 14px; /* Default label font size */
  z-index: 1; /* Ensure label is above the input */
}

.floating-label-textarea label.float-up {
  top: -8px; /* Adjust to cut into the border */
  left: 10px; /* Align with the input */
  font-size: 12px; /* Smaller font size when floating */
  color: #3f51b5; /* Floating label color */
}

.floating-label-textarea .textarea-input .el-textarea__inner {
  padding-top: 20px; /* Prevent overlap with the label */
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  transition: border-color 0.2s ease-in-out;
  background-color: transparent;
}

/* Optional: Change border color when focused */
.floating-label-textarea .textarea-input:focus-within {
  border-color: #3f51b5; /* Customize border color on focus */
}

/* Ensure label sits correctly inside the border */
.floating-label-textarea .textarea-input .el-textarea__inner:focus {
  border-color: #3f51b5; /* Border color change on focus */
}

/* To cut the border */
.floating-label-textarea .textarea-input .el-textarea__inner::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1px; /* Adjust to the height of the border */
  background: white; /* Background matches input */
  z-index: -1; /* Position behind the border */
  transition: opacity 0.3s ease-in-out;
}

.floating-label-textarea .textarea-input:focus-within::before {
  opacity: 0; /* Hide the cut line when focused */
}
</style>
