<script setup>
import axios, { formToJSON } from 'axios';
import { onMounted, ref, reactive, h, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToastr } from '../../toastr';
import { useAuthUserStore } from '../../stores/AuthUserStore';
import TransactionModal from './TransactionModal.vue';
import DocumentButtons from './DocumentButtons.vue';
import Swal from 'sweetalert2';
import { createLoadingTask, VuePdf } from 'vue3-pdfjs';
import { Form } from 'vee-validate';

const authUserStore = useAuthUserStore();
const router = useRouter();
const route = useRoute();
const transactions = ref({ 'data': [] });
const transaction_attachments = ref({ 'data': [] });

const doc = reactive({
    id: '',
    client_name: '',
    type: '',
    date_received: '',
    title: '',
    description: '',
    document_file: '',
    status: ({
        name: '',
    }),
    document_id: '',
    last_assigned: '',
    last_transaction_type: '',
});
const document = ref({ 'data': [] });

const previewSource = ref();

const toastr = useToastr();

const main_document_path = ref('/storage/uploads/documents/');
const transaction_path = ref('/storage/uploads/transaction_documents/');
const merged_path = ref('/storage/uploads/merged/');
const merged_files = ref();

const getTransactions = () => {
    axios.get(`/api/documents/transactions/${route.params.id}`)
        .then((response) => {
            transactions.value = response.data;
            transaction_attachments.value.data = transactions.value.data.filter(transaction => (transaction.attachment !== null && transaction.attachment !== ''));
        })
}

const getDocument = () => {

    axios.get(`/api/documents/document/${route.params.id}`)
        .then((response) => {
            doc.id = response.data[0].id;
            doc.client_name = response.data[0].client.name;
            doc.type = response.data[0].type;
            doc.date_received = response.data[0].date_received;
            doc.title = response.data[0].title;
            doc.description = response.data[0].description;
            doc.document_file = response.data[0].document_file;
            doc.status = response.data[0].status;
            doc.last_assigned = response.data[0].last_assigned;
            doc.last_transaction_type = response.data[0].last_transaction_type;

            console.log('def pdf source - getDocument', main_document_path.value, doc.document_file)
            getTransactions();
            getAttachedFiles();
            getAdditionalFiles();
            // previewFile(main_document_path.value, doc.document_file);
        })

}

const numOfPages = ref(0)


//download file
// const getFile = () => {
//     axios.get(`/api/documents/file/${route.params.id}`, { responseType: 'blob' })
//         .then((response) => {
//             var fileURL = window.URL.createObjectURL(new Blob([response.data]));
//             var fileLink = document.createElement('a');

//             fileLink.href = fileURL;
//             fileLink.setAttribute('download', doc.document_file);
//             document.body.appendChild(fileLink);

//             fileLink.click();
//             fileLink.remove();
//             toastr.success('File downloaded');
//         });
// }

const getFile = (event) => {
    form.file = event.target.files[0];
}

const getFileAttachment = (event) => {
    file_form.file = event.target.files[0];
}

const getTransaction_File = (transaction) => {
    axios.get(`/api/documents/transactions/file/` + transaction.id, { responseType: 'blob' })
        .then((response) => {
            var fileURL = window.URL.createObjectURL(new Blob([response.data]));
            var fileLink = document.createElement('a');
            fileLink.href = fileURL;
            fileLink.setAttribute('download', transaction.attachment);
            document.body.appendChild(fileLink);

            fileLink.click();
            fileLink.remove();
            toastr.success('File downloaded');
        });
}


const getAttachedFiles = () => {
    return axios.get(`/api/documents/getAttachedFiles/${route.params.id}`)
        .then((response) => {
            attachedFiles.value = response.data;
            merged_files.value = response.data;
            previewSource.value = merged_path.value + merged_files.value;
        })
}

getAttachedFiles().then(() => {
    console.log('pdf source', previewSource.value)

    const loadingTask = createLoadingTask(previewSource.value)
    loadingTask.promise.then((pdf) => {
        numOfPages.value = pdf.numPages
    })
})

const additionalFiles = ref();

const getAdditionalFiles = () => {
    axios.get(`/api/documents/getAdditionalFiles/${route.params.id}`)
        .then((response) => {
            additionalFiles.value = response.data;
        })
}
const previewFile = (document_path, document_file) => {
    previewSource.value = document_path + document_file;

    console.log('new pdf_source', previewSource.value)
}

const modalVisible = ref(false);

const editTransaction = ref({});

const parentFormData = reactive({
    document_id: '',
    employee_id: '',
    action: '',
    file: null,
})

const openModal = (transaction) => {
    modalVisible.value = true;
    editTransaction.value = transaction;
    parentFormData.document_id = doc.id;
    parentFormData.employee_id = transaction.assigned_to.id;
    parentFormData.action = transaction.action;
    parentFormData.document_id = transaction.attachment;
}

const closeModal = () => {
    modalVisible.value = false;
}

const docIdBeingRouted = ref();
const routeDoc = ref(true);
const routeOutside = ref(false);
const reOpen = ref(false);

const form = reactive({
    employee_id: '',
    action: '',
    file: null,
    remarks: '',
});

const file_form = reactive({
    file: null,
});

const employees = ref();

const getEmployees = () => {
    axios.get('/api/employees')
        .then((response) => {
            employees.value = response.data;
        });
}

const routeDocument = (id) => {
    docIdBeingRouted.value = id;
    $('#moveDocumentModal').modal('show');
}

const docIdAttach = ref();

const attachFile = (id) => {
    docIdAttach.value = id;
    $('#attachFileModal').modal('show');
}


const archiveDocument = (id) => {
    docIdBeingRouted.value = id;
    Swal.fire({
        title: 'End this document?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'End'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: '',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Route',
                cancelButtonText: 'Archive'
            }).then((result) => {
                if (result.isConfirmed) {
                    routeOutside.value = true;
                    routeDoc.value = true;
                } else {
                    routeOutside.value = false;
                    routeDoc.value = false;
                }
                routeDocument(id);
            });
        }
    })
}

const clearRouteForm = () => {
    form.employee_id = '';
    form.action = '';
    form.file = null;
    form.remarks = '';
}

const createRouteDocument = () => {
    const formData = new FormData();
    formData.append('document_id', docIdBeingRouted.value);
    formData.append('employee_id', form.employee_id);
    formData.append('action', form.action);
    formData.append('document_file', form.file);

    if (routeOutside.value) {
        formData.append('routeOutside', 1);
    } else {
        formData.append('routeOutside', 0);
    }

    axios.post(`/api/documents/route`, formData)
        .then((response) => {
            $('#moveDocumentModal').modal('hide');
            clearRouteForm();
            toastr.success('Document routed successfully');
            getDocument();
            getTransactions();
        });
}

const attachedFiles = ref({ 'data': [] });

const createAttachFile = () => {
    const formData = new FormData();
    formData.append('document_id', docIdAttach.value);
    formData.append('document_file', file_form.file);

    axios.post(`/api/documents/attachfile`, formData)
        .then((response) => {
            $('#attachFileModal').modal('hide');
        })
}


const createArchiveDocument = () => {
    const formData = new FormData();
    formData.append('document_id', docIdBeingRouted.value);
    formData.append('remarks', form.remarks);

    axios.post(`/api/documents/archive/` + docIdBeingRouted.value, formData)
        .then((response) => {
            $('#moveDocumentModal').modal('hide');
            routeOutside.value = false;
            routeDoc.value = true;
            // const index = documents.value.data.findIndex(document => document.id === response.data[0].id);
            // documents.value.data[index] = response.data[0];
            // updateDocumentStatusCount(response.data[0].id);
            clearRouteForm();
            toastr.success('Document archived');
        })
}

const reopenDocument = (id) => {
    Swal.fire({
        title: 'Reopen this document?' + id,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Confirm'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(`/api/documents/reset/${id}`)
                .then((response) => {
                    reOpen.value = true;
                    routeDocument(id);
                })
        }
    })
}

const deleteDocument = (id) => {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(`/api/documents/${id}`)
                .then((response) => {
                    // updateDocumentStatusDeleteCount(id);
                    // documents.value.data = documents.value.data.filter(document => document.id !== id);
                    toastr.success('Document deleted');
                    router.push('/admin/documents');
                });
        }
    })
}

const receiveDocument = (value) => {
    if (value) {
        getTransactions();
        getDocument();
    }
}

const rotation = ref(0)

const rotationStyle = computed(() => ({
    transform: `rotate(${rotation.value}deg)`,
    transformOrigin: 'center',
}))

function rotatePdf() {
    rotation.value = (rotation.value + 90) % 360
}

const scale = ref(2.0)

function zoomIn(){
    console.log(scale.value)
    scale.value += 0.1
}

function zoomOut(){
    console.log(scale.value)
    if(scale.value > 0.2) scale.value -= 0.1
}
onMounted(() => {
    getDocument();
    getEmployees();
})
</script>
<template>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Transactions</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Documents</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">

        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body document-div">
                            <div style="float: right;">
                                <DocumentButtons :key="doc.id" v-bind:document="doc" @attach-file="attachFile"
                                    @receive-document="receiveDocument" @archive-document="archiveDocument"
                                    @route-document="routeDocument" @reopen-document="reopenDocument"
                                    @delete-document="deleteDocument" />
                            </div>

                            <p>Title: {{ doc.title }} {{ doc.last_assignment }}</p>
                            <p>Date Received: {{ doc.date_received }}</p>
                            <p>Client: {{ doc.client_name }}</p>
                            <p>Content: {{ doc.description }}</p>

                            <!-- @click.prevent="getFile(doc)" -->
                            <hr class="my-4">
                            <p class="lead">Attachments:</p>
                            <p>
                                <a href="#" @click.prevent="previewFile(main_document_path, doc.document_file)">
                                    {{ doc.document_file }}
                                </a>
                            </p>
                            <p v-for="additionalFile in additionalFiles">
                                <a href="#"
                                    @click.prevent="previewFile(main_document_path, additionalFile.document_file)">
                                    {{ additionalFile.document_file }}
                                </a>
                            </p>
                            <p v-for="file in transaction_attachments.data">
                                <a href="#" @click.prevent="previewFile(transaction_path, file.attachment)">
                                    {{ file.attachment }}
                                </a>
                            </p>

                        </div>
                    </div>
                </div>
                <div v-if="previewSource" class="col-lg-6">
                    <div class="card">
                        <div class="card-body document-div" >

                            <a @click.prevent="rotatePdf">
                                <font-awesome-icon icon="fa-solid fa-rotate-right" class="mr-2" />
                            </a>
                            <!-- <a @click.prevent="zoomIn">
                                <font-awesome-icon icon="fa-solid fa-search-plus" class="mr-2"/>
                            </a>
                            <a @click.prevent="zoomOut">
                                <font-awesome-icon icon="fa-solid fa-search-minus" class="mr-2"/>
                            </a> -->
                            <a :href="previewSource" download>
                                <font-awesome-icon icon="fa-solid fa-download" class="mr-2" />
                            </a>
                            <!-- <vue-pdf-embed :source="previewSource" :rotation="rotateLeft" :downloadable="true" /> -->
                            <!-- <VuePdf :src="previewSource" :allPages="true" /> -->
                            <div :style="rotationStyle">
                                <VuePdf v-for="page in numOfPages" :key="previewSource" :src="previewSource" :page="page"/>
                            </div>
                            
                        </div>
                    </div>
                </div>

                <table v-if="transactions.data.length > 0" class="table table-bordered">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <!-- <th scope="col">ID</th> -->
                            <th></th>
                            <th scope="col">Date</th>
                            <th scope="col">Assigned to</th>
                            <th scope="col">Action</th>
                            <th scope="col">Attachment</th>
                            <th scope="col">Transaction</th>
                            <th scope="col">Status</th>
                            <th scope="col">Remarks</th>
                            <th scope="col">Created by</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(transaction, index) in transactions.data" :key="transaction.id">
                            <td>{{ index + 1 }}</td>
                            <td>
                                <a href="#" @click="openModal(transaction)"><i class="fa fa-edit"></i></a>
                            </td>
                            <!-- <td>{{ transaction.id }}</td> -->
                            <td>{{ transaction.date_assigned }}</td>
                            <td>{{ transaction.assigned_to?.emp_name }}</td>
                            <td>{{ transaction.action }}</td>
                            <td>
                                <a v-if="authUserStore.user.id === transaction.routed_by.id" href="#"
                                    @click.prevent="previewFile(transaction_path, transaction.attachment)">
                                    {{ transaction.attachment }}

                                    <!-- "getTransaction_File(transaction)" -->
                                </a>
                            </td>
                            <td>
                                <span class="badge" :class="`badge-${transaction.type.color}`">{{ transaction.type.name
                                    }}</span>
                            </td>
                            <td>
                                <span class="badge" :class="`badge-${transaction.status.color}`">{{
                                    transaction.status.name
                                }}</span>
                            </td>
                            <td>{{ transaction.remarks }}</td>
                            <td>{{ transaction.routed_by.name }}</td>
                        </tr>
                    </tbody>
                </table>
                <TransactionModal :isVisible="modalVisible" :transaction="editTransaction" :formData="parentFormData"
                    @closeModal="closeModal" @submitForm="createRouteDocument" />
            </div>
        </div>
    </div>
    <div class="modal fade" id="moveDocumentModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">
                        <span>Route Document</span>

                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div v-if="routeDoc">
                        <Form @submit.prevent="createRouteDocument()">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="employee">Employee/Office</label>
                                        <select v-model="form.employee_id" id="employee_id" name="employee_id"
                                            class="form-control">
                                            <option v-for="employee in employees" :key="employee.id"
                                                :value="employee.id">{{
                                                    employee.emp_name }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="action">Action</label>
                                        <input v-model="form.action" type="text" class="form-control" id="action"
                                            name="action">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <input type="file" class="form-control-file" id="document_file" name="document_file"
                                    @change="getFile" />
                            </div>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </Form>
                    </div>
                    <div v-else>
                        <Form @submit.prevent="createArchiveDocument()">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="archive_remarks">Remarks</label>
                                        <input v-model="form.remarks" type="text" class="form-control" id="remarks"
                                            name="remarks">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </Form>
                    </div>
                </div>

            </div>
        </div>
    </div>

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

<style lang="css" scoped>
.document-div {
    height: 410px;
    overflow: scroll;
}
</style>