<script setup>
import {ref} from 'vue'
import {router} from '@inertiajs/vue3'
import {useToast} from "vue-toastification"

const toast = useToast()
const props = defineProps({
    route: {
        type: String,
        required: true
    },
    title: {
        type: String,
        default: 'Delete Item'
    },
    message: {
        type: String,
        default: 'Are you sure you want to delete this item? This action cannot be undone.'
    },
    itemName: {
        type: String,
        default: ''
    }
})

const showDialog = ref(false)
const isDeleting = ref(false)

const openDialog = (event) => {
    event.preventDefault()
    event.stopPropagation()
    showDialog.value = true
}

const confirmDelete = () => {
    isDeleting.value = true

    router.delete(props.route, {
        preserveState: false,
        onSuccess: () => {
            toast.success(`${props.itemName || 'Item'} has been deleted successfully.`)
            showDialog.value = false
        },
        onError: (errors) => {
            toast.error('Failed to delete item. Please try again.')
            console.error('Delete errors:', errors)
        },
        onFinish: () => {
            isDeleting.value = false
        }
    })
}

const cancelDelete = () => {
    showDialog.value = false
}
</script>

<template>
    <!-- Trigger Element -->
    <div class="d-flex align-center w-100 cursor-pointer" @click="openDialog">
        <slot>
            <v-icon class="mr-2" color="error" size="14">mdi-delete</v-icon>
            <span class="text-xs text-error">Delete</span>
        </slot>
    </div>

    <!-- Confirmation Dialog -->
    <v-dialog v-model="showDialog" max-width="400px" persistent>
        <v-card>
            <v-card-title class="d-flex align-center">
                <v-icon class="mr-2" color="error">mdi-alert-circle</v-icon>
                <span class="text-h6">{{ title }}</span>
            </v-card-title>

            <v-card-text>
                <div class="py-2">
                    {{ message }}
                    <div v-if="itemName" class="mt-2">
                        <strong class="text-error">"{{ itemName }}"</strong>
                    </div>
                </div>
            </v-card-text>

            <v-card-actions class="px-6 pb-4">
                <v-spacer></v-spacer>
                <v-btn
                    :disabled="isDeleting"
                    color="grey"
                    variant="outlined"
                    @click="cancelDelete"
                >
                    Cancel
                </v-btn>
                <v-btn
                    :loading="isDeleting"
                    color="error"
                    variant="elevated"
                    @click="confirmDelete"
                >
                    Delete
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}
</style>
