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

const changeFile = ref(true);

const form = reactive({
    date_dispatched: '',
    recipient: '',
    recipient_office: '',
    subject: '',
    content: '',
    remarks: '',
    file: null,

});

const getFile = (event) => {
    form.file = event.target.files[0];
    changeFile.value = false;
}

const handleSubmit = (values, actions) => {
    if (editMode.value) {
        editDocument(values, actions);
    } else {
        createDocument(values, actions);
    }
}

const createDocument = (values, actions) => {
    const formData = new FormData();
    formData.append('document_file', form.file);
    formData.append('date_dispatched', form.date_dispatched);
    formData.append('subject', form.subject);
    formData.append('content', form.content);
    formData.append('remarks', form.remarks);
    formData.append('recipient', form.recipient);
    formData.append('recipient_office', form.recipient_office);
    saving.value = true;
    axios.post('/api/outgoing/create', formData)
        .then((response) => {
            router.push('/admin/outgoing');
            toastr.success('Document created');
        })
        .catch((error) => {
            actions.setErrors(error.response.data.errors);
        })
        .finally(() => {
            saving.value = false;
        })
    }

const editDocument = (values, actions) => {
    const formData = new FormData();
    formData.append('document_file', form.file);
    formData.append('date_dispatched', form.date_dispatched);
    formData.append('recipient', form.recipient);
    formData.append('recipient_office', form.recipient_office);
    formData.append('subject', form.subject);
    formData.append('content', form.content);
    formData.append('remarks', form.remarks);
    saving.value = true;
    axios.post(`/api/outgoing/${route.params.id}/edit`, formData)
        .then((response) => {
            router.push('/admin/outgoing');
            toastr.success('Document edited');
        })
        .catch((error) => {
            actions.setErrors(error.response.data.errors);
        })
        .finally(() => {
            saving.value = false;
        })
}

const getDocument = () => {
    axios.get(`/api/outgoing/${route.params.id}/edit`)
        .then(({ data }) => {
            form.date_dispatched = data.date_dispatched;
            form.recipient = data.recipient;
            form.recipient_office = data.recipient_office;
            form.subject = data.subject;
            form.content = data.content;
            form.remarks = data.remarks;
            form.file = data.document_file;
        })
}

const editMode = ref(false);

onMounted(() => {
    if (route.name === 'admin.outgoing.edit') {
        editMode.value = true;
        getDocument();
    }
    flatpickr(".flatpickr", {
        dateFormat: "Y-m-d",
    });
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
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="date">Date Dispatched</label>
                                            <input v-model="form.date_dispatched" type="text"
                                                class="form-control flatpickr"
                                                :class="{ 'is-invalid': errors.date_dispatched }" id="date_dispatched">
                                            <span class="invalid-feedback">{{ errors.date_dispatched }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="client">Recipient/Client Name</label>
                                            <input v-model="form.recipient" type="text" class="form-control"
                                                :class="{ 'is-invalid': errors.recipient }" id="recipient">
                                            <span class="invalid-feedback">{{ errors.recipient }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="client">Recipient/Client Office</label>
                                            <input v-model="form.recipient_office" type="text" class="form-control"
                                                :class="{ 'is-invalid': errors.recipient_office }" id="recipient_office">
                                            <span class="invalid-feedback">{{ errors.recipient_office }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="subject">Title</label>
                                            <input v-model="form.subject" type="text" class="form-control"
                                                :class="{ 'is-invalid': errors.subject }" id="subject"
                                                placeholder="Enter Subject">
                                            <span class="invalid-feedback">{{ errors.subject }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="content">Content</label>
                                    <textarea v-model="form.content" class="form-control"
                                        :class="{ 'is-invalid': errors.content }" id="description" rows="3"
                                        placeholder="Enter Content"></textarea>
                                    <span class="invalid-feedback">{{ errors.content }}</span>
                                </div>
                                <div class="form-group">
                                    <label for="remarks">Remarks</label>
                                    <textarea class="form-control" id="remarks" rows="3"
                                        placeholder="Enter Remarks"></textarea>
                                </div>
                                <div class="form-group">
                                    <input type="file" class="form-control-file" id="document_file" name="document_file"
                                        @change="getFile" />
                                    <span v-if="changeFile && editMode">Current file: {{ form.file }}</span>
                                </div>
                                <button type="submit" class="btn btn-primary">
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
    </div>
</template>