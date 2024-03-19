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

const saving = ref(false);

const form = reactive({
    client_id: '',
    document_type: '',
    date_received: '',
    title: '',
    description: '',
    remarks: '',
    file: null,
    status: '',
});

const document_types = ref();

const getDocumentType = () => {
    axios.get('/api/document-type')
        .then((response) => {
            document_types.value = response.data;
        })
}

const getFile = (event) => {
    form.file = event.target.files[0];
}

const createDocument = (values, actions) => {
    const formData = new FormData();
    formData.append('document_file', form.file);
    formData.append('client_id', form.client_id);
    formData.append('document_type', form.document_type);
    formData.append('date_received', form.date_received);
    formData.append('title', form.title);
    formData.append('description', form.description);
    formData.append('remarks', form.remarks);
    saving.value = true;
    axios.post('/api/documents/create', formData)
        .then((response) => {
            router.push('/admin/documents');
            toastr.success('Document created');
        })
        .catch((error) => {
            actions.setErrors(error.response.data.errors);
        })
        .finally(() => {
            saving.value = false;
        })

}

const handleSubmit = (values, actions) => {
    if (editMode.value) {
        editDocument(values, actions);
    } else {
        createDocument(values, actions);
    }
}

const editDocument = (values, actions) => {
    const formData = new FormData();
    formData.append('document_file', form.file);
    formData.append('client_id', form.client_id);
    formData.append('type', form.document_type);
    formData.append('date_received', form.date_received);
    formData.append('title', form.title);
    formData.append('description', form.description);
    formData.append('remarks', form.remarks);
    saving.value = true;
    axios.post(`/api/documents/${route.params.id}/edit`, formData)
        .then((response) => {
            router.push('/admin/documents');
            toastr.success('Document edited');
        })
        .catch((error) => {
            actions.setErrors(error.response.data.errors);
        })
        .finally(() => {
            saving.value = false;
        })
}

const clients = ref();

const getClients = () => {
    axios.get('/api/clients/get_clients')
        .then((response) => {
            clients.value = response.data;
        });
}

const getDocument = () => {
    axios.get(`/api/documents/${route.params.id}/edit`)
        .then(({ data }) => {
            form.date_received = data.date_received;
            form.client_id = data.client_id;
            form.document_type = data.type;
            form.title = data.title;
            form.description = data.description;
            form.remarks = data.remarks;
        })
}

const editMode = ref(false);

onMounted(() => {
    if (route.name === 'admin.documents.edit') {
        editMode.value = true;
        getDocument();
    }
    flatpickr(".flatpickr", {
        dateFormat: "Y-m-d",
    });

    getClients();
    getDocumentType();
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
                                <input type="hidden" name="_method" value="PUT">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="date">Date Received</label>
                                            <input v-model="form.date_received" type="text" class="form-control flatpickr"
                                                :class="{ 'is-invalid': errors.date_received }" id="date_received">
                                            <span class="invalid-feedback">{{ errors.date_received }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="client">Client Name</label>
                                            <select v-model="form.client_id" id="client_id" class="form-control"
                                                :class="{ 'is-invalid': errors.client_id }">
                                                <option v-for="client in clients" :key="client.id" :value="client.id">{{
                                                    client.name }}</option>
                                            </select>
                                            <span class="invalid-feedback">{{ errors.client_id }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="document_type">Document type</label>
                                            <select v-model="form.document_type" class="form-control" id="doc_type">
                                                <option v-for="document_type in document_types" :key="document_type.value"
                                                    :value="document_type.value">{{ document_type.name }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="title">Title</label>
                                    <input v-model="form.title" type="text" class="form-control"
                                        :class="{ 'is-invalid': errors.title }" id="title" placeholder="Enter Title">
                                    <span class="invalid-feedback">{{ errors.title }}</span>
                                </div>
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea v-model="form.description" class="form-control"
                                        :class="{ 'is-invalid': errors.description }" id="description" rows="3"
                                        placeholder="Enter Description"></textarea>
                                    <span class="invalid-feedback">{{ errors.description }}</span>
                                </div>
                                <div class="form-group">
                                    <label for="remarks">Remarks</label>
                                    <textarea class="form-control" id="remarks" rows="3"
                                        placeholder="Enter Remarks"></textarea>
                                </div>
                                <div class="form-group">
                                    <input type="file" class="form-control-file" id="document_file" name="document_file"
                                        @change="getFile" />
                                </div>
                                <button type="submit" class="btn btn-primary" :disabled="saving">
                                    <div v-if="saving" class="spinner-grow" style="width: 3rem; height: 3rem;" role="status">
                                        <span class="sr-only">Saving...</span>
                                    </div>
                                    <span v-else>Submit</span>
                                </button>
                            </Form>
                        </div>
                </div>
            </div>
        </div>
    </div>
</div></template>