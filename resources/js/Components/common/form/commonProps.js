export const commonProps = {
    label: {
        type: String,
        required: true,
    },
    prefix: {
        type: String,
        required: false,
    },
    required: {
        type: Boolean,
        required: false,
        default: false
    },
    modelValue: {
        type : [String, Number],
        default: ''
    },
    errorMessage: {
        type: String,
        required: false,
    },
    disabled: {
        type: Boolean,
        required: false,
        default: false
    }
}
