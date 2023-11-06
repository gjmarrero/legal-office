<script setup>
    import axios from 'axios';
    import { reactive, onMounted, ref } from 'vue';
    import { useRouter, useRoute } from 'vue-router';
    import { useToastr } from '../../toastr';
    import { Form } from 'vee-validate';
    import flatpickr from "flatpickr";
    import 'flatpickr/dist/themes/light.css';

    const router = useRouter();

    const route = useRoute();

    const toastr = useToastr();

    const form = reactive({
        client_id: '',
        type: '',
        date_received: '',
        title: '',
        description: '',
        remarks: '',
        file: null,

    });

    const getFile = (event) => {
        form.file = event.target.files[0];
    }

    const createDocument = (values, actions) => {
            const formData = new FormData();
            formData.append('document_file', form.file);
            formData.append('client_id', form.client_id);
            formData.append('type', form.type);
            formData.append('date_received', form.date_received);
            formData.append('title', form.title);
            formData.append('description', form.description);
            formData.append('remarks', form.remarks);

            axios.post('/api/documents/create', formData)
                .then((response) => {
                    router.push('/admin/documents');
                    toastr.success('Document created');
                })
                .catch((error) => {
                    actions.setErrors(error.response.data.errors);
                })
        
    }

    const handleSubmit = (values, actions) => {
        if(editMode.value){
            editDocument(values, actions);
        }else{
            createDocument(values, actions);
        }        
    }

    // const createDocument = (values, actions) => {
    //     axios.post('/api/documents/create', form)
    //         .then((response) => {
    //             router.push('/admin/documents');
    //             toastr.success('Document created');
    //         })
    //         .catch((error) => {
    //             actions.setErrors(error.response.data.errors);
    //         })
    // }

    const editDocument = (values, actions) =>{
        axios.put(`/api/documents/${route.params.id}/edit`, form)
            .then((response) => {
                router.push('/admin/documents');
                toastr.success('Document edited');
            })
            .catch((error) => {
                actions.setErrors(error.response.data.errors);
            })
    }

    const clients = ref();

    const getClients = () => {
        axios.get('/api/clients')
            .then((response) => {
                clients.value = response.data;
            });
    }

    const getDocument = () => {
        axios.get(`/api/documents/${route.params.id}/edit`)
            .then(({data}) => {
                form.date_received = data.formatted_date_received;
                form.client_id = data.client_id;
                form.type = data.type;
                form.title = data.title;
                form.description = data.description;
                form.remarks = data.remarks;
            })
    }

    const editMode = ref(false);

    onMounted(() => {
        if(route.name === 'admin.documents.edit'){
            editMode.value = true;
            getDocument();    
        }
        flatpickr(".flatpickr", {
            dateFormat: "Y-m-d",
        });

        getClients();
    })
</script>

<template>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <span v-if="editMode">Edit</span>
                        <span v-else>Create</span>
                        Document
                    </h1>
                </div>
                
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item">
                            <router-link to="/admin/dashboard">Home</router-link>
                        </li>
                        <li class="breadcrumb-item">
                            <router-link to="/admin/documents">Documents</router-link>
                        </li>
                        <li class="breadcrumb-item active">
                            <span v-if="editMode">Edit</span>
                            <span v-else>Create</span>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <Form @submit="handleSubmit" v-slot:default="{ errors }">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="date">Date Received</label>
                                            <input v-model="form.date_received" type="text" class="form-control flatpickr" :class="{'is-invalid': errors.date_received}" id="date_received">
                                            <span class="invalid-feedback">{{ errors.date_received }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="client">Client Name</label>
                                            <select v-model="form.client_id" id="client_id" class="form-control" :class="{'is-invalid': errors.client_id}">
                                                <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                                            </select>
                                            <span class="invalid-feedback">{{ errors.client_id }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="type">Type</label>
                                            <select v-model="form.type" id="type" class="form-control" :class="{'is-invalid': errors.type}">
                                                <option>Type One</option>
                                                <option>Type Two</option>
                                            </select>
                                            <span class="invalid-feedback">{{ errors.type }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="title">Title</label>
                                            <input v-model="form.title" type="text" class="form-control" :class="{'is-invalid':errors.title}" id="title" placeholder="Enter Title">
                                            <span class="invalid-feedback">{{ errors.title }}</span>
                                        </div>
                                    </div>                                    
                                </div>                                
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea v-model="form.description" class="form-control" :class="{'is-invalid':errors.title}" id="description" rows="3"
                                        placeholder="Enter Description"></textarea>
                                    <span class="invalid-feedback">{{ errors.description }}</span>
                                </div>
                                <div class="form-group">
                                    <label for="remarks">Remarks</label>
                                    <textarea class="form-control" id="remarks" rows="3"
                                        placeholder="Enter Remarks"></textarea>
                                </div>
                                <div class="form-group">
                                    <input type="file" class="form-control-file" id="document_file" name="document_file" @change="getFile"/>
                                </div>
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </Form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>