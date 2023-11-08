<script setup>
import axios from 'axios';
import { onMounted, ref, reactive, h } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToastr } from '../../toastr';
import { useAuthUserStore } from '../../stores/AuthUserStore';
// import { VuePDF, usePDF } from '@tato30/vue-pdf'
import VuePdfEmbed from 'vue-pdf-embed';

    const authUserStore = useAuthUserStore();

    const router = useRouter();

    const route = useRoute();

    const transactions = ref({'data': []});

    const doc = reactive({});

    const toastr = useToastr();

    const getTransactions = () => {
        axios.get(`/api/documents/transactions/${route.params.id}`)
            .then((response) => {
                transactions.value = response.data;
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
            })
            
    }
    var file_preview = "/storage/uploads/documents/";

    const getFile = () => {
        axios.get(`/api/documents/file/${route.params.id}`, {responseType: 'blob'})
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
    // const preview_file = '/storage/uploads/documents/1693465980_document_file_keyboard-shortcuts-windows.pdf';
    // var preview_file = doc.document_file;

    // const { pdf, pages, info } = usePDF(preview_file);

    const getTransaction_File = (transaction) => {
        axios.get(`/api/documents/transactions/file/` + transaction.id, {responseType: 'blob'})
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
                                <p class="lead"><a href="#" @click.prevent="getFile(doc)">
                                            {{ doc.document_file }}
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
                                <vue-pdf-embed :source= "file_preview + doc.document_file" :page="1" />
                            </div>
                        </div>
                    </div>
                </div>
                <table v-if="transactions.data.length > 0" class="table table-bordered">                                
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">ID</th>
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
                            <td>{{ transaction.id }}</td>
                            <td>{{ transaction.date_assigned }}</td>
                            <td>{{ transaction.assigned_to?.emp_name }}</td>
                            <td>{{ transaction.action }}</td>                            
                            <td>
                                <a v-if="authUserStore.user.id === transaction.routed_by.id" href="#" @click.prevent="getTransaction_File(transaction)">
                                    {{ transaction.attachment }}
                                </a>
                            </td>
                            <td>
                                <span class="badge" :class="`badge-${transaction.type.color}`">{{ transaction.type.name }}</span>
                            </td>
                            <td>
                                <span class="badge" :class="`badge-${transaction.status.color}`">{{ transaction.status.name }}</span>
                            </td>
                            <td>{{ transaction.routed_by.name }}</td>
                            <!-- <td>

                                <router-link :to="`/admin/documents/transactions/${document.id}`">
                                    <i class="fa fa-eye mr-2"></i>
                                </router-link>

                                <router-link :to="`/admin/documents/${document.id}/edit`">
                                    <i class="fa fa-edit mr-2"></i>
                                </router-link>

                                <router-link :to="`/admin/documents/transact/${document.id}`">
                                    <i class="fa fa-location-arrow mr-2"></i>
                                </router-link>

                                <a href="" @click.prevent="$event => deleteDocument(document.id)">
                                    <i class="fa fa-trash text-danger"></i>
                                </a>
                            </td> -->
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>