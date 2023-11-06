<script setup>
import axios from 'axios';
import {ref, onMounted, reactive, watch} from 'vue';
import {Form, Field, useResetForm} from 'vee-validate';
import * as yup from 'yup';
import {useToastr} from '../../toastr.js';
import ClientListItem from './ClientListItem.vue';
import {debounce} from 'lodash';
import { Bootstrap4Pagination } from 'laravel-vue-pagination';

    const toastr = useToastr();
    const clients = ref({'data': []});
    const editing = ref(false);
    const formValues = ref();
    const form = ref(null);
    // const employees = ref();

    // const getEmployees = () => {
    //     axios.get('/api/employees')
    //         .then((response) => {
    //             employees.value = response.data;
    //         });
    // }

    const getClients = (page = 1) => {
        axios.get(`/api/clients?page=${page}`, {
            params: {
                query: searchQuery.value
            }
        })
            .then((response) => {
                clients.value = response.data;
                selectedClients.value = [];            
                selectAll.value = false;
                console.log(clients.value);
            })
    }

    const createClientSchema = yup.object({
        name: yup.string().required(),
        office: yup.string().required(),
    });

    const editClientSchema = yup.object({
        name: yup.string().required(),
        office: yup.string().required(),
    });
    
    const createClient = (values, { resetForm, setErrors}) => {
        axios.post('/api/clients', values)
            .then((response) => {
                clients.value.data.unshift(response.data);
                $('#clientFormModal').modal('hide');
                resetForm();
                toastr.success('Client added successfully');
            })
            .catch((error) => {
                setErrors(error.response.data.errors);
            })
    };

    const addClient = () => {
        editing.value = false;
        $('#clientFormModal').modal('show');
    };

    const editClient = (client) => {
        editing.value = true;
        form.value.resetForm();
        $('#clientFormModal').modal('show');
        formValues.value = {
            id: client.id,
            name: client.name,
            office: client.office,            
        };
    };

    const updateClient = (values, {setErrors}) => {
        axios.put('/api/clients/' + formValues.value.id, values)
            .then((response) => {
                const index = clients.value.data.findIndex(client => client.id === response.data.id);
                clients.value.data[index] = response.data;
                $('#clientFormModal').modal('hide');
                toastr.success('Client updated successfully');
            }).catch((error) => {
                setErrors(error.response.data.errors);
                console.log(error);
            });
    }

    const handleSubmit = (values, actions) => {
        console.log(actions);
        if(editing.value){
            updateClient(values, actions);
        }else{
            createClient(values, actions);
        }
    }

    const searchQuery = ref(null);

    const selectedClients = ref([]);

    const toggleSelection = (client) => {
        const index = selectedClients.value.indexOf(client.id);
        if(index === -1) {
            selectedClients.value.push(client.id);
        }else{
            selectedClients.value.splice(index, 1);
        }
        console.log(selectedClients.value);
    }
    const clientIdBeingDeleted = ref(null);

    const confirmClientDeletion = (id) => {
        clientIdBeingDeleted.value = id;
        $('#deleteClientModal').modal('show');
    };

    const deleteClient = () => {
        axios.delete(`/api/clients/${clientIdBeingDeleted.value}`)
            .then(() => {
                $('#deleteClientModal').modal('hide');
                toastr.success('Client deleted successfully');
                clients.value.data = clients.value.data.filter(client => client.id !== clientIdBeingDeleted.value);
            });
    };
    const bulkDelete = () => {
        axios.delete('/api/clients', {
            data: {
                ids: selectedClients.value
            }
        })
        .then(response => {
            clients.value.data = clients.value.data.filter(client => !selectedClients.value.includes(client.id));
            selectedClients.value = [];
            selectAll.value = false;
            toastr.success(response.data.message);
        });
    };

    const selectAll = ref(false);

    const selectAllClients = () => {
        if(selectAll.value){
            selectedClients.value = clients.value.data.map(client => client.id);
        }else{
            selectedClients.value = [];
        }
    }

    watch(searchQuery, debounce(() => {
        getClients();
    },300));

    onMounted(() => {
        getClients();
        // getEmployees();
    });

</script>
<template>
    <div class="content-header">
               <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Clients</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Clients</li>
                            </ol>
                        </div>
                    </div>
               </div>
           </div>

           <div class="content">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between">
                        <div class="d-flex">
                            <button type="button" class="mb-2 btn btn-primary" @click="addClient">
                                <i class="fa fa-plus-circle mr-1"></i> Add New Client
                            </button>
                            <div v-if="selectedClients.length > 0">
                                <button type="button" class="ml-2 mb-2 btn btn-danger" @click="bulkDelete">
                                    <i class="fa fa-trash mr-1"></i> Delete Selected
                                </button>
                                <span class="ml-2">Selected {{  selectedClients.length }} users</span>
                            </div>
                        </div>
                        <div>
                            <input type="text" v-model="searchQuery" class="form-control" placeholder="Search"/>
                            
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" v-model="selectAll" @change="selectAllClients" /></th>
                                    <th style="width:10px">#</th>
                                    <th>Name</th>
                                    <th>Office</th>
                                    <th>Options</th>
                                </tr>
                            </thead>
                            <tbody v-if="clients.data.length > 0">
                                <ClientListItem v-for="(client, index) in clients.data" 
                                    :key="client.id"
                                    :client=client
                                    :index=index
                                    @edit-client="editClient"
                                    @confirm-client-deletion="confirmClientDeletion"
                                    @toggle-selection="toggleSelection"
                                    :select-all="selectAll"                            
                                />
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td colspan="6" class="text-center">No results found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Bootstrap4Pagination :data="clients" @pagination-change-page="getClients" />
                </div>
           </div>

            <div class="modal fade" id="clientFormModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">
                            <span v-if="editing">Edit Client</span>
                            <span v-else>Add New Client</span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>

                    <Form ref="form" @submit="handleSubmit" :validation-schema="editing ? editClientSchema : createClientSchema" v-slot="{ errors }" :initial-values="editing ? formValues : form">
                      <div class="modal-body">
                            <div class="form-group">
                                <label for="exampleFormControlInput1">Client Name</label>
                                <Field name="name" type="text" class="form-control" :class="{'is-invalid':errors.name}" id="name" />
                                <span class="invalid-feedback">{{ errors.name }}</span>
                            </div>
                            <div class="form-group">
                                <label for="exampleFormControlInput1">Office</label>
                                <Field name="office" type="office" class="form-control" :class="{'is-invalid':errors.office}" id="office" />
                                <span class="invalid-feedback">{{ errors.office }}</span>
                            </div>
                      </div>                    
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                      </div>
                    </Form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="deleteClientModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">
                            <span>Delete Client</span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        </div>

                    <div class="modal-body">
                        <h5>Are you sure you want to delete this client?</h5>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button @click.prevent="deleteClient" type="button" class="btn btn-primary">Delete</button>
                    </div>
                    </div>
                </div>
            </div>
</template>