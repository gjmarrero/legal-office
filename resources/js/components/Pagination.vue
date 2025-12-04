<script setup>
import { defineProps, defineEmits, computed } from 'vue';

const props = defineProps({
    currentPage: {
        type: Number,
        required: true
    },
    lastPage: {
        type: Number,
        required: true
    },
    prevUrl: {
        type: String,
        default: null
    },
    nextUrl: {
        type: String,
        default: null
    },
    maxVisible: {   // max number of page buttons to show
        type: Number,
        default: 7
    }
});

const emit = defineEmits(['change-page']);

const pages = computed(() => {
    const pagesArr = [];

    if (props.lastPage <= props.maxVisible) {
        for (let i = 1; i <= props.lastPage; i++) {
            pagesArr.push(i);
        }
    } else {
        const start = Math.max(2, props.currentPage - 2);
        const end = Math.min(props.lastPage - 1, props.currentPage + 2);

        pagesArr.push(1); // first page

        if (start > 2) {
            pagesArr.push('...');
        }

        for (let i = start; i <= end; i++) {
            pagesArr.push(i);
        }

        if (end < props.lastPage - 1) {
            pagesArr.push('...');
        }

        pagesArr.push(props.lastPage); // last page
    }

    return pagesArr;
});

const goToPage = (page) => {
    if (page && page !== '...' && page !== props.currentPage) {
        emit('change-page', page);
    }
};
</script>

<template>
    <nav v-if="lastPage > 1" aria-label="Page navigation">
        <ul class="pagination justify-content-center flex-wrap">
            <!-- Previous Button -->
            <li class="page-item" :class="{ disabled: !prevUrl }">
                <button class="page-link" :disabled="!prevUrl" @click="goToPage(currentPage - 1)">
                    &laquo; Previous
                </button>
            </li>

            <!-- Page Numbers -->
            <li v-for="page in pages" :key="page" class="page-item"
                :class="{ active: page === currentPage, disabled: page === '...' }">
                <button v-if="page !== '...'" class="page-link" @click="goToPage(page)">
                    {{ page }}
                </button>
                <span v-else class="page-link">...</span>
            </li>

            <!-- Next Button -->
            <li class="page-item" :class="{ disabled: !nextUrl }">
                <button class="page-link" :disabled="!nextUrl" @click="goToPage(currentPage + 1)">
                    Next &raquo;
                </button>
            </li>
        </ul>
    </nav>
</template>

<style scoped>
.pagination {
    overflow-x: auto;
    white-space: nowrap;
    padding: 0.5rem 0;
}
</style>
