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
    employee_id: '',
});

const document_types = ref();

const getDocumentType = () => {
    axios.get('/api/document-type')
        .then((response) => {
            document_types.value = response.data;
        })
}

const employees = ref();

const getEmployees = () => {
    axios.get('/api/employees')
        .then((response) => {
            employees.value = response.data;
            console.log("employees", employees.value);
        })
}

const fileReady = ref(false)
const fileError = ref(null)
const selectedFile = ref(null);
const selectedFileName = ref('');
const fileInput = ref(null);

const getFile = async (event) => {
    const file = event.target.files[0];

    fileError.value = null
    fileReady.value = false

    if (!file) return

    if (file.type !== 'application/pdf') {
        fileError.value = 'Only PDF files allowed'
        return
    }

    try {
        await verifyReadable(file)
        selectedFile.value = file
        selectedFileName.value = file.name
        form.file = file;
        fileReady.value = true
    } catch {
        fileError.value = 'File could not be read'
    }


}

const removeFile = () => {
    selectedFile.value = null
    selectedFileName.value = ''
    form.file = null
    fileReady.value = false
    if (fileInput.value) {
        fileInput.value.value = ''
    }
}

const verifyReadable = (file) => {
    return new Promise((resolve, reject) => {
        const reader = new FileReader()
        reader.onload = () => resolve(true)
        reader.onerror = () => reject(false)
        reader.readAsArrayBuffer(file)
    })
}


const createDocument = (values, actions) => {

    if (!form.file) {
        toastr.error('Please attach a file before submitting')
        return
    }

    const formData = new FormData();
    formData.append('document_file', form.file);
    formData.append('client_id', form.client_id);
    formData.append('document_type', form.document_type);
    formData.append('date_received', form.date_received);
    formData.append('title', form.title);
    formData.append('description', form.description);
    formData.append('remarks', form.remarks);
    formData.append('employee_id', form.employee_id);
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
    if (form.file) {
        formData.append('document_file', form.file);
    }
    formData.append('client_id', form.client_id);
    formData.append('type', form.document_type);
    formData.append('date_received', form.date_received);
    formData.append('title', form.title);
    formData.append('description', form.description);
    formData.append('remarks', form.remarks);
    formData.append('employee_id', form.employee_id);
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
            form.employee_id = data.employee_id;
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
    getEmployees();
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
                                            <label for="date">Date Received</label>
                                            <input v-model="form.date_received" type="text"
                                                class="form-control flatpickr"
                                                :class="{ 'is-invalid': errors.date_received }" id="date_received">
                                            <span class="invalid-feedback">{{ errors.date_received }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
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
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="document_type">Document type</label>
                                            <select v-model="form.document_type" class="form-control" id="doc_type">
                                                <option v-for="document_type in document_types"
                                                    :key="document_type.value" :value="document_type.value">{{
                                                        document_type.name }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="document_type">Employee Assigned</label>
                                            <select v-model="form.employee_id" class="form-control" id="employee_id">
                                                <option v-for="employee in employees" :key="employee.id"
                                                    :value="employee.id">{{
                                                        employee.emp_name }}</option>
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
                                    <label for="document_file">Attach File</label>

                                    <div v-if="selectedFile">
                                        <span>{{ selectedFileName }}</span>
                                        <button type="button" class="btn btn-sm btn-danger ml-2" @click="removeFile">
                                            Remove
                                        </button>
                                    </div>

                                    <div v-else>
                                        <input ref="fileInput" type="file" class="form-control-file" id="document_file"
                                            accept="application/pdf" name="document_file" @change="getFile" />
                                    </div>
                                    <p v-if="fileReady" class="text-success text-sm mt-1">
                                        ✔ File ready to upload
                                    </p>

                                    <p v-if="fileError" class="text-danger text-sm mt-1">
                                        ✖ {{ fileError }}
                                    </p>
                                </div>

                                <!-- <div class="form-group">
                                    <label for="document_file">Attach File</label>
                                    <div v-if="selectedFile">
                                        <span>{{ selectedFileName }}</span>
                                        <button type="button" class="btn btn-sm btn-danger ml-2"
                                            @click="removeFile">Remove</button>
                                    </div>
                                    <div v-else>
                                        <input type="file" class="form-control-file" id="document_file"
                                            accept="application/file" name="document_file" @change="getFile" />
                                        <p v-if="fileReady" class="text-success text-sm">
                                            ✔ File ready to upload
                                        </p>
                                        <p v-if="fileError" class="text-danger text-sm">
                                            ✖ {{ fileError }}
                                        </p>

                                    </div>
                                </div> -->
                                <button type="submit" class="btn btn-primary"
                                    :disabled="saving || (!editMode && !fileReady)">
                                    <div v-if="saving" class="spinner-grow" style="width: 3rem; height: 3rem;"
                                        role="status">
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