<script setup>

    import { onMounted, ref, computed, reactive, watch } from 'vue';
    import Swal from 'sweetalert2';
    import axios from 'axios';
    import { useToastr } from '../../toastr';
    import { Bootstrap4Pagination } from 'laravel-vue-pagination';
    import {Form, Field } from 'vee-validate';
    import { onBeforeRouteUpdate, useRoute, useRouter } from 'vue-router';
    import {debounce} from 'lodash';
    import { useAuthUserStore } from '../../stores/AuthUserStore.js';

    const authUserStore = useAuthUserStore();

    const toastr = useToastr();

    const route = useRoute();

    const router = useRouter();

    const documents = ref({'data': []});

    const searchQuery = ref(null);

    const searchbyQuery = ref('all');

    const getFile = (event) => {
        form.file = event.target.files[0];
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
    },300));

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
                                <select @change="setSearchByQuery" v-model="searchbyQuery" class="form-control px-1 rounded border-0">
                                    <option value="all">Search by...</option>
                                    <option value="subject">Subject</option>
                                    <option value="content">Content</option>
                                </select>
                            </div>
                            <div class="col-lg-3">
                                <input type="text" v-model="searchQuery" class="form-control" placeholder="Search"/>
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
                                        <th scope="col">Subject</th>
                                        <th scope="col">Content</th>
                                        <th scope="col">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(document, index) in documents.data" :key="document.id">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ document.date_dispatched }}</td>
                                        <td>{{ document.recipient }}</td>
                                        <td>{{ document.subject }}</td>
                                        <td>{{ document.content }}</td>
                                        <td>{{ document.remarks }}</td>
                                        <td>
                                            <router-link :to="`/admin/documents/transactions/${document.id}`">
                                                <i class="fa fa-eye mr-2"></i>
                                            </router-link>
                                            <!-- <a v-if="route.query.to_do === 'to-receive' || document.last_assigned === authUserStore.user.employee_id" href="#" @click.prevent="receiveDocument(document.id)">
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
                                            </span> -->
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <span v-else><p class="text-center font-weight-bold text-monospace">No data found</p></span>
                        </div>
                        <Bootstrap4Pagination :data="documents" @pagination-change-page="getDocuments" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    
</template>