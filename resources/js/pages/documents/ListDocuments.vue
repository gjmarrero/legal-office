<script setup>

import { onMounted, ref, computed, reactive, watch } from 'vue';
import Swal from 'sweetalert2';
import axios from 'axios';
import { useToastr } from '../../toastr';
import { Bootstrap4Pagination } from 'laravel-vue-pagination';
// import { SimpleBootstrap4Pagination } from 'laravel-vue-pagination';
import { Form, Field } from 'vee-validate';
import { useRoute, useRouter } from 'vue-router';
import { debounce } from 'lodash';
import { useAuthUserStore } from '../../stores/AuthUserStore.js';
import DocumentButtons from './DocumentButtons.vue';

const authUserStore = useAuthUserStore();

const toastr = useToastr();

const route = useRoute();

const router = useRouter();

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
    console.log(docIdBeingRouted);
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
            const index = documents.value.data.findIndex(document => document.id === response.data[0].id);
            documents.value.data[index] = response.data[0];
            updateDocumentStatusCount(response.data[0].id);
            clearRouteForm();
            toastr.success('Document routed successfully');
        });
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
            const index = documents.value.data.findIndex(document => document.id === response.data[0].id);
            documents.value.data[index] = response.data[0];
            updateDocumentStatusCount(response.data[0].id);
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
                    updateDocumentStatusDeleteCount(id);
                    documents.value.data = documents.value.data.filter(document => document.id !== id);
                    toastr.success('Document deleted');
                });
        }
    })
}

const updateDocumentStatusDeleteCount = (id) => {
    const deletedDocumentStatus = documents.value.data.find(document => document.id === id).status.name;
    const statusToDelete = documentStatus.value.find(status => status.name === deletedDocumentStatus);
    statusToDelete.count--;
}

const updateDocumentStatusCount = (id) => {
    const updatedDocumentStatus = documents.value.data.find(document => document.id === id).status.name;
    const statusToUpdate = documentStatus.value.find(status => status.name === updatedDocumentStatus);
    // if (statusToUpdate.name === 'ACTIVE') {
    statusToUpdate.count++;
    const statusToAdd = documentStatus.value.find(status => status.name != statusToUpdate.name);
    statusToAdd.count--;
    // }
}

const documents = ref({ 'data': [] });

const selectedStatus = ref(null);

const documentStatus = ref([]);

const document_types = ref([]);

const searchQuery = ref(null);

const searchbyQuery = ref(null);

const documentsCount = ref(0);

const documentsCountFiltered = ref(0);

const getDocumentStatus = () => {
    axios.get('/api/document-status', {
        params: {
            query_search: searchQuery.value,
            query_searchby: searchbyQuery.value
        }
    })
        .then((response) => {
            documentStatus.value = response.data;
        })
}

const getDocumentType = () => {
    axios.get('/api/document-type')
        .then((response) => {
            document_types.value = response.data;
        });
}

const getSelectedStatus = (status) => {
    if (route.name !== 'admin.documents') {
        router.push('/admin/documents');
    } else {
        if (status) {
            selectedStatus.value = status;
        } else {
            selectedStatus.value = null;
        }
        getDocuments();
    }
}

const getDocuments = (page = 1) => {
    axios.get(`/api/documents?page=${page}`, {
        params: {
            query: selectedStatus.value,
            query_searchby: searchbyQuery.value,
            query_search: searchQuery.value,
            query_doc_type: route.query.doc_type,
            query_doc_status: route.query.status,
            query_to_do: route.query.to_do,
            query_type: route.query.query_type,
        }
    })
        .then((response) => {
            documents.value = response.data;
            if (route.query.status === 'past_due') {
                if (route.query.doc_type === 'referrals') {
                    documents.value.data = documents.value.data.filter(document =>
                        (document.type.name === 'ADMIN_DOCS' && document.days_active > 3) ||
                        ((document.type.name === 'MUNICIPAL_ORDINANCE' || document.type.name === 'PROVINCIAL_ORDINANCE') && document.days_active > 7) ||
                        (document.type.name === 'CODE' && document.days_active > 10)
                    );
                } else if (route.query.doc_type === 'admin_docs') {
                    documents.value.data = documents.value.data.filter(document =>
                        document.days_active > 3
                    );
                } else if (route.query.doc_type === 'provincial' || route.query.doc_type === 'municipal' || route.query.doc_type === 'other_referral') {
                    documents.value.data = documents.value.data.filter(document =>
                        document.days_active > 7
                    );
                } else if (route.query.doc_type === 'code') {
                    documents.value.data = documents.value.data.filter(document =>
                        document.days_active > 10
                    );
                } else if (route.query.doc_type === 'administrative' || route.query.doc_type === 'judicial' || route.query.doc_type === 'quasi' || route.query.doc_type === 'cases') {
                    documents.value.data = documents.value.data.filter(document =>
                        document.days_active > 15
                    );
                }
            } else if (route.query.status === 'near_due') {
                if (route.query.doc_type === 'referrals') {
                    documents.value.data = documents.value.data.filter(document =>
                        (document.type.name === 'ADMIN_DOCS' && (document.days_active >= 1 && document.days_active <= 3)) ||
                        ((document.type.name === 'PROVINCIAL_ORDINANCE' || document.type.name === 'MUNICIPAL_ORDINANCE' || document.type.name === 'OTHER_REFERRAL') && document.days_active >= 4 && document.days_active <= 7) ||
                        (document.type.name === 'CODE' && (document.days_active >= 7 && document.days_active <= 10))
                    );
                } else if (route.query.doc_type === 'admin_docs') {
                    documents.value.data = documents.value.data.filter(document =>
                        (document.days_active >= 1 && document.days_active <= 3)
                    )
                } else if (route.query.doc_type === 'municipal' || route.query.doc_type === 'provincial' || route.query.doc_type === 'other_referral') {
                    documents.value.data = documents.value.data.filter(document =>
                        (document.days_active >= 4 && document.days_active <= 7)
                    )
                } else if (route.query.doc_type === 'code') {
                    documents.value.data = documents.value.data.filter(document =>
                        (document.days_active >= 7 && document.days_active <= 10)
                    )
                } else if (route.query.doc_type === 'cases' || route.query.doc_type === 'administrative' || route.query.doc_type === 'judicial' || route.query.doc_type === 'quasi') {
                    documents.value.data = documents.value.data.filter(document =>
                        (document.days_active >= 12 && document.days_active <= 15)
                    )
                }
            }
            documentsCount.value = documents.value.data.length;
            documentsCountFiltered.value = documents.value.data.length;
        });
}

const documentCount = computed(() => {
    return documentStatus.value.map(status => status.count).reduce((acc, value) => acc + value, 0);
});



const selectedSearchByQuery = ref('');

const setSearchByQuery = () => {
    getDocuments();
    searchQuery.value = '';
    selectedSearchByQuery.value = searchbyQuery.value;
}

watch(searchQuery, debounce(() => {
    getDocuments();
    getDocumentStatus();
}, 300));

onMounted(() => {
    getDocuments();
    getDocumentStatus();
    getDocumentType();
    getEmployees();
})
</script>
<template>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Documents</h1>
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
                            <router-link to="/admin/documents/create">
                                <button class="btn btn-primary"><i class="fa fa-plus-circle mr-1"></i> Add New
                                    Document</button>
                            </router-link>
                        </div>

                        <div class="btn-group col-lg-3" v-if="(route.fullPath === '/admin/documents')">
                            <button @click="getSelectedStatus()" type="button" class="btn"
                                :class="[typeof selectedStatus === 'undefined' ? 'btn-secondary' : 'btn-default']">
                                <span class="mr-1">All</span>
                                <span class="badge badge-pill badge-info">{{ documentCount }}</span>
                            </button>

                            <button v-if="(route.name === 'admin.documents')" v-for="status in documentStatus"
                                @click="getSelectedStatus(status.value)" type="button" class="btn"
                                :class="[selectedStatus == status.value ? 'btn-secondary' : 'btn-default']">
                                <span class="mr-1">{{ status.name }}</span>
                                <span class="badge badge-pill" :class="`badge-${status.color}`">{{ status.count
                                }}</span>
                            </button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="d-flex justify-content-md-center mb-2">
                                <div class="col-lg-3">
                                    <select @change="setSearchByQuery" v-model="searchbyQuery"
                                        class="form-control px-1 rounded border-0">
                                        <option>Search by...</option>
                                        <option value="client">Client</option>
                                        <option value="title">Title</option>
                                        <option value="description">Content</option>
                                        <option value="type">Document Type</option>
                                    </select>
                                </div>
                                <div v-if="selectedSearchByQuery === 'type'" class="col-md-3" @change="getDocuments()">
                                    <select v-model="searchQuery" class="form-control">
                                        <option v-for="document_type in document_types" :key="document_type.value"
                                            :value="document_type.value">{{ document_type.name }}</option>
                                    </select>
                                </div>
                                <div v-else class="col-lg-3">
                                    <input type="text" v-model="searchQuery" class="form-control"
                                        placeholder="Search" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <table v-if="documentsCount > 0" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <!-- <th>D</th>
                                        <th>L</th>
                                        <th>DA</th> -->
                                        <th scope="col">#</th>
                                        <th scope="col">Type</th>
                                        <th scope="col">Date Received</th>
                                        <th scope="col">Client Name</th>
                                        <th scope="col">Client Office</th>
                                        <th scope="col">Title</th>
                                        <th scope="col">Description</th>
                                        <th scope="col">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(document, index) in documents.data" :key="document.id">
                                        <!-- <td>{{ document.date_to_count }}</td>
                                        <td>{{ document.last_assigned }}</td>
                                        <td>{{ document.days_active }}</td> -->
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ document.type.name }}</td>
                                        <td>{{ document.date_received }}</td>
                                        <td>{{ document.client.name }}</td>
                                        <td>{{ document.client.office }}</td>
                                        <td>{{ document.title }}</td>
                                        <td>{{ document.description }}</td>
                                        <td>
                                            <span class="badge" :class="`badge-${document.status.color}`">{{
                                                document.status.name }}</span>
                                        </td>
                                        <td>
                                            <DocumentButtons v-bind:document="document"
                                                @archive-document="archiveDocument" @route-document="routeDocument"
                                                @reopen-document="reopenDocument" @delete-document="deleteDocument" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <span v-else>
                                <p class="text-center font-weight-bold text-monospace">No data found</p>
                            </span>
                        </div>
                        <div class="row pagination-wrapper">
                            <div class="col d-flex justifiy-content-center">
                                <Bootstrap4Pagination :data="documents" @pagination-change-page="getDocuments" />
                            </div>
                        </div>
                    </div>
                </div>
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
                        <Form @submit="createRouteDocument()">
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
                        <Form @submit="createArchiveDocument()">
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
</template>

<style lang="css" scoped>
.pagination-wrapper {
    overflow-x: auto;
    white-space: nowrap;
    padding: 0.5rem 0;
}
</style>