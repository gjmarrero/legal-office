<script setup>
import { onMounted, ref, computed } from 'vue';
import { VuePdf } from 'vue3-pdfjs';

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

const rotation = ref(0)

const rotationStyle = computed(() => ({
    transform: `rotate(${rotation.value}deg)`,
    transformOrigin: 'center',
}))

function rotatePdf() {
    rotation.value = (rotation.value + 90) % 360
}
onMounted(() => {
    console.log("pdf source", props.document_source)
    openModal()
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
                <div class="modal-body document-div">
                    <a @click.prevent="rotatePdf">
                        <font-awesome-icon icon="fa-solid fa-rotate-right" class="mr-2" />
                    </a>
                    <!-- <a @click.prevent="zoomIn">
                        <font-awesome-icon icon="fa-solid fa-search-plus" class="mr-2" />
                    </a>
                    <a @click.prevent="zoomOut">
                        <font-awesome-icon icon="fa-solid fa-search-minus" class="mr-2" />
                    </a> -->
                    <a :href="previewSource" download>
                        <font-awesome-icon icon="fa-solid fa-download" class="mr-2" />
                    </a>
                    <!-- <VuePdfEmbed :src="'http://192.168.6.221:8000/storage/uploads/outgoing/1746515747_OPAG_APTFB_HGDG.pdf'" /> -->
                    <div :style="rotationStyle">
                        <VuePdf :src="document_source" />
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>
<style lang="css" scoped>
    .document-div {
        height: 410px;
        overflow: scroll;
    }
</style>
