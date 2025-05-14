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

const emit = defineEmits(['editTransaction']);

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
    if (editMode.value) {
        editDocument(values, actions);
    } else {
        createDocument(values, actions);
    }
}



const employees = ref();

const getEmployees = () => {
    axios.get('/api/employees')
        .then((response) => {
            employees.value =response.data;
        });
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

    getEmployees();
})
</script>

<template>
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <Form @submit="createRouteDocument()">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="employee">Employee</label>
                                            <select v-model="form.employee_id" id="employee_id" name="employee_id"
                                                class="form-control">
                                                <option v-for="employee in employees" :key="employee.id"
                                                    :value="employee.id">{{ employee.emp_name }}</option>
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
                    </div>
                </div>
            </div>
    </div>
</div>
</template>