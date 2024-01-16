<script setup>
import axios, { formToJSON } from 'axios';
import { onMounted, ref, reactive, h } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToastr } from '../../toastr';
import { useAuthUserStore } from '../../stores/AuthUserStore';
import VuePdfEmbed from 'vue-pdf-embed';
import TransactionModal from './TransactionModal.vue';

const authUserStore = useAuthUserStore();

const router = useRouter();

const route = useRoute();

const transactions = ref({ 'data': [] });

const transaction_attachments = ref({'data':[]});

const doc = reactive({
    id: '',
    client_name: '',
    type: '',
    date_received: '',
    title: '',
    description: '',
    document_file: '',
});

const previewSource = ref();

const toastr = useToastr();

const main_document_path = ref('/storage/uploads/documents/');
const transaction_path = ref('/storage/uploads/transaction_documents/');

const getTransactions = () => {
    axios.get(`/api/documents/transactions/${route.params.id}`)
        .then((response) => {
            transactions.value = response.data;
            transaction_attachments.value.data = transactions.value.data.filter(transaction => (transaction.attachment !== null && transaction.attachment !== '') );
        })
}

const getDocument = () => {
    axios.get(`/api/documents/document/${route.params.id}`)
        .then((response) => {
            doc.id = response.data.id;
            doc.client_name = response.data.client.name;
            doc.type = response.data.type;
            doc.date_received = response.data.date_received;
            doc.title = response.data.title;
            doc.description = response.data.description;
            doc.document_file = response.data.document_file;
            previewFile(main_document_path.value, doc.document_file);
        })
}

//download file
const getFile = () => {
    axios.get(`/api/documents/file/${route.params.id}`, { responseType: 'blob' })
        .then((response) => {
            var fileURL = window.URL.createObjectURL(new Blob([response.data]));
            var fileLink = document.createElement('a');

            fileLink.href = fileURL;
            fileLink.setAttribute('download', doc.document_file);
            document.body.appendChild(fileLink);

            fileLink.click();
            fileLink.remove();
            toastr.success('File downloaded');
        });
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

const rotateLeft = ref(0);

const rotatePdf = () => {
    rotateLeft.value += 90;
}

const previewFile = (document_path, document_file) => {
    previewSource.value = document_path + document_file;
    console.log("File:" + previewSource.value);
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

const test = (attachment) => {
    alert(attachment);
}

onMounted(() => {
    getDocument();
    getTransactions();
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
                        <div class="card-body">
                            <div class="jumbotron">
                                <p class="lead">Title: {{ doc.title }}</p>
                                <p class="lead">Date Received: {{ doc.date_received }}</p>
                                <p class="lead">Client: {{ doc.client_name }}</p>
                                <p class="lead"><a href="#"
                                        @click.prevent="previewFile(main_document_path, doc.document_file)">
                                        {{ doc.document_file }}
                                        <!-- <span v-for="file in transactions.data" @click="test(file.attachment)">{{ file.attachment }}</span> -->
                                        <ul>
                                            <li v-for="file in transaction_attachments.data" @click="previewFile(transaction_path, file.attachment)">{{ file.attachment }}</li>
                                        </ul>
                                        <!-- @click.prevent="getFile(doc)" -->
                                    </a></p>
                                <hr class="my-4">
                                <p>Content: {{ doc.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="jumbotron" style="overflow: scroll; height:385px;">
                                <!-- <VuePDF :pdf="pdf" height="50px"/> -->
                                <button align-self-center @click="rotatePdf">
                                    <font-awesome-icon icon="fa fa-rotate-right" />
                                </button>
                                <vue-pdf-embed :source="previewSource" :page="1" :rotation="rotateLeft" />
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
                                <span class="badge" :class="`badge-${transaction.status.color}`">{{ transaction.status.name
                                }}</span>
                            </td>
                            <td>{{ transaction.routed_by.name }}</td>
                        </tr>
                    </tbody>
                </table>
                <TransactionModal :isVisible="modalVisible" :transaction="editTransaction" :formData="parentFormData"
                    @closeModal="closeModal" @submitForm="createRouteDocument" />
            </div>
        </div>
    </div>
</template>