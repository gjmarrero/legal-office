<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';
import { useAuthUserStore } from '../../stores/AuthUserStore';
import Swal from 'sweetalert2';
import { useToastr } from '../../toastr';

const router = useRouter();
const route = useRoute();
const authUserStore = useAuthUserStore();
const toastr = useToastr();

const props = defineProps({
    document: Object,
});

const emits = defineEmits(['attach-file', 'receive-document', 'archiveDocument', 'routeDocument', 'reopenDocument', 'deleteDocument']);

const receivedDoc = ref(false);

const receiveDocument = (id) => {
    Swal.fire({
        title: 'Receive this document?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Receive'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(`/api/documents/receive/${id}`)
                .then((response) => {
                    toastr.success('Document received');
                    if (route.name === "admin.documents") {
                        documents.value.data = documents.value.data.filter(document => document.id !== id);
                        documentsCount.value = documents.value.data.length;
                    }
                });
            receivedDoc.value = true;
            emits('receive-document', receivedDoc.value);
        }
    })
}

</script>

<template>
    <router-link :to="`/admin/documents/transactions/${document.id}`">
        <i v-if="(route.name != 'admin.documents.transactions')" class="fa fa-eye mr-2"></i>
    </router-link>
    <a v-if="(route.name != 'admin.documents')"
        href="#" @click.prevent="$emit('attachFile',document.id)">
        <font-awesome-icon icon="fa-solid fa-paperclip" class="mr-2"/>
    </a>
    
    <a v-if="(route.query.to_do === 'to-receive' || document.last_assigned === authUserStore.user.employee_id) && document.last_transaction_type == null"
        href="#" @click.prevent="receiveDocument(document.id)">
        <font-awesome-icon icon="fa fa-circle-down" class="mr-2" />
    </a>
    <a v-if="(document.status.name === 'ACTIVE' && authUserStore.user.role === 'ADMIN') || route.query.to_do === 'to-receive' || route.query.to_do === 'to-release'"
        href="" @click.prevent="$emit('archiveDocument', document.id)">
        <i class="fa fa-archive mr-2"></i>
    </a>
    <a v-if="(authUserStore.user.role === 'ADMIN' || route.query.to_do === 'to-release' || document.last_assigned === authUserStore.user.employee_id) && (document.status.name === 'ACTIVE')"
        href="#" @click.prevent="$emit('routeDocument', document.id)">
        <font-awesome-icon icon="fa fa-location-arrow" class="mr-2" />
    </a>
    <span v-if="authUserStore.user.role === 'ADMIN'">
        <router-link :to="`/admin/documents/${document.id}/edit`">
            <i class="fa fa-edit mr-2"></i>
        </router-link>
        <a href="#" v-if="(document.status.name === 'ARCHIVED')" @click.prevent="$emit('reopenDocument', document.id)">
            <i class="fa fa-folder-open mr-2"></i>
        </a>

        <a href="" @click.prevent="$emit('deleteDocument', document.id)">
            <i class="fa fa-trash text-danger mr-2"></i>
        </a>
    </span>
</template>