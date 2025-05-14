<script setup type>

import { onMounted, ref, computed, reactive, watch } from 'vue';
import Swal from 'sweetalert2';
import axios from 'axios';
import { useToastr } from '../../toastr';
import { Bootstrap4Pagination } from 'laravel-vue-pagination';
import { Form, Field } from 'vee-validate';
import { onBeforeRouteUpdate, useRoute, useRouter } from 'vue-router';
import { debounce } from 'lodash';
import { useAuthUserStore } from '../../stores/AuthUserStore.js';
import VuePdfEmbed from 'vue-pdf-embed';
import DocPreview from '../../components/DocPreview.vue';

const authUserStore = useAuthUserStore();

const toastr = useToastr();

const route = useRoute();

const router = useRouter();

const documents = ref({ 'data': [] });

const searchQuery = ref(null);

const searchbyQuery = ref('all');

// const document_path = ref('http://192.168.6.221:8000/storage/uploads/outgoing/');
// const document_path = ref(`${import.meta.env.VITE_APP_URL}/storage/uploads/outgoing/`)

const main_document_path = '/storage/uploads/outgoing/';
const additional_path = '/storage/uploads/outgoing_documents/';

const document_source = ref(null);

const show = ref(false);

const handleValue = (value) => {
    show.value = value;
};

const getFile = (document_path,document_attachment) => {
    
    document_source.value = document_path+document_attachment;
    show.value = true;
}

const getDocuments = (page = 1) => {
    axios.get(`/api/outgoing_documents?page=${page}`, {
        params: {
            query_searchby: searchbyQuery.value,
            query_search: searchQuery.value,
        }
    })
        .then((response) => {
            documents.value = response.data;
        });
}


const file_form = reactive({
    file: null,
});

const docIdAttach = ref(null)

const attachFile = (document_id) => {
    docIdAttach.value = document_id;
    $('#attachFileModal').modal('show');
}

const getFileAttachment = (event) => {
    console.log('file attached')
    file_form.file = event.target.files[0];
}

const createAttachFile = () => {
    const formData = new FormData();
    formData.append('document_id', docIdAttach.value);
    formData.append('document_file', file_form.file);

    axios.post(`/api/outgoing/attach_file`, formData)
        .then((response) => {
            $('#attachFileModal').modal('hide');
            getDocuments()
        })    
}

// const deleteDocument = (id) => {
//     Swal.fire({
//         title: 'Are you sure?',
//         text: "You won't be able to revert this!",
//         icon: 'warning',
//         showCancelButton: true,
//         confirmButtonColor: '#3085d6',
//         cancelButtonColor: '#d33',
//         confirmButtonText: 'Yes, delete it!'
//         }).then((result) => {
//         if (result.isConfirmed) {
//             axios.delete(`/api/documents/${id}`)
//                 .then((response) => {
//                     updateDocumentStatusDeleteCount(id);
//                     documents.value.data = documents.value.data.filter(document => document.id !== id);
//                     Swal.fire(
//                     'Deleted!',
//                     'Your file has been deleted.',
//                     'success'
//                     )
//                 });
//         }
//     })
// }

// const archiveDocument = (id) => {
//     Swal.fire({
//         title: 'Archive this document?',
//         icon: 'warning',
//         showCancelButton: true,
//         confirmButtonColor: '#3085d6',
//         cancelButtonColor: '#d33',
//         confirmButtonText: 'Archive'
//     }).then((result) => {
//         if(result.isConfirmed){
//             axios.post(`/api/documents/archive/${id}`)
//                 .then((response) => {
//                     Swal.fire(
//                         'Archived',
//                         'success'
//                     )
//                     const index = documents.value.data.findIndex(document => document.id === id);
//                     documents.value.data[index].status.name = 'ARCHIVED';
//                     documents.value.data[index].status.color = 'success';
//                     documents.value.data = documents.value.data.filter(document => document.id !== id);
//                     updateDocumentStatusCount(id);
//                 });
//         }
//     })
// }

const employees = ref();

const selectedSearchByQuery = ref('');

const setSearchByQuery = () => {
    getDocuments();
    searchQuery.value = '';
    selectedSearchByQuery.value = searchbyQuery.value;
}

watch(searchQuery, debounce(() => {
    getDocuments();
}, 300));

onMounted(() => {
    getDocuments();
})
</script>
<template>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Outgoing Documents</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item">
                            <router-link to="/admin/dashboard">Home</router-link>
                        </li>
                        <li class="breadcrumb-item active">Documents</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex justify-content-between mb-2">
                        <div class="col-lg-3">
                            <router-link to="/admin/outgoing/create">
                                <button class="btn btn-primary"><i class="fa fa-plus-circle mr-1"></i> Add New
                                    Document</button>
                            </router-link>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="d-flex justify-content-md-center mb-2">
                                <div class="col-lg-3">
                                    <select @change="setSearchByQuery" v-model="searchbyQuery"
                                        class="form-control px-1 rounded border-0">
                                        <option value="all">Search by...</option>
                                        <option value="subject">Subject</option>
                                        <option value="content">Content</option>
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <input type="text" v-model="searchQuery" class="form-control"
                                        placeholder="Search" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <table v-if="documents.data.length > 0" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Date Dispatched</th>
                                        <th scope="col">Recipient</th>
                                        <th scope="col">Recipient Office</th>
                                        <th scope="col">Subject</th>
                                        <th scope="col">Content</th>
                                        <th class="col-lg-3" scope="col">Attachment</th>
                                        <th scope="col">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(document, index) in documents.data" :key="document.id">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ document.date_dispatched }}</td>
                                        <td>{{ document.recipient }}</td>
                                        <td>{{ document.recipient_office }}</td>
                                        <td>{{ document.subject }}</td>
                                        <td>{{ document.content }}</td>
                                        <td class="col-lg-3">
                                            <a href="#" @click.prevent="getFile(main_document_path,document.attachment)">
                                                <p>{{ document.attachment }}</p>
                                            </a>

                                            <a v-for="(attachment, index) in document.additional_attachments" href="#" @click.prevent="getFile(additional_path,attachment.document_file)">
                                                <p>{{ attachment.document_file }}</p>
                                            </a>
                                        </td>
                                        <td>
                                            {{ document.remarks }}
                                        </td>
                                        <td>
                                            <router-link :to="`/admin/outgoing/${document.id}/edit`">
                                                <i class="fa fa-edit mr-2"></i>
                                            </router-link>

                                            <a v-if="(route.name != 'admin.documents')" href="#"
                                                @click.prevent="attachFile(document.id)">
                                                <font-awesome-icon icon="fa-solid fa-paperclip" class="mr-2" />
                                            </a>
                                        </td>
                                        <!-- <td>
                                            <router-link :to="`/admin/documents/transactions/${document.id}`">
                                                <i class="fa fa-eye mr-2"></i>
                                            </router-link>
                                            <a v-if="route.query.to_do === 'to-receive' || document.last_assigned === authUserStore.user.employee_id" href="#" @click.prevent="receiveDocument(document.id)">
                                                <font-awesome-icon icon="fa fa-circle-down" class="mr-2" />
                                            </a>
                                            <a v-if="(document.status.name === 'ACTIVE' && authUserStore.user.role === 'ADMIN') || route.query.to_do === 'to-receive'" href="" @click.prevent="$event => archiveDocument(document.id)">
                                                <i class="fa fa-archive mr-2"></i>
                                            </a>
                                            <a v-if="(authUserStore.user.role === 'ADMIN' || route.query.to_do === 'to-release' || document.last_assigned === authUserStore.user.employee_id) && (document.status.name === 'ACTIVE')" href="#" @click.prevent="routeDocument(document.id)">
                                                <font-awesome-icon icon="fa fa-location-arrow" class="mr-2" />
                                            </a>
                                            <span v-if="authUserStore.user.role === 'ADMIN'">
                                                <router-link  :to="`/admin/documents/${document.id}/edit`">
                                                    <i class="fa fa-edit mr-2"></i>
                                                </router-link>
                                                <a href="#" v-if="(document.status.name === 'ARCHIVED')" @click.prevent="reopenDocument(document.id)">
                                                    <i class="fa fa-folder-open mr-2"></i>
                                                </a>

                                                <a href="" @click.prevent="$event => deleteDocument(document.id)">
                                                    <i class="fa fa-trash text-danger mr-2"></i>
                                                </a>
                                            </span>
                                        </td> -->
                                    </tr>
                                </tbody>
                            </table>
                            <span v-else>
                                <p class="text-center font-weight-bold text-monospace">No data found</p>
                            </span>
                        </div>
                        <Bootstrap4Pagination :data="documents" @pagination-change-page="getDocuments" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-if="show">
        <DocPreview v-bind:document_source="document_source" @modal-stat="handleValue" />
    </div>
    <!-- <div class="modal" tabindex="-1" id="myModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Document</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="closeModal">
                <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <vue-pdf-embed :source="document_source" />
          </div>
        </div>
      </div>
    </div> -->

    <div class="modal fade" id="attachFileModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">
                        <span>Attach File</span>

                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div>
                        <Form @submit="createAttachFile()">
                            <div class="form-group">
                                <input type="file" class="form-control-file" id="document_file" name="document_file"
                                    @change="getFileAttachment" />
                            </div>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </Form>
                    </div>
                </div>

            </div>
        </div>
    </div>

</template>