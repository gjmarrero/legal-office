<script setup>
import { onMounted, ref } from 'vue';
import VuePdfEmbed from 'vue-pdf-embed';

const props = defineProps({
    document_source: String,
});

const emits = defineEmits(['modal-stat']);

const show = ref(false);

const openModal = () => {
    $('#myModal').modal('show');
};

const closeModal = () => {
    $('#myModal').modal('hide');
    emits('modal-stat', show.value);
};

const rotateLeft = ref(0);

const rotatePdf = () => {
    rotateLeft.value += 90;
}

onMounted(() => {
    openModal();
})
</script>

<template>
    <div class="modal" tabindex="-1" id="myModal">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Document</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="closeModal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <button align-self-center @click="rotatePdf">
                        <font-awesome-icon icon="fa fa-rotate-right" />
                    </button>
                    <vue-pdf-embed :source="document_source" :rotation="rotateLeft"/>
                </div>
            </div>
        </div>
    </div>
</template>
